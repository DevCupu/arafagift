<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'reguler', 'name' => 'Reguler', 'eta' => '2–4 hari', 'price' => 22000],
            ['code' => 'kargo', 'name' => 'Kargo rombongan', 'eta' => '4–7 hari', 'price' => 15000],
            ['code' => 'sameday', 'name' => 'Same day (Jabodetabek)', 'eta' => 'Hari ini', 'price' => 45000],
        ];

        foreach ($methods as $method) {
            ShippingMethod::create($method);
        }
    }
}
