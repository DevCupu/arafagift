<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Seo\PageSeo;
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
            return Inertia::render('shop/TrackOrderPage', [
                'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
            ]);
        }

        $order = Order::where('order_number', trim($validated['order_number']))->first();
        $digits = fn (string $value) => substr(preg_replace('/\D/', '', $value), -9);

        $found = $order && $digits($order->customer_phone) === $digits($validated['phone']);

        return Inertia::render('shop/TrackOrderPage', [
            'order' => $found ? $order->load(['items', 'paymentMethod'])->toCatalog() : null,
            'searched' => true,
            'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
        ]);
    }

    /**
     * Link tracking yang dikirim ke WhatsApp: token acak, bukan nomor pesanan,
     * supaya orang lain tidak bisa menebak pesanan pelanggan.
     */
    public function showByToken(string $token): Response
    {
        $order = Order::where('tracking_token', $token)
            ->with(['items', 'paymentMethod'])
            ->first();

        abort_unless($order, 404);

        return Inertia::render('shop/TrackOrderPage', [
            'order' => $order->toCatalog(),
            'searched' => true,
            'seoHead' => PageSeo::noindex('Lacak Pesanan', follow: true)->toArray(),
        ]);
    }
}
