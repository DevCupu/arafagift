<?php

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentConfirmation;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Inventory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('local');

    $this->admin = User::factory()->create(['is_admin' => true]);

    // Migrasi checkout sudah menyisipkan 'cod', jadi jangan create mentah —
    // pakai firstOrCreate agar test tidak bentrok dengan data hasil migrasi.
    $this->transfer = PaymentMethod::firstOrCreate(
        ['code' => 'transfer'],
        ['name' => 'Transfer bank', 'is_active' => true, 'sort_order' => 10],
    );
    $this->cod = PaymentMethod::firstOrCreate(
        ['code' => 'cod'],
        ['name' => 'Bayar di Tempat', 'is_active' => true, 'sort_order' => 20],
    );
});

function paymentProofOrder(array $overrides = []): Order
{
    return Order::factory()->create(array_merge([
        'tracking_token' => str_repeat('t', 32),
        'payment_method_id' => PaymentMethod::where('code', 'transfer')->firstOrFail()->id,
        'payment_status' => 'unpaid',
        'payment_due_at' => now()->addHours(24),
    ], $overrides));
}

function uploadPaymentProof(Order $order, array $overrides = []): PaymentConfirmation
{
    test()->post(route('order.track.proof', $order->tracking_token), array_merge([
        'proof' => UploadedFile::fake()->create('bukti-transfer.jpg', 200, 'image/jpeg'),
        'amount' => 150000,
        'sender_name' => 'Budi Santoso',
        'note' => 'Transfer dari rekening BSI',
    ], $overrides))->assertSessionHasNoErrors();

    return PaymentConfirmation::latest('id')->firstOrFail();
}

it('lets a guest upload a payment proof through the tracking token', function () {
    $order = paymentProofOrder();

    $confirmation = uploadPaymentProof($order);

    expect($order->fresh()->payment_status)->toBe('waiting_confirmation')
        ->and($confirmation->status)->toBe(PaymentConfirmation::STATUS_SUBMITTED)
        ->and((int) $confirmation->amount)->toBe(150000)
        ->and($confirmation->sender_name)->toBe('Budi Santoso');

    Storage::disk('local')->assertExists($confirmation->path);

    expect(OrderEvent::where('type', OrderEvent::TYPE_PROOF_SUBMITTED)->count())->toBe(1);
});

it('rejects a proof file with an unsupported format', function () {
    $order = paymentProofOrder();

    $this->post(route('order.track.proof', $order->tracking_token), [
        'proof' => UploadedFile::fake()->create('bukti.exe', 10, 'application/octet-stream'),
    ])->assertSessionHasErrors('proof');

    expect(PaymentConfirmation::count())->toBe(0)
        ->and($order->fresh()->payment_status)->toBe('unpaid');
});

it('does not accept a proof for an order that is already paid', function () {
    $order = paymentProofOrder(['payment_status' => 'paid']);

    $this->post(route('order.track.proof', $order->tracking_token), [
        'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
    ])->assertSessionHasErrors('proof');

    expect(PaymentConfirmation::count())->toBe(0);
});

it('does not ask for a payment proof on a COD order', function () {
    $order = paymentProofOrder([
        'payment_method_id' => $this->cod->id,
        'payment_due_at' => null,
    ]);

    $this->post(route('order.track.proof', $order->tracking_token), [
        'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
    ])->assertSessionHasErrors('proof');

    expect(PaymentConfirmation::count())->toBe(0);
});

it('lets an admin open a private payment proof but blocks regular customers', function () {
    $confirmation = uploadPaymentProof(paymentProofOrder());

    $this->actingAs($this->admin)
        ->get(route('admin.payment.proof', $confirmation))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.payment.proof', $confirmation))
        ->assertForbidden();
});

it('verifies a proof and marks the order as paid', function () {
    $order = paymentProofOrder();
    $confirmation = uploadPaymentProof($order);

    $this->actingAs($this->admin)->post(
        route('admin.order.payment.verify', [$order, $confirmation]),
        ['payment_reference' => 'BSI-123456'],
    )->assertSessionHasNoErrors();

    $order->refresh();
    $confirmation->refresh();

    expect($order->payment_status)->toBe('paid')
        ->and($order->payment_reference)->toBe('BSI-123456')
        ->and($order->payment_confirmed_at)->not->toBeNull()
        ->and($confirmation->status)->toBe(PaymentConfirmation::STATUS_VERIFIED)
        ->and($confirmation->reviewed_by)->toBe($this->admin->id);

    expect(OrderEvent::where('type', OrderEvent::TYPE_PAYMENT_VERIFIED)->exists())->toBeTrue();
});

