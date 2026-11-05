<?php

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Cache::flush();
    Mail::fake();
    Setting::create(['store_name' => 'ArafahGift', 'email' => 'admin@arafagift.test']);
});

function cancelTestOrder(array $overrides = []): Order
{
    $product = Product::factory()->create(['stock' => 10]);
    $order = Order::factory()->create(array_merge([
        'customer_email' => 'budi@example.test',
        'tracking_token' => fake()->unique()->regexify('[A-Za-z0-9]{32}'),
        'payment_status' => 'unpaid',
        'payment_method_id' => PaymentMethod::firstOrCreate(['code' => 'transfer'], ['name' => 'Transfer', 'is_active' => true, 'sort_order' => 1])->id,
    ], $overrides));
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'qty' => 3]);
    OrderStock::deduct($order);

    return $order;
}

it('lets a guest cancel an unpaid order via the tracking token and restores stock', function () {
    $order = cancelTestOrder();
    $product = $order->items()->first()->product;
    expect($product->fresh()->stock)->toBe(7);

    $this->post(route('order.track.cancel', $order->tracking_token))->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->stock)->toBe(10);
    $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'type' => 'order_cancelled']);
    Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->template === 'cancelled');
});

it('lets the account owner cancel but not another user', function () {
    $owner = User::factory()->create();
    $order = cancelTestOrder(['user_id' => $owner->id]);

    $this->actingAs(User::factory()->create())
        ->post(route('account.order.cancel', $order))->assertForbidden();

    $this->actingAs($owner)->post(route('account.order.cancel', $order))->assertSessionHasNoErrors();
    expect($order->fresh()->status)->toBe('cancelled');
});

it('refuses to cancel paid or processed orders', function () {
    $paid = cancelTestOrder(['payment_status' => 'paid']);
    $processing = cancelTestOrder(['status' => 'processing']);

    foreach ([$paid, $processing] as $order) {
        $this->post(route('order.track.cancel', $order->tracking_token))->assertSessionHasErrors('cancel');
        expect($order->fresh()->status)->not->toBe('cancelled');
    }
});

it('does not restore stock twice when cancelled again', function () {
    $order = cancelTestOrder();
    $this->post(route('order.track.cancel', $order->tracking_token));
    $this->post(route('order.track.cancel', $order->tracking_token))->assertSessionHasErrors('cancel');

    expect($order->items()->first()->product->fresh()->stock)->toBe(10);
});
