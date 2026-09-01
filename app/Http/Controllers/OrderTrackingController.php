<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'order_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if (empty($validated['order_number']) || empty($validated['phone'])) {
            return Inertia::render('shop/TrackOrderPage');
        }

        $order = Order::where('order_number', trim($validated['order_number']))->first();
        $digits = fn (string $value) => substr(preg_replace('/\D/', '', $value), -9);

        $found = $order && $digits($order->customer_phone) === $digits($validated['phone']);

        return Inertia::render('shop/TrackOrderPage', [
            'order' => $found ? $order->load('items')->toCatalog() : null,
            'searched' => true,
        ]);
    }
}
