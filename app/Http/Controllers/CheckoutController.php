<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPricing;
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
            'note' => ['nullable', 'string', 'max:500'],
            'hideInvoice' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        // Harga & stok dihitung dari database, bukan dari input klien, supaya tidak bisa dimanipulasi.
        $priced = OrderPricing::priceItems($validated['items']);

        $order = Order::create([
            'order_number' => OrderPricing::nextOrderNumber(),
            'user_id' => $request->user()?->id,
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'] ?? '',
            'customer_phone' => $validated['phone'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'] ?? null,
            'postal_code' => $validated['postal'] ?? null,
            'gift_message' => $validated['giftMessage'] ?? null,
            'note' => $validated['note'] ?? null,
            'hide_invoice' => $validated['hideInvoice'] ?? true,
            'status' => 'pending',
            'channel' => 'Website',
            'subtotal' => $priced['subtotal'],
            'total' => $priced['subtotal'],
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

        return response()->json($order->load('items')->toCatalog());
    }
}
