<?php

use App\Models\Content;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    Content::create([
        'key' => 'home',
        'data' => [
            'hero' => ['headline' => 'Hadiah dari Tanah Suci'],
            'signature' => ['productSlug' => 'tidak-ada'],
        ],
    ]);
});

it('exposes analytics ids as config without loading third-party scripts before consent', function () {
    Setting::create([
        'store_name' => 'Arafagift',
        'google_analytics_id' => 'G-TEST12345',
        'facebook_pixel_id' => '987654321',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('__ANALYTICS_CONFIG__')
        ->toContain('G-TEST12345')
        ->toContain('987654321')
        // Script vendor baru dimuat oleh AnalyticsConsent setelah izin.
        ->not->toContain('googletagmanager.com/gtag/js');
});

it('does not emit analytics config when no ids are configured', function () {
    Setting::create(['store_name' => 'Arafagift']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('__ANALYTICS_CONFIG__');
});