it('rejects a proof with a reason and returns the order to unpaid', function () {
    $order = paymentProofOrder();
    $confirmation = uploadPaymentProof($order);

    $this->actingAs($this->admin)->post(
        route('admin.order.payment.reject', [$order, $confirmation]),
        ['rejection_reason' => 'Nominal transfer tidak sesuai total pesanan.'],
    )->assertSessionHasNoErrors();

    $order->refresh();
    $confirmation->refresh();

    expect($confirmation->status)->toBe(PaymentConfirmation::STATUS_REJECTED)
        ->and($confirmation->rejection_reason)->toBe('Nominal transfer tidak sesuai total pesanan.')
        ->and($order->payment_status)->toBe('unpaid');

    expect(OrderEvent::where('type', OrderEvent::TYPE_PAYMENT_REJECTED)->exists())->toBeTrue();
});

it('expires an unpaid order past its deadline and restores deducted stock', function () {
    $product = Product::factory()->create(['stock' => 10]);
    $order = paymentProofOrder(['payment_due_at' => now()->subHour()]);

    $order->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'sku' => $product->sku,
        'art' => $product->art,
        'qty' => 3,
        'price' => $product->price,
    ]);

    Inventory::lock($product, fn (Product $locked) => Inventory::apply(
        $locked,
        'sale',
        -3,
        null,
        null,
        'test',
        $order->order_number,
    ));

    expect($product->fresh()->stock)->toBe(7);

    $this->artisan('orders:expire-unpaid')->assertExitCode(0);

    expect($order->fresh()->status)->toBe('cancelled')
        ->and($product->fresh()->stock)->toBe(10)
        ->and(OrderEvent::where('type', OrderEvent::TYPE_ORDER_EXPIRED)->exists())->toBeTrue();
});

it('never expires a COD order automatically', function () {
    $order = paymentProofOrder([
        'payment_method_id' => $this->cod->id,
        'payment_due_at' => now()->subHour(),
    ]);

    $this->artisan('orders:expire-unpaid')->assertExitCode(0);

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('unpaid');
});

it('does not auto-expire an order whose proof is waiting for verification', function () {
    $order = paymentProofOrder([
        'payment_status' => 'waiting_confirmation',
        'payment_due_at' => now()->subHour(),
    ]);

    $this->artisan('orders:expire-unpaid')->assertExitCode(0);

    expect($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->payment_status)->toBe('waiting_confirmation');
});

it('gives a fresh deadline when a proof is rejected after the old deadline', function () {
    $order = paymentProofOrder(['payment_due_at' => now()->addHour()]);
    $confirmation = uploadPaymentProof($order);

    // Simulasikan bukti baru diperiksa admin setelah tenggat lama lewat.
    $order->update(['payment_due_at' => now()->subHour()]);

    $this->actingAs($this->admin)->post(
        route('admin.order.payment.reject', [$order, $confirmation]),
        ['rejection_reason' => 'Bukti buram, mohon unggah ulang.'],
    )->assertSessionHasNoErrors();

    $order->refresh();

    expect($order->payment_status)->toBe('unpaid')
        ->and($order->payment_due_at)->not->toBeNull()
        ->and($order->payment_due_at->isFuture())->toBeTrue();

    $this->artisan('orders:expire-unpaid')->assertExitCode(0);

    expect($order->fresh()->status)->toBe('pending');
});

it('sets a payment deadline from the store setting at checkout but not for COD', function () {
    Setting::create(['store_name' => 'Toko', 'payment_window_hours' => 48]);

    $product = Product::factory()->create(['stock' => 10, 'price' => 100000]);

    $payload = [
        'fulfillment_type' => 'delivery',
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'address' => 'Jl. Mawar No. 1',
        'city' => 'Jakarta',
        'items' => [['id' => $product->id, 'qty' => 1]],
    ];

    $this->postJson('/checkout', $payload + ['payment_method' => 'transfer'])->assertOk();
    $transferOrder = Order::latest('id')->firstOrFail();

    $this->postJson('/checkout', $payload + ['payment_method' => 'cod'])->assertOk();
    $codOrder = Order::latest('id')->firstOrFail();

    expect($transferOrder->payment_due_at)->not->toBeNull()
        ->and(now()->addHours(48)->diffInMinutes($transferOrder->payment_due_at))->toBeLessThan(2)
        ->and($codOrder->payment_due_at)->toBeNull();
});

it('saves the payment window setting from the admin panel', function () {
    Setting::create(['store_name' => 'Toko Awal']);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'Arafagift',
        'payment_window_hours' => 12,
    ])->assertSessionHasNoErrors();

    expect((int) Setting::first()->payment_window_hours)->toBe(12);
});
