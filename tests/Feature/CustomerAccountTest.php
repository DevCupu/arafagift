<?php

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->customer = User::factory()->create(['is_admin' => false]);
});

it('toggles a product in and out of the wishlist', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->customer)->post(route('wishlist.toggle', $product))
        ->assertSessionHasNoErrors();
    expect($this->customer->wishlists()->where('product_id', $product->id)->exists())->toBeTrue();

    $this->actingAs($this->customer)->post(route('wishlist.toggle', $product));
    expect($this->customer->wishlists()->where('product_id', $product->id)->exists())->toBeFalse();
});

it('lets a customer add an address and makes the first one primary automatically', function () {
    $this->actingAs($this->customer)->post(route('account.address.store'), [
        'label' => 'Rumah',
        'recipient_name' => 'Budi',
        'phone' => '0812345678',
        'address_text' => 'Jl. Contoh No. 1',
    ])->assertSessionHasNoErrors();

    $address = Address::where('user_id', $this->customer->id)->first();
    expect($address)->not->toBeNull();
    expect($address->is_primary)->toBeTrue();
});

it('prevents a customer from editing another customer\'s address', function () {
    $other = User::factory()->create();
    $address = Address::factory()->for($other)->create();

    $this->actingAs($this->customer)->put(route('account.address.update', $address), [
        'label' => 'Hacked', 'recipient_name' => 'x', 'phone' => '1', 'address_text' => 'x',
    ])->assertForbidden();
});

it('lets a customer update their profile', function () {
    $this->actingAs($this->customer)->put(route('account.update'), [
        'name' => 'Nama Baru',
        'email' => $this->customer->email,
        'phone' => '0899999999',
    ])->assertSessionHasNoErrors();

    expect($this->customer->fresh()->name)->toBe('Nama Baru');
});

it('lets a customer change their password with the correct current password', function () {
    $this->actingAs($this->customer)->put(route('account.password'), [
        'current_password' => 'password',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasNoErrors();

    $this->assertTrue(Hash::check('newpassword123', $this->customer->fresh()->password));
});
