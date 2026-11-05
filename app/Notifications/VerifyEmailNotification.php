<?php

namespace App\Notifications;

use App\Support\StoreSettingsCache;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/** Email verifikasi memakai layout toko yang sama dengan email pesanan. */
class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $store = StoreSettingsCache::store();

        return (new MailMessage)
            ->subject('Verifikasi email Anda — '.($store['name'] ?? config('app.name')))
            ->view('mail.auth.verify-email', [
                'user' => $notifiable,
                'store' => $store,
                'verifyUrl' => $this->verificationUrl($notifiable),
            ]);
    }
}
