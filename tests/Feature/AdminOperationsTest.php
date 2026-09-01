<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin update an order status and tracking number', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), [
        'status' => 'shipped',
        'awb' => 'SC1234567890',
    ])->assertSessionHasNoErrors();

    expect($order->fresh())
        ->status->toBe('shipped')
        ->awb->toBe('SC1234567890');
});

it('recalculates the order total when an admin sets the shipping cost', function () {
    $order = Order::factory()->create(['status' => 'pending', 'subtotal' => 100000, 'shipping_cost' => 0]);

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), [
        'status' => 'pending',
        'shippingCost' => 15000,
        'adminNote' => 'Kirim via JNE reguler',
    ])->assertSessionHasNoErrors();

    $fresh = $order->fresh();
    expect((int) $fresh->shipping_cost)->toBe(15000);
    expect((int) $fresh->total)->toBe(115000);
    expect($fresh->admin_note)->toBe('Kirim via JNE reguler');
});

it('rejects an invalid order status', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.order.update', $order), [
        'status' => 'not-a-real-status',
    ])->assertSessionHasErrors('status');
});

it('blocks deleting a category that still has products', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('category');

    expect(Category::find($category->id))->not->toBeNull();
});

it('lets an admin delete an empty category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasNoErrors();

    expect(Category::find($category->id))->toBeNull();
});

it('blocks non-admin users from the customers list', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.customers'))->assertForbidden();
});

it('redirects guests away from account orders to login', function () {
    $this->get(route('account.orders'))->assertRedirect(route('login'));
});

it('only shows a customer their own orders', function () {
    $customer = User::factory()->create(['is_admin' => false]);
    $mine = Order::factory()->create(['user_id' => $customer->id]);
    $someoneElses = Order::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->actingAs($customer)->get(route('account.order', $someoneElses))->assertForbidden();
    $this->actingAs($customer)->get(route('account.order', $mine))->assertOk();
});
