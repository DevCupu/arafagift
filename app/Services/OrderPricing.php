<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
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
}
