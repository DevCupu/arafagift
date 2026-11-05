<?php

namespace App\Console\Commands;

use App\Services\OrderLifecycle;
use Illuminate\Console\Command;

class RemindUnpaidOrders extends Command
{
    protected $signature = 'orders:remind-payment';

    protected $description = 'Kirim email pengingat untuk pesanan yang batas pembayarannya hampir habis.';

    public function handle(): int
    {
        $this->info(OrderLifecycle::sendPaymentReminders().' pengingat pembayaran dikirim.');

        return self::SUCCESS;
    }
}
