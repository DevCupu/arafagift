<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Validation\ValidationException;

class VoucherPricing
{
    /**
     * Validasi kode voucher dan hitung diskonnya dari baris pesanan yang sudah dihargai server-side.
     *
     * @param  array<int, array{product: Product, qty: int}>  $lines
     * @return array{promotion: Promotion, discount: int}|null
     *
     * @throws ValidationException
     */
    public static function resolve(?string $code, array $lines): ?array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $promotion = Promotion::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if (! $promotion) {
            throw self::fail('Kode voucher tidak ditemukan.');
        }

        if (! $promotion->is_active || $promotion->status === 'ended') {
            throw self::fail('Voucher sudah tidak aktif.');
        }

        $now = now();

        if ($promotion->starts_at && $promotion->starts_at->isFuture()) {
            throw self::fail('Voucher belum dapat digunakan.');
        }

        if ($promotion->ends_at && $promotion->ends_at->isPast()) {
            throw self::fail('Voucher sudah kadaluarsa.');
        }

        if ($promotion->usage_limit !== null && $promotion->used_count >= $promotion->usage_limit) {
            throw self::fail('Voucher sudah mencapai batas penggunaan.');
        }

        $subtotal = self::eligibleSubtotal($lines, $promotion);

        if ($subtotal <= 0) {
            throw self::fail('Voucher tidak berlaku untuk produk di keranjang.');
        }

        if ($promotion->min_purchase > 0 && $subtotal < $promotion->min_purchase) {
            throw self::fail('Minimum belanja voucher belum terpenuhi.');
        }

        $discount = self::discountFor($promotion, $subtotal);

        if ($discount <= 0) {
            throw self::fail('Voucher tidak memiliki nilai diskon.');
        }

        return ['promotion' => $promotion, 'discount' => $discount];
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lines
     */
    public static function eligibleSubtotal(array $lines, Promotion $promotion): int
    {
        $targets = self::targets($promotion);

        $total = 0;

        foreach ($lines as $line) {
            $product = $line['product'];

            if (! self::appliesToProduct($promotion, $product, $targets)) {
                continue;
            }

            $total += (int) $product->price * (int) $line['qty'];
        }

        return $total;
    }

    public static function discountFor(Promotion $promotion, int $subtotal): int
    {
        $value = (int) $promotion->discount_value;

        $discount = $promotion->discount_type === 'fixed'
            ? $value
            : (int) floor($subtotal * $value / 100);

        if ($promotion->max_discount !== null && $promotion->max_discount > 0) {
            $discount = min($discount, (int) $promotion->max_discount);
        }

        return max(0, min($discount, $subtotal));
    }

    /**
     * @return list<string>
     */
    private static function targets(Promotion $promotion): array
    {
        return collect(explode(',', (string) $promotion->applies_value))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $targets
     */
    private static function appliesToProduct(Promotion $promotion, Product $product, array $targets): bool
    {
        return match ($promotion->applies_to) {
            'category' => $targets !== [] && in_array((string) $product->category_id, $targets, true),
            'product' => $targets !== [] && in_array((string) $product->id, $targets, true),
            default => true,
        };
    }

    private static function fail(string $message): ValidationException
    {
        return ValidationException::withMessages(['voucher' => $message]);
    }
}
