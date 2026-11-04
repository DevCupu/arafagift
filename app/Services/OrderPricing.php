<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderPricing
{
    public static function nextOrderNumber(): string
    {
        do {
            $orderNumber = 'AGF-'.str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while (Order::withTrashed()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public static function trackingToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Order::withTrashed()->where('tracking_token', $token)->exists());

        return $token;
    }

    /**
     * Order lama (atau yang dibuat sebelum migrasi token) tetap bisa dilacak.
     */
    public static function ensureTrackingToken(Order $order): string
    {
        if (is_string($order->tracking_token) && $order->tracking_token !== '') {
            return $order->tracking_token;
        }

        $token = self::trackingToken();
        $order->forceFill(['tracking_token' => $token])->saveQuietly();

        return $token;
    }

    /**
     * Price line items from the database (never trust client-sent prices), and validate stock.
     *
     * @param  array<int, array{id: int, qty: int}>  $items
     * @return array{subtotal: int|float, lines: array<int, array{product: Product, qty: int}>}
     *
     * @throws ValidationException
     */
    public static function priceItems(array $items): array
    {
        $ids = array_column($items, 'id');
        $products = Product::whereIn('id', $ids)->get()->keyBy('id');

        $shortages = [];
        $subtotal = 0;
        $lines = [];
        foreach ($items as $item) {
            $product = $products[$item['id']];
            if ($item['qty'] > $product->stock) {
                $shortages["items.{$item['id']}"] = "Stok {$product->name} tinggal {$product->stock}.";
            }
            $subtotal += $product->price * $item['qty'];
            $lines[] = ['product' => $product, 'qty' => $item['qty']];
        }

        if ($shortages) {
            throw ValidationException::withMessages($shortages);
        }

        return ['subtotal' => $subtotal, 'lines' => $lines];
    }

    /**
     * @param  array<int, array{product: Product, qty: int}>  $lines
     */
    public static function totalWeightGrams(array $lines): int
    {
        return array_sum(array_map(fn (array $line) => $line['product']->weight * $line['qty'], $lines));
    }
}
