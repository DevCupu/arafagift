<?php

use App\Models\Product;
use App\Models\Promotion;

function voucherPayload(int $productId, array $overrides = []): array
{
    return array_merge([
        'code' => 'HEMAT10',
        'items' => [['id' => $productId, 'qty' => 1]],
    ], $overrides);
}

function voucherPromotion(array $overrides = []): Promotion
{
    return Promotion::create(array_merge([
        'code' => 'HEMAT10',
        'type' => 'Diskon persen',
        'usage' => '1x',
        'period' => 'November',
        'status' => 'active',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'min_purchase' => 0,
        'is_active' => true,
    ], $overrides));
}

it('calculates a percent voucher discount', function () {
    voucherPromotion();
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id))
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('discount', 10000);
});

it('caps a percent discount with max_discount', function () {
    voucherPromotion(['discount_value' => 50, 'max_discount' => 15000]);
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id))
        ->assertOk()
        ->assertJsonPath('discount', 15000);
});

it('rejects a voucher whose minimum spend is not met', function () {
    voucherPromotion(['min_purchase' => 200000]);
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voucher']);
});

it('rejects an expired voucher', function () {
    voucherPromotion(['ends_at' => now()->subDay()]);
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voucher']);
});

it('rejects a voucher that has reached its usage limit', function () {
    voucherPromotion(['usage_limit' => 1, 'used_count' => 1]);
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voucher']);
});

it('rejects an unknown voucher code', function () {
    $product = Product::factory()->create(['price' => 100000, 'stock' => 5]);

    $this->postJson('/checkout/voucher', voucherPayload($product->id, ['code' => 'TIDAKADA']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['voucher']);
});
