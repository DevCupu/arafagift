<?php

use App\Models\Promotion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin create a promotion code', function () {
    $this->actingAs($this->admin)->post(route('admin.promotions.store'), [
        'code' => 'PULANGHAJI',
        'type' => 'Potongan 10%',
        'usage' => '0 / 500',
        'period' => '1-31 Ags 2026',
        'status' => 'active',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'applies_to' => 'all',
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    expect(Promotion::where('code', 'PULANGHAJI')->exists())->toBeTrue();
});

it('rejects a duplicate promotion code', function () {
    Promotion::factory()->create(['code' => 'DOUBLE']);

    $this->actingAs($this->admin)->post(route('admin.promotions.store'), [
        'code' => 'DOUBLE',
        'type' => 'x',
        'usage' => '0 / 10',
        'period' => 'x',
        'status' => 'active',
        'discount_type' => 'percent',
        'discount_value' => 10,
        'applies_to' => 'all',
    ])->assertSessionHasErrors('code');
});

it('lets an admin update a promotion into a real voucher', function () {
    $promo = Promotion::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.promotions.update', $promo), [
        'code' => $promo->code,
        'type' => 'Potongan nominal',
        'usage' => '0 / 50',
        'period' => 'November',
        'status' => 'active',
        'discount_type' => 'fixed',
        'discount_value' => 25000,
        'min_purchase' => 100000,
        'applies_to' => 'all',
        'is_active' => true,
    ])->assertSessionHasNoErrors();

    $promo->refresh();
    expect($promo->discount_type)->toBe('fixed');
    expect($promo->discount_value)->toBe(25000);
    expect($promo->min_purchase)->toBe(100000);
});

it('lets an admin delete a promotion', function () {
    $promo = Promotion::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.promotions.destroy', $promo))
        ->assertSessionHasNoErrors();

    expect(Promotion::find($promo->id))->toBeNull();
});

it('blocks non-admin users from managing promotions', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.promotions'))->assertForbidden();
});
