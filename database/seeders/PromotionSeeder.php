<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'code' => 'PULANGHAJI', 'type' => 'Potongan 10%',
                'usage' => '128 / 500', 'period' => '1–31 Ags 2026', 'status' => 'active',
                'discount_type' => 'percent', 'discount_value' => 10,
                'min_purchase' => 0, 'max_discount' => 100000,
                'usage_limit' => 500, 'used_count' => 128,
                'applies_to' => 'all', 'is_active' => true,
            ],
            [
                'code' => 'ROMBONGAN50', 'type' => 'Potongan Rp 50.000',
                'usage' => '12 / 100', 'period' => '1 Ags – 30 Sep 2026', 'status' => 'active',
                'discount_type' => 'fixed', 'discount_value' => 50000,
                'min_purchase' => 500000,
                'usage_limit' => 100, 'used_count' => 12,
                'applies_to' => 'all', 'is_active' => true,
            ],
            [
                'code' => 'GRATISONGKIR', 'type' => 'Gratis ongkir reguler',
                'usage' => '340 / 340', 'period' => '1–15 Ags 2026', 'status' => 'ended',
                'discount_type' => 'fixed', 'discount_value' => 20000,
                'min_purchase' => 0,
                'usage_limit' => 340, 'used_count' => 340,
                'applies_to' => 'all', 'is_active' => false,
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::updateOrCreate(['code' => $promotion['code']], $promotion);
        }
    }
}
