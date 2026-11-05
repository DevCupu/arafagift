<?php

use Illuminate\Support\Facades\Mail;

it('sends a test email', function () {
    Mail::fake();

    $this->artisan('mail:test', ['to' => 'tes@example.test'])->assertSuccessful();

    Mail::assertSentCount(1);
});

it('rejects an invalid address', function () {
    $this->artisan('mail:test', ['to' => 'bukan-email'])->assertFailed();
});
