<?php

use App\Models\Faq;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

it('returns a compact search payload and caches repeated hot searches', function () {
    $product = Product::factory()->create([
        'name' => 'Kurma Ajwa Premium',
        'description' => 'Kurma pilihan untuk keluarga',
        'status' => 'active',
        'cost' => 50000,
    ]);

    DB::enableQueryLog();

    $this->getJson('/pencarian?q=kurma')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, private')
        ->assertJsonPath('0.id', $product->id)
        ->assertJsonMissingPath('0.cost')
        ->assertJsonMissingPath('0.description')
        ->assertJsonMissingPath('0.supplier');

    expect(DB::getQueryLog())->toHaveCount(2);

    DB::flushQueryLog();

    $this->getJson('/pencarian?q=KURMA')->assertOk();

    expect(DB::getQueryLog())->toHaveCount(0);

    DB::disableQueryLog();
});

it('serves the faq page from cache after the first request', function () {
    Faq::factory()->count(3)->create();

    $this->get('/faq')->assertOk()->assertInertia(fn ($page) => $page->has('faqs', 3));

    DB::enableQueryLog();
    $this->get('/faq')->assertOk();

    $faqQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains($query, 'faqs'));

    expect($faqQueries)->toHaveCount(0);

    DB::disableQueryLog();
});
