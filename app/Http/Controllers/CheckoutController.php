<?php

namespace App\Http\Controllers;

use App\Exceptions\RajaOngkirException;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderPricing;
use App\Services\RajaOngkirService;
use App\Support\StoreSettingsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal' => ['nullable', 'string', 'max:10'],
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
        ]);

        $ids = array_values(array_unique(array_column($validated['items'], 'id')));
        if (Product::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['items' => 'Salah satu produk tidak tersedia.']);
        }

        // Harga & stok dihitung dari database, bukan dari input klien, supaya tidak bisa dimanipulasi.
        $priced = OrderPricing::priceItems($validated['items']);

        [$shippingCost, $shippingCourier, $shippingService, $shippingEtd, $note] = $this->resolveShipping(
            $validated, $priced,
        );

        $order = Order::create([
            'order_number' => OrderPricing::nextOrderNumber(),
            'user_id' => $request->user()?->id,
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'] ?? '',
            'customer_phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal'] ?? null,
            'shipping_destination_id' => $validated['destination_id'] ?? null,
            'shipping_courier' => $shippingCourier,
            'shipping_service' => $shippingService,
            'shipping_etd' => $shippingEtd,
            'shipping_cost' => $shippingCost,
            'gift_message' => $validated['giftMessage'] ?? null,
            'note' => $note,
            'hide_invoice' => $validated['hideInvoice'] ?? true,
            'status' => 'pending',
            'channel' => 'Website',
            'subtotal' => $priced['subtotal'],
            'total' => $priced['subtotal'] + $shippingCost,
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

        Cache::forget('pending-orders-count');

        return response()->json($order->load('items')->toCatalog());
    }

    /**
     * Re-hitung ongkir dari RajaOngkir server-side (jangan percaya angka dari client), lalu terapkan
     * override gratis ongkir. Kalau titik asal belum diatur admin atau API RajaOngkir gagal/timeout,
     * checkout tetap lanjut dengan ongkir 0 dan catatan otomatis untuk ditindaklanjuti manual via WhatsApp
     * — sama seperti perilaku sebelum integrasi ini ada.
     *
     * @param  array{city: string, destination_id?: string|null, courier?: string|null, service?: string|null, note?: string|null}  $validated
     * @param  array{subtotal: int|float, lines: array<int, array{product: Product, qty: int}>}  $priced
     * @return array{0: int, 1: string|null, 2: string|null, 3: string|null, 4: string|null}
     */
    private function resolveShipping(array $validated, array $priced): array
    {
        $store = StoreSettingsCache::store();
        $note = $validated['note'] ?? null;
        $freeShipping = $this->hasFreeShipping($store, $validated['city'], $priced['subtotal']);

        $origin = $store['originDestinationId'];
        $destinationId = $validated['destination_id'] ?? null;
        $courier = $validated['courier'] ?? null;
        $service = $validated['service'] ?? null;

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
