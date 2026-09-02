<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Inventory;
use App\Services\OrderPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return Inertia::render('admin/OrdersPage', [
            'orders' => Order::with(['items', 'shippingMethod', 'paymentMethod'])->latest()->get()->map->toCatalog()->values(),
            'trashedOrders' => Order::onlyTrashed()->with(['items', 'shippingMethod', 'paymentMethod'])->latest('deleted_at')->limit(20)->get()->map->toCatalog()->values(),
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
            $order = Order::create([
                'order_number' => OrderPricing::nextOrderNumber(),
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

            // Pesanan customer selalu mulai dari 'pending' (belum potong stok) — pesanan manual admin
            // bisa langsung dibuat dengan status lebih maju, jadi potong stok kalau perlu.
            $this->syncStock($order, 'pending', $validated['status'], $request->user()->id);

            return $order;
        });

        return redirect()->route('admin.order', $order)->with('success', "Pesanan {$order->order_number} dibuat");
    }

    public function show(Order $order): Response
    {
        return Inertia::render('admin/OrderDetailPage', [
            'order' => $order->load(['items', 'shippingMethod', 'paymentMethod'])->toCatalog(),
        ]);
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order, $request): void {
            $this->restoreDeductedStock($order, $request->user()->id);
            $order->delete();
        });

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
                $this->restoreDeductedStock($order, $request->user()->id);
                $order->delete();
            }
        });

        return redirect()->route('admin.orders')->with('success', $orders->count().' pesanan dihapus. Bisa dipulihkan dari daftar pesanan terhapus.');
    }

    public function restore(Request $request, string $order_number): RedirectResponse
    {
        $order = Order::onlyTrashed()->where('order_number', $order_number)->firstOrFail();
        $order->restore();

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

            fputcsv($out, ['No. Pesanan', 'Tanggal', 'Pelanggan', 'Email', 'Telepon', 'Status', 'Pembayaran', 'Kanal', 'Barang Terjual', 'Subtotal', 'Ongkir', 'Total', 'Alamat']);

            Order::with(['items', 'shippingMethod', 'paymentMethod'])->latest()->cursor()->each(function (Order $order) use ($out): void {
                fputcsv($out, [
                    $order->order_number,
                    $order->created_at->toDateTimeString(),
                    $order->customer_name,
                    $order->customer_email,
                    $order->customer_phone,
                    $order->status,
                    optional($order->paymentMethod)->name ?? '',
                    $order->channel ?? '',
                    $order->items->sum(fn ($item) => (int) $item->qty),
                    $order->subtotal,
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

        $update = [
            'status' => $validated['status'],
            'awb' => $validated['awb'] ?? null,
            'admin_note' => $validated['adminNote'] ?? null,
        ];

        if (array_key_exists('shippingCost', $validated) && $validated['shippingCost'] !== null) {
            $update['shipping_cost'] = $validated['shippingCost'];
            $update['total'] = $order->subtotal + $validated['shippingCost'];
        }

        DB::transaction(function () use ($order, $update, $validated, $request): void {
            $previous = $order->status;
            $order->update($update);
            $this->syncStock($order, $previous, $validated['status'], $request->user()->id);
        });

        return back()->with('success', "Status {$order->order_number} diperbarui");
    }

    /**
     * Potong stok saat pesanan masuk ke processing/shipped dan kembalikan saat dibatalkan.
     * Idempoten: gerakan unik per (produk, nomor pesanan, jenis).
     */
    private function syncStock(Order $order, string $from, string $to, int $userId): void
    {
        if ($from === $to) {
            return;
        }

        $items = $order->items()->whereNotNull('product_id')->with('product')->get();

        if (in_array($to, self::STOCK_DEDUCT_STATES, true)) {
            foreach ($items as $item) {
                if (! $item->product || $this->isCurrentlyDeducted($item, $order)) {
                    continue;
                }

                Inventory::lock($item->product, fn (Product $locked) => Inventory::apply(
                    $locked,
                    'sale',
                    -(int) $item->qty,
                    $userId,
                    null,
                    "Pesanan {$order->order_number}",
                    $order->order_number,
                ));
            }

            return;
        }

        if ($to === 'cancelled') {
            $this->restoreDeductedStock($order, $userId);
        }
    }

    /**
     * Kembalikan stok yang sudah terpotong untuk pesanan ini (idempoten — aman dipanggil berkali-kali,
     * atau untuk pesanan yang belum pernah potong stok sama sekali). Dipakai saat pesanan dibatalkan
     * maupun saat pesanan dihapus, supaya angka stok tidak pernah "nyangkut" pada pesanan yang sudah tidak ada.
     */
    private function restoreDeductedStock(Order $order, int $userId): void
    {
        $items = $order->items()->whereNotNull('product_id')->with('product')->get();

        foreach ($items as $item) {
            if (! $item->product || ! $this->isCurrentlyDeducted($item, $order)) {
                continue;
            }

            Inventory::lock($item->product, fn (Product $locked) => Inventory::apply(
                $locked,
                'sale_cancelled',
                (int) $item->qty,
                $userId,
                null,
                "Pesanan {$order->order_number} dihapus/dibatalkan",
                $order->order_number,
            ));
        }
    }

    /**
     * Apakah stok item ini SAAT INI masih dalam keadaan terpotong untuk pesanan ini — dihitung dari selisih
     * jumlah gerakan 'sale' dan 'sale_cancelled', bukan sekadar "pernah ada gerakan sale", supaya siklus
     * potong→kembalikan→potong lagi (mis. processing → dihapus → dipulihkan) tetap dihitung benar.
     */
    private function isCurrentlyDeducted(OrderItem $item, Order $order): bool
    {
        $deductedCount = $item->product->movements()
            ->where('type', 'sale')
            ->where('order_number', $order->order_number)
            ->count();

        $restoredCount = $item->product->movements()
            ->where('type', 'sale_cancelled')
            ->where('order_number', $order->order_number)
            ->count();

        return $deductedCount > $restoredCount;
    }
}
