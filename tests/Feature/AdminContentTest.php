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
    Content::create([
        'key' => 'home',
        'data' => [
            'announcement' => 'Promo',
            'hero' => ['eyebrow' => 'e', 'headline' => 'h', 'sub' => 's', 'cta' => ['label' => 'l', 'to' => '/koleksi']],
            'signature' => ['title' => 't', 'body' => 'b', 'productSlug' => $product->slug],
            'instagram' => ['handle' => '@x', 'url' => 'https://instagram.com/x'],
        ],
    ]);

    $this->actingAs($this->admin)->put(route('admin.content.update'), [
        'announcement' => 'Promo baru',
        'hero' => ['eyebrow' => 'e', 'headline' => 'h', 'sub' => 's', 'cta' => ['label' => 'l', 'to' => '/koleksi']],
        'signature' => ['title' => 't', 'body' => 'b', 'productSlug' => $product->slug],
        'instagram' => ['handle' => '@x', 'url' => 'https://instagram.com/x'],
        'hero_image' => UploadedFile::fake()->image('hero.jpg'),
    ])->assertSessionHasNoErrors();

    $image = Content::where('key', 'home')->first()->data['hero']['image'];
    expect($image)->not->toBeNull();
});
