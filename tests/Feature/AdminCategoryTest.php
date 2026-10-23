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

it('shows the new Gift Set category and its active product count in admin', function () {
    $giftSet = Category::where('slug', 'gift-set')->firstOrFail();
    Product::factory()->for($giftSet)->create(['status' => 'active']);
    Product::factory()->for($giftSet)->create(['status' => 'draft']);

    $this->actingAs($this->admin)->get(route('admin.categories'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/CategoriesPage')
            ->where('categories.0.slug', 'gift-set')
            ->where('categories.0.count', 1));
});

it('lets an admin create a category with an image', function () {
    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Parfum Sunnah',
        'slug' => 'parfum-sunnah',
        'art' => 'giftset',
        'tagline' => 'Non alkohol',
        'image' => UploadedFile::fake()->image('kategori.jpg', 900, 900),
    ])->assertSessionHasNoErrors();

    $category = Category::where('slug', 'parfum-sunnah')->first();
    expect($category->image)->not->toBeNull();
    Storage::disk('public')->assertExists($category->image);
    Storage::disk('public')->assertExists('categories/'.pathinfo($category->image, PATHINFO_FILENAME).'-320.webp');
    expect($category->toCatalog()['imageSources']['webp'])->toContain('-320.webp 320w');
});

it('lets an admin replace a category image on update and removes the old upload', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'art' => $category->art,
        'tagline' => $category->tagline,
        'image' => UploadedFile::fake()->image('baru.jpg'),
    ])->assertSessionHasNoErrors();

    $oldImage = $category->fresh()->image;
    Storage::disk('public')->assertExists($oldImage);

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'art' => $category->art,
        'tagline' => $category->tagline,
        'image' => UploadedFile::fake()->image('terbaru.jpg'),
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($oldImage);
    expect($category->fresh()->image)->not->toBe($oldImage);
});

it('keeps seeded static images intact when replacing them with an upload', function () {
    $category = Category::factory()->create(['image' => '/images/catalog/kurma.jpg']);

    $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
        'name' => $category->name,
        'slug' => $category->slug,
        'art' => $category->art,
        'tagline' => $category->tagline,
        'image' => UploadedFile::fake()->image('upload.jpg'),
    ])->assertSessionHasNoErrors();

    expect(str_starts_with($category->fresh()->image, '/'))->toBeFalse();
});

it('accepts a method-spoofed multipart update as browsers send it', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.categories.update', $category), [
        '_method' => 'put',
        'name' => $category->name,
        'slug' => $category->slug,
        'art' => $category->art,
        'tagline' => $category->tagline,
        'image' => UploadedFile::fake()->image('spoofed.jpg'),
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists($category->fresh()->image);
});
