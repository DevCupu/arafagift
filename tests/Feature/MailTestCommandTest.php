<?php

use Illuminate\Support\Facades\Mail;

it('sends a test email', function () {
    // Mail::raw tidak tercatat oleh Mail::fake(); transport array (env test) menyimpan pesannya.
    $this->artisan('mail:test', ['to' => 'tes@example.test'])->assertSuccessful();

    $messages = Mail::mailer('array')->getSymfonyTransport()->messages();

    expect($messages)->toHaveCount(1)
        ->and($messages[0]->getEnvelope()->getRecipients()[0]->getAddress())->toBe('tes@example.test');
});

it('rejects an invalid address', function () {
    $this->artisan('mail:test', ['to' => 'bukan-email'])->assertFailed();
});
