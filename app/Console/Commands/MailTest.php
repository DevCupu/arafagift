<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTest extends Command
{
    protected $signature = 'mail:test {to : Alamat email penerima tes}';

    protected $description = 'Kirim email tes untuk memastikan konfigurasi SMTP benar.';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Mailer: %s, host: %s:%s, dari: %s',
            config('mail.default'),
            config('mail.mailers.'.config('mail.default').'.host', '-'),
            config('mail.mailers.'.config('mail.default').'.port', '-'),
            config('mail.from.address'),
        ));

        try {
            Mail::raw('Tes SMTP dari '.config('app.name').'. Kalau email ini masuk, konfigurasi sudah benar.', fn ($message) => $message
                ->to($to)
                ->subject('Tes SMTP '.config('app.name')));
        } catch (Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Email tes dikirim ke {$to}. Cek inbox dan folder spam.");

        return self::SUCCESS;
    }
}
