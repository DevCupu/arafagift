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
                'tagline' => $settings?->tagline,
                'address' => $settings?->address,
                'businessHours' => $settings?->business_hours,
                'email' => $settings?->email,
                'whatsapp' => $settings?->whatsapp,
                'whatsappSecondary' => $settings?->whatsapp_secondary,
                'instagramUrl' => $settings?->instagram_url,
                'tiktokUrl' => $settings?->tiktok_url,
                'mapsUrl' => $settings?->maps_url,
                // Fallback wajib Makassar, bukan Jakarta: alamat ini ikut ke
                // structured data Store dan dibandingkan Google Business Profile.
                // Default yang salah di sini membuat situs dan GBP terlihat seperti
                // dua bisnis berbeda.
                'originCity' => $settings?->origin_city ?? 'Makassar',
                'originDestinationId' => $settings?->origin_destination_id,
                'freeShippingFrom' => $settings?->free_shipping_from ?? 0,
                'freeShippingCities' => $settings?->freeShippingCitiesList() ?? [],
                'bulkMinimum' => $settings?->bulk_minimum ?? 0,
                'handlingTime' => $settings?->handling_time,
                'shippingNote' => $settings?->shipping_note,
                'storeStatus' => $settings?->store_status ?? 'open',
                'closedMessage' => $settings?->closed_message,
                'maintenanceTitle' => $settings?->maintenance_title,
                'maintenanceEndTime' => $settings?->maintenance_end_time,
                'maintenanceWhitelistIps' => $settings?->maintenance_whitelist_ips,
                'maintenanceSecret' => $settings?->maintenance_secret,
                'lowStockThreshold' => $settings?->low_stock_threshold ?? 5,
                'bankName' => $settings?->bank_name,
                'bankAccountNumber' => $settings?->bank_account_number,
                'bankAccountName' => $settings?->bank_account_name,
                'paymentInstructions' => $settings?->payment_instructions,
                'metaTitle' => $settings?->meta_title,
                'metaDescription' => $settings?->meta_description,
                'googleAnalyticsId' => $settings?->google_analytics_id,
                'facebookPixelId' => $settings?->facebook_pixel_id,
            ];
        });
    }

    public static function forget(): void
    {
        Cache::forget('settings-store');
    }
}
