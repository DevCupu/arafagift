<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentConfirmation;
use App\Models\Product;
use App\Services\OrderLifecycle;
use App\Services\OrderNotifier;
use App\Services\OrderPricing;
use App\Services\OrderStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOrderController extends Controller
{
    private const STOCK_DEDUCT_STATES = ['processing', 'shipped'];

    public function index(): Response
    {
        OrderLifecycle::sweepIfDue();

        return Inertia::render('admin/OrdersPage', [
            'orders' => Order::with(['items', 'shippingMethod', 'paymentMethod', 'latestPaymentConfirmation'])
                ->latest()
                ->get()
                ->map->toCatalog()
                ->values(),
            'trashedOrders' => Order::onlyTrashed()->with(['items', 'shippingMethod', 'paymentMethod'])
                ->latest('deleted_at')->limit(20)->get()->map->toCatalog()->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/OrderFormPage', [
            'products' => Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'price', 'stock', 'art']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'])],
            'shippingCost' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'adminNote' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Harga & stok dihitung dari database, bukan dari input admin, supaya konsisten dengan checkout customer.
        $priced = OrderPricing::priceItems($validated['items']);
        $shippingCost = (float) ($validated['shippingCost'] ?? 0);

        $order = DB::transaction(function () use ($validated, $priced, $shippingCost, $request): Order {
            $paid = in_array($validated['status'], ['paid', 'processing', 'shipped', 'completed'], true);

            $order = Order::create([
                'order_number' => OrderPricing::nextOrderNumber(),
                'tracking_token' => OrderPricing::trackingToken(),
                'customer_name' => $validated['name'],
                'customer_email' => $validated['email'] ?? '',
                'customer_phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal'] ?? null,
                'note' => $validated['note'] ?? null,
                'admin_note' => $validated['adminNote'] ?? null,
                'status' => $validated['status'],
                'payment_status' => $paid ? 'paid' : 'unpaid',
                // Pesanan yang dibuat admin tidak punya batas bayar otomatis:
                // pembayarannya disepakati langsung dengan pelanggan.
                'payment_confirmed_at' => $paid ? now() : null,
                'fulfillment_type' => 'delivery',
                'channel' => 'Admin',
                'shipping_cost' => $shippingCost,
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

            OrderEvent::log($order, OrderEvent::TYPE_ORDER_CREATED, null, $validated['status'], $request->user(), [
                'channel' => 'Admin',
            ]);

            if ($paid) {
                OrderEvent::log($order, OrderEvent::TYPE_PAYMENT_STATUS_CHANGED, 'unpaid', 'paid', $request->user(), [
                    'reason' => 'dibuat admin',
                ]);
            }

            // Pesanan customer selalu mulai dari 'pending' (belum potong stok) — pesanan manual admin
            // bisa langsung dibuat dengan status lebih maju, jadi potong stok kalau perlu.
            $this->syncStock($order, 'pending', $validated['status'], $request->user()->id);

            return $order;
        });

        Cache::forget('pending-orders-count');

        // Admin yang membuat pesanan tidak perlu salinan email "pesanan baru",
        // tapi pelanggan tetap dapat konfirmasi kalau emailnya diisi.
        OrderNotifier::orderPlaced($order, notifyAdmin: false);

        return redirect()->route('admin.order', $order)->with('success', "Pesanan {$order->order_number} dibuat");
    }

    public function show(Order $order): Response
    {
        OrderLifecycle::sweepIfDue();

        $order->load([
            'items',
            'shippingMethod',
            'paymentMethod',
            'paymentConfirmations' => fn ($query) => $query->latest(),
            'paymentConfirmations.reviewer',
            'latestPaymentConfirmation',
            'events.actor',
        ]);

        return Inertia::render('admin/OrderDetailPage', [
            'order' => $order->toCatalog(),
        ]);
    }

    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(OrderLifecycle::PAYMENT_STATUSES)],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $previous = $order->payment_status ?? 'unpaid';

        OrderLifecycle::changePaymentStatus(
            $order,
            $validated['payment_status'],
            $request->user(),
            $validated['payment_reference'] ?? null,
        );

        if ($validated['payment_status'] === 'paid' && $previous !== 'paid') {
            OrderNotifier::paymentVerified($order);
        }

        $label = match ($validated['payment_status']) {
            'paid' => 'Lunas',
            'refunded' => 'Refund',
            'waiting_confirmation' => 'Menunggu verifikasi',
            default => 'Belum bayar',
        };

        Cache::forget('pending-orders-count');

        return back()->with('success', "Pembayaran {$order->order_number} ditandai: {$label}.");
    }

    /**
     * Admin memverifikasi bukti bayar: pesanan langsung lunas, bukti lain yang
     * masih menunggu otomatis ditolak supaya tidak ada dua bukti aktif.
     */
    public function verifyPayment(Request $request, Order $order, PaymentConfirmation $confirmation): RedirectResponse
    {
        abort_unless($confirmation->order_id === $order->id, 404);

        $validated = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        if ($confirmation->status === PaymentConfirmation::STATUS_VERIFIED) {
            return back()->with('success', 'Bukti pembayaran ini sudah diverifikasi sebelumnya.');
        }

        DB::transaction(function () use ($request, $order, $confirmation, $validated): void {
            $confirmation->update([
                'status' => PaymentConfirmation::STATUS_VERIFIED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $order->paymentConfirmations()
                ->whereKeyNot($confirmation->id)
                ->where('status', PaymentConfirmation::STATUS_SUBMITTED)
                ->update([
                    'status' => PaymentConfirmation::STATUS_REJECTED,
                    'rejection_reason' => 'Digantikan bukti lain yang sudah diverifikasi.',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

            OrderLifecycle::changePaymentStatus(
                $order,
                'paid',
                $request->user(),
                $validated['payment_reference'] ?? null,
                'Bukti pembayaran diverifikasi',
            );

            OrderEvent::log($order, OrderEvent::TYPE_PAYMENT_VERIFIED, null, null, $request->user(), [
                'confirmation_id' => $confirmation->id,
                'amount' => $confirmation->amount,
                'sender_name' => $confirmation->sender_name,
            ]);
        });

        Cache::forget('pending-orders-count');

        OrderNotifier::paymentVerified($order);

        return back()->with('success', "Pembayaran {$order->order_number} diverifikasi dan ditandai lunas.");
    }

    public function rejectPayment(Request $request, Order $order, PaymentConfirmation $confirmation): RedirectResponse
    {
        abort_unless($confirmation->order_id === $order->id, 404);

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:255'],
        ], [
            'rejection_reason.required' => 'Tulis alasan penolakan agar pelanggan tahu apa yang harus diperbaiki.',
        ]);

        DB::transaction(function () use ($request, $order, $confirmation, $validated): void {
            $confirmation->update([
                'status' => PaymentConfirmation::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            $hasOtherProof = $order->paymentConfirmations()
                ->where('status', PaymentConfirmation::STATUS_SUBMITTED)
                ->exists();

            if (! $hasOtherProof && $order->payment_status === 'waiting_confirmation') {
                OrderLifecycle::changePaymentStatus(
                    $order,
                    'unpaid',
                    $request->user(),
                    null,
                    $validated['rejection_reason'],
                );

                // Kalau tenggat lama sudah lewat (atau tidak ada), mulai ulang
                // supaya pelanggan sempat mengunggah bukti pengganti.
                if ($order->payment_due_at === null || $order->payment_due_at->isPast()) {
                    OrderLifecycle::extendPaymentWindow($order);
                }
            }

            OrderEvent::log($order, OrderEvent::TYPE_PAYMENT_REJECTED, null, null, $request->user(), [
                'confirmation_id' => $confirmation->id,
                'reason' => $validated['rejection_reason'],
            ]);
        });

        Cache::forget('pending-orders-count');

        OrderNotifier::paymentRejected($order, $validated['rejection_reason']);

        return back()->with('success', "Bukti pembayaran {$order->order_number} ditolak. Pelanggan bisa mengunggah ulang.");
    }

    /**
     * Bukti bayar disimpan di disk private, jadi hanya admin yang login yang
     * bisa membukanya. Header no-store mencegah file tersimpan di cache browser
     * atau proxy.
     */
    public function paymentProof(PaymentConfirmation $confirmation): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($confirmation->path), 404);

        // Hanya tipe yang memang diizinkan saat unggah. Data lama bisa saja
        // menyimpan MIME dari klien, jadi jangan langsung percaya kolom itu.
        $mime = in_array($confirmation->mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'], true)
            ? $confirmation->mime
            : 'application/octet-stream';

        return Storage::disk('local')->response(
            $confirmation->path,
            $confirmation->original_name ?: basename($confirmation->path),
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order, $request): void {
            OrderStock::restore($order, $request->user()->id);
            $order->delete();
        });

        Cache::forget('pending-orders-count');

        return redirect()->route('admin.orders')->with('success', "Pesanan {$order->order_number} dihapus. Bisa dipulihkan dari daftar pesanan terhapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_numbers' => ['required', 'array', 'min:1'],
            'order_numbers.*' => ['required', 'string', 'exists:orders,order_number'],
        ]);

        $orders = Order::whereIn('order_number', $validated['order_numbers'])->get();

        DB::transaction(function () use ($orders, $request): void {
            foreach ($orders as $order) {
                OrderStock::restore($order, $request->user()->id);
                $order->delete();
            }
        });

        Cache::forget('pending-orders-count');

        return redirect()->route('admin.orders')->with('success', $orders->count().' pesanan dihapus. Bisa dipulihkan dari daftar pesanan terhapus.');
    }

    public function restore(Request $request, string $order_number): RedirectResponse
    {
        $order = Order::onlyTrashed()->where('order_number', $order_number)->firstOrFail();
        $order->restore();

        Cache::forget('pending-orders-count');

        // ponytail: stok yang sudah dikembalikan saat dihapus TIDAK dipotong ulang di sini — kolom
        // stock_movements punya unique constraint per (produk, nomor pesanan, jenis), jadi "sale" kedua
        // untuk pesanan yang sama tidak bisa disimpan. Kalau pesanan yang dipulihkan berstatus
        // processing/shipped, admin perlu cek ulang stoknya manual di halaman Inventori.
        return back()->with('success', "Pesanan {$order->order_number} dipulihkan. Jika statusnya sudah Diproses/Dikirim, periksa kembali stoknya di halaman Inventori.");
    }

    public function export(): StreamedResponse
    {
        $filename = 'pesanan-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                throw new RuntimeException('Tidak dapat membuka output untuk ekspor CSV.');
            }

            fputcsv($out, ['No. Pesanan', 'Tanggal', 'Pelanggan', 'Email', 'Telepon', 'Tipe', 'Status', 'Status Bayar', 'Batas Bayar', 'Referensi Bayar', 'Pembayaran', 'Kanal', 'Barang Terjual', 'Subtotal', 'Diskon', 'Ongkir', 'Total', 'Alamat']);

            Order::with(['items', 'shippingMethod', 'paymentMethod'])->latest()->cursor()->each(function (Order $order) use ($out): void {
                fputcsv($out, [
                    $order->order_number,
                    $order->created_at->toDateTimeString(),
                    $order->customer_name,
                    $order->customer_email,
                    $order->customer_phone,
                    $order->isPickup() ? 'Ambil di Tempat' : 'Diantar',
                    $order->status,
                    $order->payment_status ?? 'unpaid',
                    $order->payment_due_at?->toDateTimeString() ?? '',
                    $order->payment_reference ?? '',
                    optional($order->paymentMethod)->name ?? '',
                    $order->channel ?? '',
                    $order->items->sum(fn ($item) => (int) $item->qty),
                    $order->subtotal,
                    $order->discount_total,
                    $order->shipping_cost,
                    $order->total,
                    trim(implode(', ', array_filter([
                        $order->address, $order->city, $order->province,
                    ])).' '.$order->postal_code),
                ]);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled'])],
            'awb' => ['nullable', 'string', 'max:60'],
            'shippingCost' => ['nullable', 'numeric', 'min:0'],
            'adminNote' => ['nullable', 'string', 'max:2000'],
        ]);

        $previousStatus = $order->status;
        $previousPayment = $order->payment_status ?? 'unpaid';

        $update = [
            'status' => $validated['status'],
            'awb' => $validated['awb'] ?? null,
            'admin_note' => $validated['adminNote'] ?? null,
        ];

        // Status pesanan yang lebih maju menandakan pembayaran sudah diterima, kecuali
        // admin sudah menandainya refund.
        if (in_array($previousPayment, ['unpaid', 'waiting_confirmation'], true)
            && in_array($validated['status'], ['paid', 'processing', 'shipped', 'completed'], true)) {
            $update['payment_status'] = 'paid';
            $update['payment_confirmed_at'] = $order->payment_confirmed_at ?? now();
        }

        if (array_key_exists('shippingCost', $validated) && $validated['shippingCost'] !== null) {
            $update['shipping_cost'] = $validated['shippingCost'];
            $update['total'] = $order->subtotal - (int) $order->discount_total + $validated['shippingCost'];
        }

        DB::transaction(function () use ($order, $update, $validated, $request, $previousStatus, $previousPayment): void {
            $order->update($update);

            if ($previousStatus !== $validated['status']) {
                OrderEvent::log($order, OrderEvent::TYPE_STATUS_CHANGED, $previousStatus, $validated['status'], $request->user());
            }

            if (isset($update['payment_status']) && $previousPayment !== $update['payment_status']) {
                OrderEvent::log($order, OrderEvent::TYPE_PAYMENT_STATUS_CHANGED, $previousPayment, $update['payment_status'], $request->user(), [
                    'reason' => 'status pesanan diperbarui',
                ]);
            }

            $this->syncStock($order, $previousStatus, $validated['status'], $request->user()->id);
        });

        Cache::forget('pending-orders-count');

        return back()->with('success', "Status {$order->order_number} diperbarui");
    }

    /**
     * Potong stok saat pesanan masuk ke processing/shipped dan kembalikan saat dibatalkan.
     */
    private function syncStock(Order $order, string $from, string $to, ?int $userId): void
    {
        if ($from === $to) {
            return;
        }

        if (in_array($to, self::STOCK_DEDUCT_STATES, true)) {
            OrderStock::deduct($order, $userId);

            return;
        }

        if ($to === 'cancelled') {
            OrderStock::restore($order, $userId);
        }
    }
}
