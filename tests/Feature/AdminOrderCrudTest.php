<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin create a manual order priced from the database', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $this->actingAs($this->admin)->post(route('admin.orders.store'), [
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'status' => 'pending',
        'shippingCost' => 15000,
        'items' => [['id' => $product->id, 'qty' => 2, 'price' => 1]],
    ])->assertSessionHasNoErrors();

    $order = Order::latest()->first();
    expect($order->channel)->toBe('Admin');
    expect((int) $order->subtotal)->toBe(200000);
    expect((int) $order->total)->toBe(215000);
    expect((int) $order->items->first()->price)->toBe(100000);
    expect($product->fresh()->stock)->toBe(10); // pending: belum potong stok
});

it('deducts stock immediately when a manual order is created already processing', function () {
    $product = Product::factory()->create(['price' => 50000, 'stock' => 10]);

    $this->actingAs($this->admin)->post(route('admin.orders.store'), [
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'status' => 'processing',
        'items' => [['id' => $product->id, 'qty' => 3]],
    ])->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(7);
});

it('rejects a manual order that exceeds available stock', function () {
    $product = Product::factory()->create(['stock' => 1]);

    $this->actingAs($this->admin)->post(route('admin.orders.store'), [
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'status' => 'pending',
        'items' => [['id' => $product->id, 'qty' => 5]],
    ])->assertSessionHasErrors(["items.{$product->id}"]);

    expect(Order::count())->toBe(0);
});

it('soft-deletes an order and restores stock that had been deducted', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::factory()->create(['status' => 'processing']);
    $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'art' => $product->art, 'qty' => 4, 'price' => $product->price]);
    \App\Services\Inventory::lock($product, fn ($p) => \App\Services\Inventory::apply($p, 'sale', -4, $this->admin->id, null, 'test', $order->order_number));

    expect($product->fresh()->stock)->toBe(6);

    $this->actingAs($this->admin)->delete(route('admin.order.destroy', $order))->assertSessionHasNoErrors();

    expect(Order::find($order->id))->toBeNull(); // excluded by default scope
    expect(Order::withTrashed()->find($order->id))->not->toBeNull();
    expect($product->fresh()->stock)->toBe(10);
});

it('does not show a deleted order to customers tracking it', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    $this->actingAs($this->admin)->delete(route('admin.order.destroy', $order));

    $this->get(route('admin.orders'))->assertOk();
    expect(Order::query()->count())->toBe(0);
});

it('lets an admin restore a deleted order without crashing, even for an already-shipped order', function () {
    // Stock movements are unique per (product, order_number, type), so a restored order's stock
    // can't be re-deducted with a second "sale" row — restoring must stay a safe no-op on stock.
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::factory()->create(['status' => 'shipped']);
    $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'art' => $product->art, 'qty' => 2, 'price' => $product->price]);
    \App\Services\Inventory::lock($product, fn ($p) => \App\Services\Inventory::apply($p, 'sale', -2, $this->admin->id, null, 'test', $order->order_number));

    $this->actingAs($this->admin)->delete(route('admin.order.destroy', $order));
    expect($product->fresh()->stock)->toBe(10);

    $this->actingAs($this->admin)->patch(route('admin.order.restore', $order->order_number))->assertSessionHasNoErrors();

    expect(Order::find($order->id))->not->toBeNull();
    expect($product->fresh()->stock)->toBe(10);
});

it('blocks non-admin users from creating or deleting orders', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $order = Order::factory()->create();

    $this->actingAs($user)->get(route('admin.orders.new'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.order.destroy', $order))->assertForbidden();
});
