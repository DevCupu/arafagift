<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class Inventory
{
    /**
     * Catat gerakan stok untuk produk yang sudah di-lock. Panggil di dalam DB::transaction().
     */
    public static function apply(
        Product $product,
        string $type,
        int $delta,
        ?int $userId = null,
        ?int $supplierId = null,
        ?string $note = null,
        ?string $orderNumber = null,
    ): StockMovement {
        $balanceAfter = $product->stock + $delta;

        if ($balanceAfter < 0) {
            $shortage = abs($delta) - $product->stock;
            throw new InsufficientStockException($product, $shortage);
        }

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'supplier_id' => $supplierId,
            'type' => $type,
            'delta' => $delta,
            'balance_before' => $product->stock,
            'balance_after' => $balanceAfter,
            'note' => $note,
            'order_number' => $orderNumber,
            'document_number' => StockMovement::nextDocumentNumber($type),
        ]);

        $update = ['stock' => $balanceAfter];

        // Stok pulih di atas ambang: periode menipis berikutnya boleh memicu alert baru.
        if ($balanceAfter > $product->low_stock_threshold) {
            $update['low_stock_notified_at'] = null;
        }

        $product->update($update);

        return $movement;
    }

    /**
     * Jalankan callback dengan lock pessimistic pada satu produk.
     *
     * @template T
     *
     * @param  callable(Product): T  $callback
     * @return T
     */
    public static function lock(Product $product, callable $callback)
    {
        return DB::transaction(function () use ($product, $callback) {
            $fresh = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            return $callback($fresh);
        });
    }
}
