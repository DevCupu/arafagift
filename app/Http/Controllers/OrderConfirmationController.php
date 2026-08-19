<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class OrderConfirmationController extends Controller
{
    public function show(Order $order): Response
    {
        $order->load(['paymentMethod', 'shippingMethod']);

        return Inertia::render('shop/OrderDonePage', [
            'order' => [
                'orderNumber' => $order->order_number,
                'paymentMethodName' => $order->paymentMethod->name,
                'shippingEta' => $order->shippingMethod->eta,
            ],
        ]);
    }
}
