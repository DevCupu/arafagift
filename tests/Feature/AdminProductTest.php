<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin update a product without error', function () {
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => 'Arafah Deluxe Box',
        'slug' => $product->slug,
        'sku' => $product->sku,
        'unit' => 'box',
        'category_id' => $category->id,
        'price' => 750000,
        'stock' => 20,
        'low_stock_threshold' => 5,
        'weight' => 1200,
        'status' => 'active',
        'featured' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    expect($product->fresh())
        ->name->toBe('Arafah Deluxe Box')
        ->category_id->toBe($category->id)
        ->price->toBe(750000)
        ->featured->toBeTrue();
});

it('lets an admin upload a product image on update', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'slug' => $product->slug,
        'sku' => $product->sku,
        'unit' => $product->unit ?? 'pcs',
        'category_id' => $product->category_id,
        'price' => $product->price,
        'stock' => $product->stock,
        'low_stock_threshold' => $product->low_stock_threshold,
        'weight' => $product->weight,
        'status' => $product->status,
        'image' => UploadedFile::fake()->image('produk.jpg'),
    ])->assertSessionHasNoErrors();

    $product->refresh();
    expect($product->image)->not->toBeNull();
    Storage::disk('public')->assertExists($product->image);
});

it('rejects a product update with invalid data', function () {
    $product = Product::factory()->create();
    $other = Product::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.products.update', $product), [
        'name' => '',
        'slug' => $product->slug,
        'sku' => $other->sku,
        'category_id' => 999,
        'price' => -10,
        'stock' => 0,
        'low_stock_threshold' => 0,
        'weight' => 0,
        'status' => 'invalid-status',
    ])->assertSessionHasErrors(['name', 'sku', 'category_id', 'price', 'status']);
});

it('lets an admin create a product', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.products.store'), [
        'name' => 'Kurma Ajwa Baru',
        'slug' => 'kurma-ajwa-baru',
        'sku' => 'AGF-NEW-01',
        'unit' => 'pcs',
        'category_id' => $category->id,
        'price' => 100000,
        'stock' => 10,
        'low_stock_threshold' => 5,
        'weight' => 500,
        'status' => 'draft',
    ])->assertSessionHasNoErrors();

    expect(Product::where('slug', 'kurma-ajwa-baru')->exists())->toBeTrue();
});

it('lets an admin delete a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.products.destroy', $product))
        ->assertSessionHasNoErrors();

    expect(Product::find($product->id))->toBeNull();
});

it('blocks non-admin users from updating a product', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $product = Product::factory()->create();

    $this->actingAs($user)->put(route('admin.products.update', $product), [
        'name' => 'Hacked',
    ])->assertForbidden();
});
