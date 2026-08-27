<?php

use App\Models\Order;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('shows a customer detail page with their own order history', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $mine = Order::factory()->create(['user_id' => $customer->id, 'total' => 200000]);
    Order::factory()->create(['user_id' => User::factory()->create()->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.customer', $customer));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/CustomerDetailPage')
        ->where('customer.id', $customer->id)
        ->has('orders', 1)
        ->where('orders.0.id', $mine->order_number)
    );
});

it('returns 404 for an admin user viewed as a customer', function () {
    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($this->admin)->get(route('admin.customer', $otherAdmin))->assertNotFound();
});
