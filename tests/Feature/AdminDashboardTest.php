<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('renders the dashboard with sales aggregates from real orders', function () {
    $product = Product::factory()->create(['stock' => 2, 'low_stock_threshold' => 10]);
    $order = Order::factory()->create(['status' => 'paid', 'total' => 500000]);
    OrderItem::factory()->for($order)->for($product)->create(['qty' => 3, 'price' => 100000]);

    $response = $this->actingAs($this->admin)->get(route('admin'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/DashboardPage')
        ->has('stats', 4)
        ->has('lowStockProducts')
    );
});

it('renders the dashboard even with no orders at all', function () {
    $this->actingAs($this->admin)->get(route('admin'))->assertOk();
});

it('renders the reports page with sales aggregates', function () {
    Order::factory()->create(['status' => 'paid', 'total' => 300000]);

    $this->actingAs($this->admin)->get(route('admin.reports'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/ReportsPage')->has('stats', 4));
});

it('blocks non-admin users from the dashboard and reports', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.reports'))->assertForbidden();
});
