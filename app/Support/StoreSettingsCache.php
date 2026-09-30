<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class StoreSettingsCache
{
    public static function store(): array
    {
        return Cache::flexible('settings-store', [600, 3600], function (): array {
            $settings = Setting::first();

            return [
                'name' => $settings?->store_name ?? config('app.name'),
                'address' => $settings?->address,
                'email' => $settings?->email,
                'whatsapp' => $settings?->whatsapp,
                // Fallback wajib Makassar, bukan Jakarta: alamat ini ikut ke
                // structured data Store dan dibandingkan Google Business Profile.
                // Default yang salah di sini membuat situs dan GBP terlihat seperti
                // dua bisnis berbeda.
                'originCity' => $settings?->origin_city ?? 'Makassar',
                'originDestinationId' => $settings?->origin_destination_id,
                'freeShippingFrom' => $settings?->free_shipping_from ?? 0,
                'freeShippingCities' => $settings?->freeShippingCitiesList() ?? [],
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('settings-store');
    }
}
