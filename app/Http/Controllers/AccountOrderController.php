<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountOrderController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('account/OrdersPage', [
            'orders' => $request->user()->orders()->with(['items', 'shippingMethod', 'paymentMethod'])->latest()->get()->map->toCatalog()->values(),
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return Inertia::render('account/OrderDetailPage', [
            'order' => $order->load(['items', 'shippingMethod', 'paymentMethod'])->toCatalog(),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return OrderLifecycle::cancelByCustomer($order, $request->user())
            ? back()->with('success', 'Pesanan dibatalkan.')
            : back()->withErrors(['cancel' => 'Pesanan ini sudah diproses atau dibayar, sehingga tidak bisa dibatalkan sendiri. Hubungi kami via WhatsApp.']);
    }
}
