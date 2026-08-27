<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('only lists the current customer\'s wishlist products', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $mine = Product::factory()->create();
    $notMine = Product::factory()->create();
    $customer->wishlists()->create(['product_id' => $mine->id]);

    $response = $this->actingAs($customer)->get(route('account.wishlist'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('account/WishlistPage')
        ->has('products', 1)
        ->where('products.0.id', $mine->id)
    );
});

it('only lists the current customer\'s own orders, newest first', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $older = Order::factory()->create(['user_id' => $customer->id, 'created_at' => now()->subDay()]);
    $newer = Order::factory()->create(['user_id' => $customer->id, 'created_at' => now()]);
    Order::factory()->create(['user_id' => User::factory()->create()->id]);

    $response = $this->actingAs($customer)->get(route('account.orders'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('account/OrdersPage')
        ->has('orders', 2)
        ->where('orders.0.id', $newer->order_number)
        ->where('orders.1.id', $older->order_number)
    );
});
