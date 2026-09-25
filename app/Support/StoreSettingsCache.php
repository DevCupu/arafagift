<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class StoreSettingsCache
{
    public static function store(): array
    {
        return Cache::remember('settings-store', now()->addMinutes(10), function (): array {
            $settings = Setting::first();

            return [
                'name' => $settings?->store_name ?? config('app.name'),
                'address' => $settings?->address,
                'email' => $settings?->email,
                'whatsapp' => $settings?->whatsapp,
                'originCity' => $settings?->origin_city ?? 'Jakarta',
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
