<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin update store settings including free shipping cities', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'ArafahGift.id',
        'whatsapp' => '+62 812-0000-0000',
        'free_shipping_from' => 500000,
        'free_shipping_cities' => 'Makassar, Jakarta Selatan',
        'bulk_minimum' => 50,
    ])->assertSessionHasNoErrors();

    $settings = Setting::first();
    expect($settings->free_shipping_cities)->toBe('Makassar, Jakarta Selatan');
    expect($settings->freeShippingCitiesList())->toBe(['Makassar', 'Jakarta Selatan']);
});

it('blocks non-admin users from updating settings', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->put(route('admin.settings.update'), [
        'store_name' => 'Hacked',
    ])->assertForbidden();
});
