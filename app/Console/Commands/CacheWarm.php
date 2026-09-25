<?php

namespace App\Console\Commands;

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Models\Content;
use App\Models\Order;
use App\Support\StoreSettingsCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheWarm extends Command
{
    protected $signature = 'app:cache-warm';

    protected $description = 'Warm cache payload toko sebelum benar-benar melayani pengunjung (dipanggil di deploy).';

    public function handle(): int
    {
        $this->line('Memaksa rebuild semua cache payload toko...');

        Cache::forget('settings-store');
        StoreSettingsCache::store();
        $this->line('settings-store warmed.');

        Cache::forget('home-content');
        Cache::put('home-content', Content::where('key', 'home')->first()?->data, now()->addMinutes(10));
        $this->line('home-content warmed.');

        Cache::forget('home-payload');
        HomeController::payload();
        $this->line('home-payload warmed.');

        Cache::forget('koleksi-payload');
        CollectionController::payload();
        $this->line('koleksi-payload warmed.');

        Cache::forget('sitemap-urls');
        SitemapController::urls();
        $this->line('sitemap-urls warmed.');

        Cache::put('pending-orders-count', Order::where('status', 'pending')->count(), now()->addMinutes(5));
        $this->line('pending-orders-count warmed.');

        $this->newLine();
        $this->info('Cache toko sudah hangat.');

        return self::SUCCESS;
    }
}
