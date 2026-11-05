<?php

namespace App\Console\Commands;

use App\Services\OrderLifecycle;
use Illuminate\Console\Command;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Batalkan pesanan yang belum dibayar setelah batas waktu pembayaran lewat.';

    public function handle(): int
    {
        $expired = OrderLifecycle::expireUnpaidOrders();

        $this->info($expired === 0
            ? 'Tidak ada pesanan yang perlu dibatalkan.'
            : "{$expired} pesanan dibatalkan karena melewati batas pembayaran.");

        return self::SUCCESS;
    }
}
