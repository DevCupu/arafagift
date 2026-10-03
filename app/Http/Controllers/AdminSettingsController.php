<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\StoreSettingsCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function edit(): Response
    {
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? (PHP_OS_FAMILY.' ('.php_uname('s').')'),
            'opcache_active' => function_exists('opcache_get_status') && is_array(@opcache_get_status(false)) && ! empty(opcache_get_status(false)['opcache_enabled']),
            'storage_linked' => file_exists(public_path('storage')),
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'client_ip' => request()->ip() ?? '127.0.0.1',
        ];

        return Inertia::render('admin/SettingsPage', [
            'settings' => Setting::firstOrFail(),
            'systemInfo' => $systemInfo,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Identitas & Jam
            'store_name' => ['required', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:400'],
            'business_hours' => ['nullable', 'string', 'max:120'],
            'origin_city' => ['nullable', 'string', 'max:120'],
            'origin_destination_id' => ['nullable', 'string', 'max:50'],

            // Kontak & Sosial Media
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'whatsapp_secondary' => ['nullable', 'string', 'max:30'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'tiktok_url' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'string', 'max:1000'],

            // Aturan Penjualan & Pengiriman
            'free_shipping_from' => ['nullable', 'integer', 'min:0'],
            'free_shipping_cities' => ['nullable', 'string', 'max:500'],
            'bulk_minimum' => ['nullable', 'integer', 'min:0'],
            'handling_time' => ['nullable', 'string', 'max:100'],
            'shipping_note' => ['nullable', 'string', 'max:500'],

            // Operasional, Maintenance & Status
            'store_status' => ['nullable', 'string', 'in:open,holiday,maintenance'],
            'closed_message' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'maintenance_title' => ['nullable', 'string', 'max:150'],
            'maintenance_end_time' => ['nullable', 'string', 'max:50'],
            'maintenance_whitelist_ips' => ['nullable', 'string', 'max:1000'],
            'maintenance_secret' => ['nullable', 'string', 'max:100'],

            // Rekening Pembayaran Manual
            'bank_name' => ['nullable', 'string', 'max:60'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:100'],
            'payment_instructions' => ['nullable', 'string', 'max:1000'],

            // SEO & Analitik
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'facebook_pixel_id' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['free_shipping_from'] = $validated['free_shipping_from'] ?? 0;
        $validated['bulk_minimum'] = $validated['bulk_minimum'] ?? 0;
        $validated['low_stock_threshold'] = $validated['low_stock_threshold'] ?? 5;
        $validated['store_status'] = $validated['store_status'] ?? 'open';

        Setting::firstOrFail()->update($validated);
        StoreSettingsCache::forget();

        return back()->with('success', 'Pengaturan sistem toko berhasil disimpan');
    }

    public function clearAllCache(): RedirectResponse
    {
        Cache::flush();
        StoreSettingsCache::forget();
        SitemapController::flush();

        return back()->with('success', 'Seluruh cache payload dan data aplikasi berhasil dibersihkan.');
    }

    public function rewarmCache(): RedirectResponse
    {
        Artisan::call('app:cache-warm');

        return back()->with('success', 'Semua cache payload toko (homepage, koleksi, sitemap, pengaturan) berhasil di-rebuild.');
    }

    public function clearViews(): RedirectResponse
    {
        Artisan::call('view:clear');

        return back()->with('success', 'Kompilasi template Blade berhasil di-flush.');
    }

    public function reloadOpcache(): RedirectResponse
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        $lsphpRestart = '/home/izim8295/.lsphp_restart.txt';
        if (file_exists('/home/izim8295')) {
            @touch($lsphpRestart);
        }

        return back()->with('success', 'Sinyal reload OPcache / LiteSpeed PHP berhasil dikirim.');
    }
}
