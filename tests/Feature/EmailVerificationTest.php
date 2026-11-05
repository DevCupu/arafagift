<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('blocks unverified users from the account area', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/akun')->assertRedirect(route('verification.notice'));
    $this->actingAs($user)->get(route('verification.notice'))->assertOk();
});

it('lets verified users into the account area', function () {
    $this->actingAs(User::factory()->create())->get('/akun')->assertOk();
});

it('sends the verification email on registration and resend', function () {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Budi',
        'email' => 'budi@example.test',
        'password' => 'Password-12345',
        'password_confirmation' => 'Password-12345',
    ]);

    $user = User::where('email', 'budi@example.test')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmailNotification::class);

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
    Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
});

it('renders the verification email with the store layout', function () {
    $user = User::factory()->unverified()->create();
    $mail = (new VerifyEmailNotification)->toMail($user);

    expect($mail->view)->toBe('mail.auth.verify-email');
    expect(view($mail->view, $mail->viewData)->render())->toContain('Verifikasi email');
});

it('verifies the email through the signed link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('account'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
