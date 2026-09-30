<?php

use App\Models\Content;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin upload a hero image for the homepage', function () {
    $product = Product::factory()->create();

    // ContentController::update() memvalidasi seluruh blok home sekaligus,
    // jadi payload harus lengkap. Test ini fokus pada hero_image, tapi tetap
    // harus lolos semua aturan validasi yang berlaku.
    $payload = fn (string $announcement) => [
        'announcement' => $announcement,
        'hero' => [
            'eyebrow' => 'Oleh-oleh Haji & Umrah',
            'headline' => 'h',
            'sub' => 's',
            'cta' => ['label' => 'l', 'to' => '/koleksi'],
            'ctaSecondary' => ['label' => 'l2', 'to' => '/koleksi/gift-set'],
        ],
        'signature' => [
            'eyebrow' => 'Pilihan enthalpy',
            'title' => 't',
            'body' => 'b',
            'productSlug' => $product->slug,
            'cta' => ['label' => 'l3', 'to' => '/produk/'.$product->slug],
        ],
        'bulk' => [
            'eyebrow' => 'Custom',
            'title' => 'bt',
            'sub' => 'bs',
            'points' => ['Mulai 50 pcs'],
            'cta' => ['label' => 'l4', 'href' => 'https://wa.me/628192242444'],
        ],
        'story' => [
            'eyebrow' => 'Cerita kami',
            'title' => 'st',
            'body' => ['Baris pertama.'],
            'signature' => 'Arafagift',
        ],
        'instagram' => [
            'handle' => '@x',
            'title' => 'it',
            'url' => 'https://instagram.com/x',
            'posts' => [['art' => 'giftset', 'caption' => 'c']],
        ],
        'values' => [
            ['icon' => 'Gift', 'title' => 'v', 'body' => 'vb'],
        ],
    ];

    Content::create(['key' => 'home', 'data' => $payload('Promo')]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        ...$payload('Promo baru'),
        'hero_image' => UploadedFile::fake()->image('hero.jpg'),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];
    expect($image)->not->toBeNull();
});
