<?php

use App\Models\Order;
use App\Models\Product;

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'items' => [],
    ], $overrides);
}

it('prices the order from the database, ignoring any price sent by the client', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 2, 'price' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->subtotal)->toBe(200000);
    expect((int) $order->items->first()->price)->toBe(100000);
});

it('rejects checkout when the requested quantity exceeds available stock', function () {
    $product = Product::factory()->create(['stock' => 1]);

    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 5]],
    ]))->assertStatus(422)->assertJsonValidationErrors(["items.{$product->id}"]);

    expect(Order::count())->toBe(0);
});
