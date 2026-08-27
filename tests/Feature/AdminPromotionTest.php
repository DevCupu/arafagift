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
    ])->assertSessionHasErrors('code');
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
