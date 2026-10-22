<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('creates responsive variants on upload while keeping the original and product visible', function () {
    $product = Product::factory()->create(['status' => 'active']);
    $other = Product::factory()->create(['status' => 'active']);
    $admin = User::factory()->create(['is_admin' => true]);

    // A cached detail page must be invalidated when the admin adds a photo.
    $this->get('/produk/'.$product->slug)->assertOk();

    $this->actingAs($admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'unit' => $product->unit,
        'category_id' => $product->category_id,
        'price' => $product->price,
        'stock' => $product->stock,
        'low_stock_threshold' => $product->low_stock_threshold,
        'weight' => $product->weight,
        'status' => 'active',
        'image' => UploadedFile::fake()->image('foto-produk.png', 1200, 1200),
    ])->assertSessionHasNoErrors();

    $product->refresh();
    $disk = Storage::disk('public');
    $stem = pathinfo($product->image, PATHINFO_FILENAME);
    $disk->assertExists($product->image);
    $disk->assertExists('products/'.$stem.'-320.webp');
    $disk->assertExists('products/'.$stem.'-social.jpg');

    $this->get('/koleksi')->assertOk()->assertInertia(fn ($page) => $page
        ->has('products', 2)
        ->where('products.0.imageSources.webp', fn ($srcset) => str_contains($srcset, '-320.webp 320w'))
    );

    $this->get('/produk/'.$product->slug)->assertOk()->assertInertia(fn ($page) => $page
        ->where('product.imageSources.webp', fn ($srcset) => str_contains($srcset, '-320.webp 320w'))
    );
    $this->get('/produk/'.$other->slug)->assertOk();

    $html = $this->get('/produk/'.$product->slug)->getContent();
    expect($html)->toContain('og:image')
        ->toContain($stem.'-social.jpg')
        ->toContain('http://localhost/storage/products/'.$stem.'-social.jpg');
});

it('backfills variants without changing a stored product image or its record', function () {
    $path = UploadedFile::fake()->image('lama.png', 900, 900)->store('products', 'public');
    $product = Product::factory()->create(['image' => $path, 'status' => 'active']);
    $categoryPath = UploadedFile::fake()->image('kategori-lama.png', 900, 900)->store('categories', 'public');
    $category = Category::factory()->create(['image' => $categoryPath]);
    $before = hash_file('sha256', Storage::disk('public')->path($path));
    $updatedAt = $product->updated_at;
    $stem = pathinfo($path, PATHINFO_FILENAME);
    $categoryStem = pathinfo($categoryPath, PATHINFO_FILENAME);

    $this->artisan('app:optimize-product-images', ['--dry-run' => true])->assertSuccessful();
    Storage::disk('public')->assertMissing('products/'.$stem.'-320.webp');
    Storage::disk('public')->assertMissing('categories/'.$categoryStem.'-320.webp');

    $this->artisan('app:optimize-product-images')->assertSuccessful();
    Storage::disk('public')->assertExists('products/'.$stem.'-320.webp');
    Storage::disk('public')->assertExists('categories/'.$categoryStem.'-320.webp');
    $this->artisan('app:optimize-product-images')->assertSuccessful();

    expect(hash_file('sha256', Storage::disk('public')->path($path)))->toBe($before)
        ->and($product->fresh()->image)->toBe($path)
        ->and($product->fresh()->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($category->fresh()->image)->toBe($categoryPath);
});
