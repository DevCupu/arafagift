<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Inventory;
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
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('admin/OrderDetailPage', [
            'order' => $order->load(['items', 'shippingMethod', 'paymentMethod'])->toCatalog(),
        ]);
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
                $alreadyDeducted = $item->product->movements()
                    ->where('type', 'sale')
                    ->where('order_number', $order->order_number)
                    ->exists();

                if ($alreadyDeducted) {
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
            foreach ($items as $item) {
                $deducted = $item->product->movements()
                    ->where('type', 'sale')
                    ->where('order_number', $order->order_number)
                    ->exists();

                $alreadyRestored = $item->product->movements()
                    ->where('type', 'sale_cancelled')
                    ->where('order_number', $order->order_number)
                    ->exists();

                if (! $deducted || $alreadyRestored) {
                    continue;
                }

                Inventory::lock($item->product, fn (Product $locked) => Inventory::apply(
                    $locked,
                    'sale_cancelled',
                    (int) $item->qty,
                    $userId,
                    null,
                    "Pesanan {$order->order_number} dibatalkan",
                    $order->order_number,
                ));
            }
        }
    }
}
