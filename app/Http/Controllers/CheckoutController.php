<?php

namespace App\Http\Controllers;

use App\Exceptions\RajaOngkirException;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\OrderPricing;
use App\Services\RajaOngkirService;
use App\Services\VoucherPricing;
use App\Support\Phone;
use App\Support\StoreSettingsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    public function store(Request $request): JsonResponse
    {
        $request->merge(['phone' => Phone::normalize((string) $request->input('phone', ''))]);

        $fulfillment = $request->input('fulfillment_type', 'delivery');

        $validated = $request->validate([
            'fulfillment_type' => ['required', Rule::in(['delivery', 'pickup'])],
            'payment_method' => ['required', 'string', 'max:30'],
            'voucher_code' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^0[0-9]{8,15}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => [Rule::requiredIf($fulfillment === 'delivery'), 'nullable', 'string', 'min:8', 'max:500'],
            'city' => [Rule::requiredIf($fulfillment === 'delivery'), 'nullable', 'string', 'min:2', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
            // Nullable: kalau pencarian tujuan/ongkir RajaOngkir gagal total di sisi pelanggan, checkout tetap
            // boleh lanjut manual (lihat resolveShipping) daripada memblokir pemesanan.
            'destination_id' => ['nullable', 'string', 'max:50'],
            'courier' => ['nullable', 'string', 'max:30'],
            'service' => ['nullable', 'string', 'max:30'],
            'giftMessage' => ['nullable', 'string', 'max:180'],
            'note' => ['nullable', 'string', 'max:500'],
            'hideInvoice' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'name.min' => 'Nama lengkap terlalu pendek.',
            'phone.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890).',
            'address.required' => 'Tulis alamat lebih lengkap (jalan & nomor rumah).',
            'address.min' => 'Tulis alamat lebih lengkap (jalan & nomor rumah).',
            'city.required' => 'Isi kota atau kabupaten tujuan.',
            'city.min' => 'Isi kota atau kabupaten tujuan.',
            'postal.regex' => 'Kode pos harus 5 angka.',
            'payment_method.required' => 'Pilih metode pembayaran.',
        ]);

        $store = StoreSettingsCache::store();

        // Toko tutup/libur: pesanan baru tidak boleh masuk supaya tidak menumpuk tanpa konfirmasi.
        if (($store['storeStatus'] ?? 'open') !== 'open') {
            throw ValidationException::withMessages([
                'checkout' => $store['closedMessage'] ?: 'Toko sedang tutup. Silakan coba lagi saat jam operasional.',
            ]);
        }

        if ($validated['fulfillment_type'] === 'pickup' && ! ($store['pickupEnabled'] ?? true)) {
            throw ValidationException::withMessages([
                'fulfillment_type' => 'Pengambilan di toko sedang tidak tersedia.',
            ]);
        }

        $payment = PaymentMethod::active()->where('code', $validated['payment_method'])->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran tidak tersedia. Silakan pilih metode lain.',
            ]);
        }

        $ids = array_values(array_unique(array_column($validated['items'], 'id')));
        if (Product::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['items' => 'Salah satu produk tidak tersedia.']);
        }

        // Harga & stok dihitung dari database, bukan dari input klien, supaya tidak bisa dimanipulasi.
        $priced = OrderPricing::priceItems($validated['items']);

        $minOrder = (int) ($store['minOrderAmount'] ?? 0);
        if ($minOrder > 0 && $priced['subtotal'] < $minOrder) {
            throw ValidationException::withMessages([
                'items' => 'Minimum belanja untuk checkout adalah Rp'.number_format($minOrder, 0, ',', '.').'.',
            ]);
        }

        $voucher = VoucherPricing::resolve($validated['voucher_code'] ?? null, $priced['lines']);
        $discount = $voucher['discount'] ?? 0;

        [$shippingCost, $shippingCourier, $shippingService, $shippingEtd, $note] = $this->resolveShipping(
            $validated, $priced, $store,
        );

        $order = DB::transaction(function () use ($request, $validated, $priced, $payment, $voucher, $discount, $shippingCost, $shippingCourier, $shippingService, $shippingEtd, $note): Order {
            $isPickup = $validated['fulfillment_type'] === 'pickup';

            $order = Order::create([
                'order_number' => OrderPricing::nextOrderNumber(),
                'tracking_token' => OrderPricing::trackingToken(),
                'user_id' => $request->user()?->id,
                'customer_name' => $validated['name'],
                'customer_email' => $validated['email'] ?? '',
                'customer_phone' => $validated['phone'],
                'address' => $isPickup ? '' : $validated['address'],
                'city' => $isPickup ? 'Ambil di Toko' : $validated['city'],
                'province' => $isPickup ? null : ($validated['province'] ?? null),
                'postal_code' => $isPickup ? null : ($validated['postal'] ?? null),
                'shipping_destination_id' => $isPickup ? null : ($validated['destination_id'] ?? null),
                'shipping_courier' => $shippingCourier,
                'shipping_service' => $shippingService,
                'shipping_etd' => $shippingEtd,
                'shipping_cost' => $shippingCost,
                'payment_method_id' => $payment->id,
                'fulfillment_type' => $validated['fulfillment_type'],
                'payment_status' => 'unpaid',
                'voucher_code' => $voucher['promotion']->code ?? null,
                'discount_total' => $discount,
                'gift_message' => $validated['giftMessage'] ?? null,
                'note' => $note,
                'hide_invoice' => $validated['hideInvoice'] ?? true,
                'status' => 'pending',
                'channel' => 'Website',
                'subtotal' => $priced['subtotal'],
                'total' => $priced['subtotal'] - $discount + $shippingCost,
            ]);

            foreach ($priced['lines'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'name' => $line['product']->name,
                    'sku' => $line['product']->sku ?? '-',
                    'art' => $line['product']->art ?? '-',
                    'qty' => $line['qty'],
                    'price' => $line['product']->price,
                ]);
            }

            if ($voucher) {
                $voucher['promotion']->increment('used_count');
            }

            return $order;
        });

        Cache::forget('pending-orders-count');

        $catalog = $order->load(['items', 'paymentMethod'])->toCatalog();
        $catalog['trackingUrl'] = route('order.track.token', $order->tracking_token);

        return response()->json($catalog);
    }

    /**
     * Re-hitung ongkir dari RajaOngkir server-side (jangan percaya angka dari client), lalu terapkan
     * override gratis ongkir. Kalau titik asal belum diatur admin atau API RajaOngkir gagal/timeout,
     * checkout tetap lanjut dengan ongkir 0 dan catatan otomatis untuk ditindaklanjuti manual via WhatsApp
     * — sama seperti perilaku sebelum integrasi ini ada.
     *
     * @param  array{city: string, destination_id?: string|null, courier?: string|null, service?: string|null, note?: string|null}  $validated
     * @param  array{subtotal: int|float, lines: array<int, array{product: Product, qty: int}>}  $priced
     * @param  array<string, mixed>  $store
     * @return array{0: int, 1: string|null, 2: string|null, 3: string|null, 4: string|null}
     */
    private function resolveShipping(array $validated, array $priced, array $store): array
    {
        $note = $validated['note'] ?? null;

        if (($validated['fulfillment_type'] ?? 'delivery') === 'pickup') {
            return [0, null, null, null, $note];
        }

        $freeShipping = $this->hasFreeShipping($store, $validated['city'], $priced['subtotal']);

        $origin = $store['originDestinationId'];
        $destinationId = $validated['destination_id'] ?? null;
        $courier = $validated['courier'] ?? null;
        $service = $validated['service'] ?? null;

        if ($courier === 'arafagift') {
            $isMakassar = preg_match('/\bmakassar\b/i', $validated['city']) === 1;
            $noteText = $isMakassar
                ? '[Sistem] Kurir ArafahGift gratis untuk Kota Makassar; jadwal pengantaran dikonfirmasi manual melalui WhatsApp.'
                : '[Sistem] Kurir ArafahGift di luar Makassar; biaya dan jadwal pengantaran dikonfirmasi manual melalui WhatsApp.';
            $note = trim(($note ? $note."\n" : '').$noteText);

            return [0, 'arafagift', $service ?: 'Antar oleh tim ArafahGift', null, $note];
        }

        if (! $origin || ! $destinationId || ! $courier || ! $service) {
            if (! $freeShipping) {
                $note = trim(($note ? $note."\n" : '').'[Sistem] Ongkir belum dikonfirmasi otomatis, mohon konfirmasi manual.');
            }

            return [0, null, null, null, $note];
        }

        try {
            $weight = OrderPricing::totalWeightGrams($priced['lines']);
            $options = $this->rajaOngkir->getCosts($origin, $destinationId, $weight);
            $match = collect($options)->first(
                fn (array $o) => $o['courier'] === $courier && $o['service'] === $service,
            );
            abort_if(! $match, 422, 'Opsi pengiriman tidak lagi tersedia, silakan pilih ulang.');

            return [$freeShipping ? 0 : $match['cost'], $match['courier'], $match['service'], $match['etd'], $note];
        } catch (RajaOngkirException) {
            $note = trim(($note ? $note."\n" : '').'[Sistem] Ongkir belum terverifikasi otomatis, mohon konfirmasi manual.');

            return [0, null, null, null, $note];
        }
    }

    private function hasFreeShipping(array $store, string $city, int|float $subtotal): bool
    {
        $freeFrom = (int) ($store['freeShippingFrom'] ?? 0);
        $byAmount = $freeFrom > 0 && $subtotal >= $freeFrom;
        $byCity = collect($store['freeShippingCities'] ?? [])
            ->contains(fn (string $c) => str_contains(strtolower($city), strtolower($c)));

        return $byAmount || $byCity;
    }
}
