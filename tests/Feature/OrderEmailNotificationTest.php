<?php

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Models\PaymentConfirmation;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');
    Mail::fake();

    Setting::create([
        'store_name' => 'ArafahGift',
        'email' => 'admin@arafagift.test',
        'payment_window_hours' => 24,
    ]);

    PaymentMethod::firstOrCreate(
        ['code' => 'transfer'],
        ['name' => 'Transfer bank', 'is_active' => true, 'sort_order' => 20],
    );

    $this->admin = User::factory()->create(['is_admin' => true]);
});

function emailTestOrder(array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'customer_email' => 'budi@example.test',
        'tracking_token' => str_repeat('e', 32),
        'payment_method_id' => PaymentMethod::where('code', 'transfer')->firstOrFail()->id,
        'payment_status' => 'unpaid',
        'payment_due_at' => now()->addHours(24),
    ], $overrides));
}

function emailTestProof(Order $order): PaymentConfirmation
{
    test()->post(route('order.track.proof', $order->tracking_token), [
        'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        'amount' => 150000,
        'sender_name' => 'Budi Santoso',
    ])->assertSessionHasNoErrors();

    return PaymentConfirmation::latest('id')->firstOrFail();
}

it('emails the customer and admin when an order is placed', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $this->postJson('/checkout', [
        'fulfillment_type' => 'delivery',
        'payment_method' => 'transfer',
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'email' => 'budi@example.test',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Makassar',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ])->assertOk();

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'placed'
        && $mail->hasTo('budi@example.test'));

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'placed-admin'
        && $mail->hasTo('admin@arafagift.test'));
});

it('still notifies the admin when the customer leaves the email empty', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 10]);

    $this->postJson('/checkout', [
        'fulfillment_type' => 'delivery',
        'payment_method' => 'transfer',
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'email' => null,
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Makassar',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ])->assertOk();

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'placed-admin'
        && $mail->hasTo('admin@arafagift.test'));

    Mail::assertNotSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'placed');
});

it('emails the admin when a customer uploads a payment proof', function () {
    $order = emailTestOrder();

    emailTestProof($order);

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'proof-submitted'
        && $mail->hasTo('admin@arafagift.test')
        && $mail->extra['amount'] === 150000);
});

it('emails the customer when the payment is verified', function () {
    $order = emailTestOrder();
    $confirmation = emailTestProof($order);
    Mail::fake();

    $this->actingAs($this->admin)->post(
        route('admin.order.payment.verify', [$order, $confirmation]),
        ['payment_reference' => 'BSI-123'],
    )->assertSessionHasNoErrors();

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'verified'
        && $mail->hasTo('budi@example.test'));
});

it('emails the customer the rejection reason', function () {
    $order = emailTestOrder();
    $confirmation = emailTestProof($order);
    Mail::fake();

    $this->actingAs($this->admin)->post(
        route('admin.order.payment.reject', [$order, $confirmation]),
        ['rejection_reason' => 'Nominal transfer tidak sesuai.'],
    )->assertSessionHasNoErrors();

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'rejected'
        && $mail->hasTo('budi@example.test')
        && $mail->extra['reason'] === 'Nominal transfer tidak sesuai.');
});

it('emails the customer when an unpaid order expires', function () {
    $order = emailTestOrder(['payment_due_at' => now()->subHour()]);

    $this->artisan('orders:expire-unpaid')->assertExitCode(0);

    expect($order->fresh()->status)->toBe('cancelled');

    Mail::assertSent(OrderNotificationMail::class, fn (OrderNotificationMail $mail) => $mail->template === 'expired'
        && $mail->hasTo('budi@example.test'));
});
