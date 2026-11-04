<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\StoreSettingsCache;
use Illuminate\Support\Facades\Auth;

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

it('lets an admin update pickup and minimum order settings', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'ArafahGift.id',
        'pickup_enabled' => true,
        'pickup_instructions' => 'Ambil di kasir lantai 1.',
        'min_order_amount' => 75000,
    ])->assertSessionHasNoErrors();

    $settings = Setting::first();
    expect($settings->pickup_enabled)->toBeTrue();
    expect($settings->pickup_instructions)->toBe('Ambil di kasir lantai 1.');
    expect((int) $settings->min_order_amount)->toBe(75000);
});

it('lets an admin configure the ArafahGift delivery window', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'ArafahGift.id',
        'arafagift_delivery_start' => '13:00',
        'arafagift_delivery_end' => '16:00',
    ])->assertSessionHasNoErrors();

    $settings = Setting::first();
    expect($settings->arafagift_delivery_start->format('H:i'))->toBe('13:00')
        ->and($settings->arafagift_delivery_end->format('H:i'))->toBe('16:00');
});

it('lets an admin activate or deactivate a payment method', function () {
    $method = \App\Models\PaymentMethod::firstOrCreate(
        ['code' => 'cod'],
        ['name' => 'COD', 'is_active' => true, 'sort_order' => 30],
    );

    $this->actingAs($this->admin)->put(route('admin.settings.payment-method', $method), [
        'is_active' => false,
        'instructions' => 'Bayar tunai saat diterima.',
    ])->assertSessionHasNoErrors();

    $method->refresh();
    expect($method->is_active)->toBeFalse();
    expect($method->instructions)->toBe('Bayar tunai saat diterima.');
});

it('blocks non-admin users from updating settings', function () {
    Setting::create(['store_name' => 'Toko Awal', 'free_shipping_from' => 0, 'bulk_minimum' => 0]);
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->put(route('admin.settings.update'), [
        'store_name' => 'Hacked',
    ])->assertForbidden();
});

it('applies maintenance changes immediately even when store settings were cached', function () {
    Setting::create(['store_name' => 'Arafagift', 'store_status' => 'open']);
    StoreSettingsCache::store();

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'Arafagift',
        'store_status' => 'maintenance',
    ])->assertSessionHasNoErrors();

    Auth::logout();
    $this->get('/robots.txt')->assertStatus(503);

    $this->actingAs($this->admin)->put(route('admin.settings.update'), [
        'store_name' => 'Arafagift',
        'store_status' => 'open',
    ])->assertSessionHasNoErrors();

    Auth::logout();
    $this->get('/robots.txt')->assertOk();
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
