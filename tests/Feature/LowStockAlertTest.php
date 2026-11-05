<?php

use App\Mail\LowStockAlertMail;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Inventory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Cache::flush();
    Mail::fake();
    Setting::create(['store_name' => 'ArafahGift', 'email' => 'admin@arafagift.test']);
});

it('emails admin about low-stock products only once per low period', function () {
    $low = Product::factory()->create(['status' => 'active', 'stock' => 3, 'low_stock_threshold' => 10]);
    Product::factory()->create(['status' => 'active', 'stock' => 50, 'low_stock_threshold' => 10]);
    Product::factory()->create(['status' => 'draft', 'stock' => 1, 'low_stock_threshold' => 10]);

    $this->artisan('inventory:low-stock-alert')->assertSuccessful();

    Mail::assertSent(LowStockAlertMail::class, fn ($mail) => $mail->products->count() === 1 && $mail->products->first()->is($low));
    expect($low->fresh()->low_stock_notified_at)->not->toBeNull();

    $this->artisan('inventory:low-stock-alert')->assertSuccessful();
    Mail::assertSent(LowStockAlertMail::class, 1);
});

it('re-arms the alert after stock recovers above the threshold', function () {
    $product = Product::factory()->create(['status' => 'active', 'stock' => 3, 'low_stock_threshold' => 10, 'low_stock_notified_at' => now()]);

    Inventory::lock($product, fn ($locked) => Inventory::apply($locked, 'restock', 20));

    expect($product->fresh()->low_stock_notified_at)->toBeNull();
});

it('keeps the flag while stock is still low', function () {
    $product = Product::factory()->create(['status' => 'active', 'stock' => 3, 'low_stock_threshold' => 10, 'low_stock_notified_at' => now()]);

    Inventory::lock($product, fn ($locked) => Inventory::apply($locked, 'restock', 2));

    expect($product->fresh()->low_stock_notified_at)->not->toBeNull();
});

it('renders the low-stock email', function () {
    $product = Product::factory()->create(['stock' => 2, 'low_stock_threshold' => 10]);
    $mail = new LowStockAlertMail(Product::whereKey($product->id)->get());

    $mail->assertSeeInHtml($product->name);
});
