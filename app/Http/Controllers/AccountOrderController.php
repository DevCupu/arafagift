<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
}
