<?php

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\GiftSetCategorySeeder;

it('creates only the missing Gift Set category and keeps product assignments', function () {
    Category::where('slug', 'gift-set')->delete();
    $other = Category::factory()->create(['slug' => 'paket-oleh-oleh']);
    $product = Product::factory()->for($other)->create();

    $this->seed(GiftSetCategorySeeder::class);
    $this->seed(GiftSetCategorySeeder::class);

    expect(Category::where('slug', 'gift-set')->count())->toBe(1)
        ->and(Category::where('slug', 'gift-set')->first()->name)->toBe('Gift Set')
        ->and($product->fresh()->category_id)->toBe($other->id);
});

it('preserves Gift Set details already edited in admin', function () {
    $giftSet = Category::where('slug', 'gift-set')->firstOrFail();
    $giftSet->update(['name' => 'Gift Set Pilihan', 'tagline' => 'Pilihan admin']);

    $this->seed(GiftSetCategorySeeder::class);

    expect($giftSet->fresh()->name)->toBe('Gift Set Pilihan')
        ->and($giftSet->fresh()->tagline)->toBe('Pilihan admin');
});
