<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shop/CheckoutPage', [
            'shippingMethods' => ShippingMethod::orderBy('id')->get()->map->toCatalog()->values(),
            'paymentMethods' => PaymentMethod::orderBy('id')->get()->map->toCatalog()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string'],
            'address' => ['required', 'string', 'min:8'],
            'city' => ['required', 'string'],
            'province' => ['nullable', 'string'],
            'postal' => ['required', 'regex:/^\d{5}$/'],
            'shipping' => ['required', 'exists:shipping_methods,code'],
            'payment' => ['required', 'exists:payment_methods,code'],
            'giftMessage' => ['nullable', 'string', 'max:180'],
            'hideInvoice' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $shippingMethod = ShippingMethod::where('code', $validated['shipping'])->firstOrFail();
        $paymentMethod = PaymentMethod::where('code', $validated['payment'])->firstOrFail();
        $products = Product::whereIn('id', collect($validated['items'])->pluck('product_id'))->get()->keyBy('id');

        $subtotal = collect($validated['items'])->sum(fn ($item) => $products[$item['product_id']]->price * $item['qty']);
        $total = $subtotal + $shippingMethod->price;

        $order = DB::transaction(function () use ($request, $validated, $shippingMethod, $paymentMethod, $products, $subtotal, $total) {
            $order = Order::create([
                'order_number' => 'PENDING',
                'user_id' => $request->user()?->id,
                'customer_name' => $validated['name'],
                'customer_email' => $validated['email'],
                'customer_phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'] ?? null,
                'postal_code' => $validated['postal'],
                'shipping_method_id' => $shippingMethod->id,
                'shipping_cost' => $shippingMethod->price,
                'payment_method_id' => $paymentMethod->id,
                'gift_message' => $validated['giftMessage'] ?? null,
                'hide_invoice' => $validated['hideInvoice'] ?? true,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            $order->forceFill(['order_number' => 'AGF-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)])->save();

            foreach ($validated['items'] as $item) {
                $product = $products[$item['product_id']];

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'art' => $product->art,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                ]);

                $product->decrement('stock', $item['qty']);
            }

            return $order;
        });

        return redirect()->route('checkout.done', $order->order_number);
    }
}
