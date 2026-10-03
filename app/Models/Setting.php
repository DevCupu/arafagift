<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'store_name', 'tagline', 'email', 'whatsapp', 'address', 'business_hours',
    'whatsapp_secondary', 'instagram_url', 'tiktok_url', 'maps_url',
    'origin_city', 'origin_destination_id',
    'free_shipping_from', 'free_shipping_cities', 'bulk_minimum', 'handling_time', 'shipping_note',
    'store_status', 'closed_message', 'low_stock_threshold',
    'maintenance_title', 'maintenance_end_time', 'maintenance_whitelist_ips', 'maintenance_secret',
    'bank_name', 'bank_account_number', 'bank_account_name', 'payment_instructions',
    'meta_title', 'meta_description', 'google_analytics_id', 'facebook_pixel_id',
])]
class Setting extends Model
{
    /**
     * @return list<string>
     */
    public function whitelistIpsList(): array
    {
        return collect(explode(',', (string) $this->maintenance_whitelist_ips))
            ->map(fn (string $ip) => trim($ip))
            ->filter()
            ->values()
            ->all();
    }
    /**
     * @return list<string>
     */
    public function freeShippingCitiesList(): array
    {
        return collect(explode(',', (string) $this->free_shipping_cities))
            ->map(fn (string $city) => trim($city))
            ->filter()
            ->values()
            ->all();
    }
}
