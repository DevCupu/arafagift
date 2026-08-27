<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $user
 * @property-read Supplier|null $supplier
 */
#[Fillable([
    'product_id', 'user_id', 'supplier_id', 'type', 'delta',
    'balance_before', 'balance_after', 'note', 'order_number', 'document_number',
])]
class StockMovement extends Model
{
    /** @var array<string, int> type => arah delta (1 masuk, -1 keluar) */
    public const DIRECTIONS = [
        'initial' => 1,
        'purchase' => 1,
        'customer_return' => 1,
        'manual_in' => 1,
        'sale_cancelled' => 1,
        'sale' => -1,
        'internal_use' => -1,
        'damage' => -1,
        'loss' => -1,
        'supplier_return' => -1,
        'adjustment' => 0,
    ];

    public const LABELS = [
        'initial' => 'Stok awal',
        'purchase' => 'Pembelian supplier',
        'customer_return' => 'Retur pelanggan',
        'manual_in' => 'Tambah manual',
        'sale_cancelled' => 'Pembatalan pesanan',
        'sale' => 'Penjualan',
        'internal_use' => 'Pemakaian internal',
        'damage' => 'Barang rusak',
        'loss' => 'Barang hilang',
        'supplier_return' => 'Retur ke supplier',
        'adjustment' => 'Penyesuaian opname',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public static function directionOf(string $type): int
    {
        return self::DIRECTIONS[$type] ?? 0;
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }

    public static function nextDocumentNumber(string $type): string
    {
        $direction = self::directionOf($type);
        $prefix = match (true) {
            $direction > 0 => 'IN',
            $direction < 0 => 'OUT',
            default => 'ADJ',
        };
        $stamp = now()->format('Ymd');
        $sequence = self::where('document_number', 'like', "{$prefix}-{$stamp}-%")->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $stamp, $sequence);
    }
}
