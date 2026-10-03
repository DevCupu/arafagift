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

it('lets an admin update complete system settings across all categories', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);

    $payload = [
        'store_name' => 'Arafagift Official',
        'tagline' => 'Oleh-oleh Premium Tanah Suci',
        'address' => 'Jl. Abdullah Daeng Sirua No. 61, Makassar',
        'business_hours' => 'Senin - Sabtu 08:00 - 17:00 WITA',
        'origin_city' => 'Makassar',
        'origin_destination_id' => '12345',
        'email' => 'cs@arafagift.id',
        'whatsapp' => '08192242444',
        'whatsapp_secondary' => '081234567890',
        'instagram_url' => 'https://instagram.com/arafagift.id',
        'tiktok_url' => 'https://tiktok.com/@arafagift',
        'maps_url' => 'https://maps.app.goo.gl/test',
        'free_shipping_from' => 750000,
        'free_shipping_cities' => 'Makassar, Gowa, Maros',
        'bulk_minimum' => 50,
        'handling_time' => '1 - 2 hari kerja',
        'shipping_note' => 'Order sebelum 15:00 WITA dikirim di hari yang sama',
        'store_status' => 'open',
        'closed_message' => null,
        'low_stock_threshold' => 10,
        'bank_name' => 'Bank Mandiri',
        'bank_account_number' => '1234567890',
        'bank_account_name' => 'PT Arafagift Indonesia',
        'payment_instructions' => 'Kirim bukti transfer ke WhatsApp',
        'meta_title' => 'Arafagift - Oleh-oleh Haji Makassar',
        'meta_description' => 'Pusat oleh-oleh haji dan umrah terpercaya di Makassar.',
        'google_analytics_id' => 'G-TEST12345',
        'facebook_pixel_id' => 'PIXEL12345',
    ];

    $this->actingAs($this->admin)->put(route('admin.settings.update'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $settings = Setting::first();
    expect($settings->store_name)->toBe('Arafagift Official')
        ->and($settings->business_hours)->toBe('Senin - Sabtu 08:00 - 17:00 WITA')
        ->and($settings->bank_name)->toBe('Bank Mandiri')
        ->and($settings->low_stock_threshold)->toBe(10)
        ->and($settings->google_analytics_id)->toBe('G-TEST12345');
});

