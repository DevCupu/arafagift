<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlertMail;
use App\Models\Product;
use App\Support\StoreSettingsCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LowStockAlert extends Command
{
    protected $signature = 'inventory:low-stock-alert';

    protected $description = 'Email admin berisi produk aktif yang stoknya di bawah ambang (sekali per periode menipis).';

    public function handle(): int
    {
        $products = Product::query()
            ->where('status', 'active')
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->whereNull('low_stock_notified_at')
            ->orderBy('stock')
            ->get();

        if ($products->isEmpty()) {
            $this->info('Tidak ada produk baru yang stoknya menipis.');

            return self::SUCCESS;
        }

        $email = trim((string) (StoreSettingsCache::store()['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = (string) config('mail.from.address');
        }

        try {
            Mail::to($email)->send(new LowStockAlertMail($products));
        } catch (Throwable $e) {
            Log::warning('Email stok menipis gagal dikirim.', ['message' => $e->getMessage()]);
            $this->error('Email gagal dikirim.');

            return self::FAILURE;
        }

        // Ditandai hanya setelah email terkirim, supaya gagal kirim dicoba lagi besok.
        Product::whereKey($products->modelKeys())->update(['low_stock_notified_at' => now()]);

        $this->info("Alert stok menipis dikirim untuk {$products->count()} produk.");

        return self::SUCCESS;
    }
}
