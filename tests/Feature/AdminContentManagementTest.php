<?php

use App\Models\Content;
use App\Models\Product;
use App\Models\Testimonial;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    Content::create(['key' => 'home', 'data' => ['announcement' => 'x']]);
});

it('lets an admin add, update, and delete a testimonial', function () {
    $this->actingAs($this->admin)->post(route('admin.content.testimonials.store'), [
        'rating' => 5, 'quote' => 'Mantap', 'name' => 'Budi', 'city' => 'Jakarta',
    ])->assertSessionHasNoErrors();

    $testimonial = Testimonial::where('name', 'Budi')->firstOrFail();

    $this->actingAs($this->admin)->put(route('admin.content.testimonials.update', $testimonial), [
        'rating' => 4, 'quote' => 'Bagus sekali', 'name' => 'Budi', 'city' => 'Bandung',
    ])->assertSessionHasNoErrors();
    expect($testimonial->fresh()->city)->toBe('Bandung');

    $this->actingAs($this->admin)->delete(route('admin.content.testimonials.destroy', $testimonial))
        ->assertSessionHasNoErrors();
    expect(Testimonial::find($testimonial->id))->toBeNull();
});

it('lets an admin add and remove a product from the featured list', function () {
    $product = Product::factory()->create(['featured' => false]);

    $this->actingAs($this->admin)->patch(route('admin.content.featured.add', $product))
        ->assertSessionHasNoErrors();
    expect($product->fresh()->featured)->toBeTrue();

    $this->actingAs($this->admin)->patch(route('admin.content.featured.remove', $product))
        ->assertSessionHasNoErrors();
    expect($product->fresh()->featured)->toBeFalse();
});

it('blocks non-admin users from managing content', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin.content'))->assertForbidden();
});
