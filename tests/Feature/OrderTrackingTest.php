<?php

use App\Models\Order;

it('finds an order when the order number and whatsapp number match', function () {
    $order = Order::factory()->create(['customer_phone' => '081234567890']);

    $this->get(route('order.track', ['order_number' => $order->order_number, 'phone' => '081234567890']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shop/TrackOrderPage')
            ->where('order.id', $order->order_number));
});

it('does not leak order data when the whatsapp number does not match', function () {
    $order = Order::factory()->create(['customer_phone' => '081234567890']);

    $this->get(route('order.track', ['order_number' => $order->order_number, 'phone' => '089999999999']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shop/TrackOrderPage')
            ->where('order', null)
            ->where('searched', true));
});
