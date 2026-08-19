<?php

use App\Models\User;

it('redirects guests from admin to the login page', function () {
    $this->get(route('admin'))->assertRedirect(route('login'));
});

it('blocks logged-in non-admin users from the admin panel', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get(route('admin'))->assertForbidden();
});

it('lets an admin access the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('admin'))->assertOk();
});

it('authenticates an admin and redirects to the admin panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin'));

    $this->assertAuthenticatedAs($admin);
});

it('rejects invalid login credentials', function () {
    User::factory()->create(['is_admin' => true]);

    $this->post(route('login'), [
        'email' => 'admin@arafahgift.id',
        'password' => 'salah-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('redirects an authenticated admin away from the login page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->get(route('login'))->assertRedirect(route('admin'));
});

it('logs out an authenticated admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});
