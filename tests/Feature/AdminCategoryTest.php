<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lets an admin create a category with an image', function () {
    $this->actingAs($this->admin)->post(route('admin.categories.store'), [
        'name' => 'Parfum Sunnah',
        'slug' => 'parfum-sunnah',
        'art' => 'giftset',
        'tagline' => 'Non alkohol',
        'image' => UploadedFile::fake()->image('kategori.jpg'),
    ])->assertSessionHasNoErrors();

    $category = Category::where('slug', 'parfum-sunnah')->first();
    expect($category->image)->not->toBeNull();
    Storage::disk('public')->assertExists($category->image);
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
