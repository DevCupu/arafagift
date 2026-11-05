<?php

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function checkoutPayload(array $overrides = []): array
{
    return array_merge([
        'fulfillment_type' => 'delivery',
        'payment_method' => 'transfer',
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'email' => 'budi@example.test',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'destination_id' => '17473',
        'courier' => 'jne',
        'service' => 'REG',
        'items' => [],
    ], $overrides);
}

beforeEach(function () {
    Cache::flush();
    PaymentMethod::create(['code' => 'transfer', 'name' => 'Transfer bank', 'is_active' => true, 'sort_order' => 10]);
});

it('prices the order from the database, ignoring any price sent by the client', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $response = $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 2, 'price' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect((int) $order->subtotal)->toBe(200000);
    expect((int) $order->items->first()->price)->toBe(100000);
    expect($response->json('subtotal'))->toBe(200000)
        ->and($response->json('total'))->toBe(200000);
});

it('rejects checkout when the requested quantity exceeds available stock', function () {
    $product = Product::factory()->create(['stock' => 1]);

    $this->actingAs(User::factory()->create())->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 5]],
    ]))->assertStatus(422)->assertJsonValidationErrors(["items.{$product->id}"]);

    expect(Order::count())->toBe(0);
});

it('lets guests place an order without logging in', function () {
    $this->get('/checkout')->assertStatus(200);

    $product = Product::factory()->create(['stock' => 5]);
    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    expect(Order::first()->user_id)->toBeNull();
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

it('lets a customer pick up at the store without an address or shipping cost', function () {
    $product = Product::factory()->create(['stock' => 10, 'price' => 50000]);

    $this->postJson('/checkout', checkoutPayload([
        'fulfillment_type' => 'pickup',
        'address' => null,
        'city' => null,
        'destination_id' => null,
        'courier' => null,
        'service' => null,
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect($order->fulfillment_type)->toBe('pickup');
    expect((int) $order->shipping_cost)->toBe(0);
    expect($order->shipping_courier)->toBeNull();
    expect((int) $order->total)->toBe(50000);
});

it('stores the payment method chosen at checkout', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $payment = PaymentMethod::where('code', 'transfer')->first();

    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect($order->payment_method_id)->toBe($payment->id);
    expect($order->payment_status)->toBe('unpaid');
    expect($order->tracking_token)->not->toBeEmpty();
});

it('rejects an inactive payment method', function () {
    PaymentMethod::where('code', 'cod')->update(['is_active' => false]);
    $product = Product::factory()->create(['stock' => 10]);

    $this->postJson('/checkout', checkoutPayload([
        'payment_method' => 'cod',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertStatus(422)->assertJsonValidationErrors(['payment_method']);

    expect(Order::count())->toBe(0);
});

it('rejects new orders while the store is closed', function () {
    Setting::create(['store_name' => 'Toko', 'store_status' => 'holiday', 'closed_message' => 'Libur lebaran']);
    $product = Product::factory()->create(['stock' => 10]);

    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertStatus(422)->assertJsonValidationErrors(['checkout']);

    expect(Order::count())->toBe(0);
});

it('enforces the configured minimum order amount', function () {
    Setting::create(['store_name' => 'Toko', 'min_order_amount' => 100000]);
    $product = Product::factory()->create(['stock' => 10, 'price' => 50000]);

    $this->postJson('/checkout', checkoutPayload([
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertStatus(422)->assertJsonValidationErrors(['items']);

    expect(Order::count())->toBe(0);
});

it('applies a voucher discount and records its usage', function () {
    Setting::create(['store_name' => 'Toko']);
    $promotion = Promotion::create([
        'code' => 'HEMAT10',
        'type' => 'Diskon persen',
        'usage' => '1x per pelanggan',
        'period' => 'November',
        'status' => 'active',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['stock' => 10, 'price' => 100000]);

    $this->postJson('/checkout', checkoutPayload([
        'voucher_code' => 'hemat10',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ]))->assertOk();

    $order = Order::latest()->first();
    expect($order->voucher_code)->toBe('HEMAT10');
    expect((int) $order->discount_total)->toBe(10000);
    expect((int) $order->total)->toBe(90000);
    expect($promotion->fresh()->used_count)->toBe(1);
});
