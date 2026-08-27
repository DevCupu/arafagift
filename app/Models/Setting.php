<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['store_name', 'tagline', 'email', 'whatsapp', 'address', 'free_shipping_from', 'free_shipping_cities', 'bulk_minimum'])]
class Setting extends Model
{
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
