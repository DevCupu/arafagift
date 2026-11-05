<?php

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\OrderLifecycle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Cache::flush();
    Mail::fake();
    Setting::create(['store_name' => 'ArafahGift', 'email' => 'admin@arafagift.test']);
});

function reminderOrder(array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'customer_email' => 'budi@example.test',
        'tracking_token' => fake()->unique()->regexify('[A-Za-z0-9]{32}'),
        'payment_status' => 'unpaid',
        'payment_due_at' => now()->addHours(2),
        'payment_method_id' => PaymentMethod::firstOrCreate(['code' => 'transfer'], ['name' => 'Transfer', 'is_active' => true, 'sort_order' => 1])->id,
    ], $overrides));
}

it('sends one reminder for orders close to their deadline', function () {
    $due = reminderOrder();
    reminderOrder(['payment_due_at' => now()->addHours(10)]);
    reminderOrder(['payment_status' => 'paid']);
    reminderOrder(['customer_email' => '']);

    $this->artisan('orders:remind-payment')->assertSuccessful();

    Mail::assertSent(OrderNotificationMail::class, 1);
    Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->template === 'reminder' && $mail->order->is($due));
    expect($due->fresh()->payment_reminder_sent_at)->not->toBeNull();

    $this->artisan('orders:remind-payment')->assertSuccessful();
    Mail::assertSent(OrderNotificationMail::class, 1);
});

it('skips COD orders', function () {
    $cod = PaymentMethod::firstOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true, 'sort_order' => 2]);
    reminderOrder(['payment_method_id' => $cod->id]);

    expect(OrderLifecycle::sendPaymentReminders())->toBe(0);
});

it('resets the reminder when the payment window is extended', function () {
    $order = reminderOrder(['payment_reminder_sent_at' => now()]);

    OrderLifecycle::extendPaymentWindow($order);

    expect($order->fresh()->payment_reminder_sent_at)->toBeNull();
});
