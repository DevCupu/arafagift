<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'destination_id' => '17473',
        'courier' => 'jne',
        'service' => 'REG',
        'items' => [],
    ], $overrides);
}

it('prices the order from the database, ignoring any price sent by the client', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 2, 'price' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->subtotal)->toBe(200000);
    expect((int) $order->items->first()->price)->toBe(100000);
});

it('rejects checkout when the requested quantity exceeds available stock', function () {
    $product = Product::factory()->create(['stock' => 1]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 5]],
    ]))->assertStatus(422)->assertJsonValidationErrors(["items.{$product->id}"]);

    expect(Order::count())->toBe(0);
});

it('blocks checkout for guests, sending them to login', function () {
    $this->get('/checkout')->assertRedirect('/login');

    $product = Product::factory()->create(['stock' => 5]);
    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertStatus(401);
});

it('stores the shipping cost fetched from RajaOngkir, not any cost sent by the client', function () {
    Setting::create(['store_name' => 'Toko', 'origin_destination_id' => '4816']);
    Http::fake(['rajaongkir.komerce.id/*' => Http::response([
        'meta' => ['code' => 200, 'status' => 'success'],
        'data' => [
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 15000, 'etd' => '2 day'],
        ],
    ], 200)]);
    $product = Product::factory()->create(['stock' => 10, 'weight' => 500]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->shipping_cost)->toBe(15000);
    expect($order->shipping_courier)->toBe('jne');
    expect($order->shipping_service)->toBe('REG');
    expect((int) $order->total)->toBe((int) $order->subtotal + 15000);
});

it('forces shipping cost to zero for a free-shipping city even if the client picked a paid courier', function () {
    Setting::create(['store_name' => 'Toko', 'origin_destination_id' => '4816', 'free_shipping_cities' => 'Makassar']);
    Http::fake(['rajaongkir.komerce.id/*' => Http::response([
        'meta' => ['code' => 200, 'status' => 'success'],
        'data' => [
            ['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'description' => 'Reguler', 'cost' => 15000, 'etd' => '2 day'],
        ],
    ], 200)]);
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'city' => 'Makassar',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->shipping_cost)->toBe(0);
});

it('still creates the order with zero shipping cost when RajaOngkir is unreachable', function () {
    Setting::create(['store_name' => 'Toko', 'origin_destination_id' => '4816']);
    Http::fake(['rajaongkir.komerce.id/*' => fn () => throw new ConnectionException('timed out')]);
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->shipping_cost)->toBe(0);
    expect($order->shipping_courier)->toBeNull();
});
