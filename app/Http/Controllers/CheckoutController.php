<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal' => ['nullable', 'string', 'max:10'],
            'giftMessage' => ['nullable', 'string', 'max:180'],
            'hideInvoice' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $ids = array_column($validated['items'], 'id');
        $products = Product::whereIn('id', $ids)->get()->keyBy('id');

        $orderNumber = 'AGF-' . str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        while (Order::where('order_number', $orderNumber)->exists()) {
            $orderNumber = 'AGF-' . str_pad((string) mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        }

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $request->user()?->id,
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'] ?? '',
            'customer_phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal'] ?? null,
            'gift_message' => $validated['giftMessage'] ?? null,
            'hide_invoice' => $validated['hideInvoice'] ?? true,
            'status' => 'pending',
            'channel' => 'Website',
            'subtotal' => $validated['subtotal'],
            'total' => $validated['subtotal'],
        ]);

        foreach ($validated['items'] as $item) {
            $product = $products[$item['id']] ?? null;
            $order->items()->create([
                'product_id' => $item['id'],
                'name' => $product->name ?? 'Produk',
                'sku' => $product->sku ?? '-',
                'art' => $product->art ?? '-',
                'qty' => $item['qty'],
                'price' => $item['price'],
            ]);
        }

        return response()->json([
            'order_number' => $orderNumber,
        ]);
    }
}
