<?php

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Product::query()->orderBy('id')->get()->each(function (Product $product, int $index): void {
            if ($product->stock === 0) {
                return;
            }

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'initial',
                'delta' => $product->stock,
                'balance_before' => 0,
                'balance_after' => $product->stock,
                'note' => 'Saldo awal saat migrasi inventori',
                'document_number' => sprintf('IN-%s-%04d', now()->format('Ymd'), $index + 1),
            ]);
        });
    }

    public function down(): void
    {
        StockMovement::where('type', 'initial')->delete();
    }
};
