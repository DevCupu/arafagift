<?php

use App\Models\User;

it('lets a guest register as a customer and logs them in', function () {
    $response = $this->post(route('register'), [
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
        'phone' => '081234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('account'));

    $user = User::where('email', 'budi@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->is_admin)->toBeFalse();
    $this->assertAuthenticatedAs($user);
});

it('rejects registration with a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register'), [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

it('redirects a customer to the account page after login', function () {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->post(route('login'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('account'));
});

it('blocks guests from the account area and sends them to login', function () {
    $this->get(route('account'))->assertRedirect(route('login'));
    $this->get(route('account.wishlist'))->assertRedirect(route('login'));
    $this->get(route('account.address'))->assertRedirect(route('login'));
});
