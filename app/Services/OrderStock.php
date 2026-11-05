<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

/**
 * Satu-satunya tempat untuk memotong/mengembalikan stok sebuah pesanan.
 *
 * Dipakai admin (ubah status), pembatalan otomatis karena batas bayar, dan
 * penghapusan pesanan. Semua operasi idempoten: aman dipanggil berulang tanpa
 * membuat stok terpotong dua kali atau kembali dua kali.
 */
class OrderStock
{
    public static function deduct(Order $order, ?int $userId = null): void
    {
        $items = $order->items()->whereNotNull('product_id')->with('product')->get();

        foreach ($items as $item) {
            if (! $item->product || self::isDeducted($item, $order)) {
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
    }

    public static function restore(Order $order, ?int $userId = null): void
    {
        $items = $order->items()->whereNotNull('product_id')->with('product')->get();

        foreach ($items as $item) {
            if (! $item->product || ! self::isDeducted($item, $order)) {
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

    /**
     * Dihitung dari selisih gerakan 'sale' dan 'sale_cancelled', bukan sekadar
     * "pernah ada sale", supaya siklus potong → kembali → potong lagi benar.
     */
    public static function isDeducted(OrderItem $item, Order $order): bool
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
