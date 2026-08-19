<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            ['code' => 'PULANGHAJI', 'type' => 'Potongan 10%', 'usage' => '128 / 500', 'period' => '1–31 Ags 2026', 'status' => 'active'],
            ['code' => 'ROMBONGAN50', 'type' => 'Potongan Rp 50.000', 'usage' => '12 / 100', 'period' => '1 Ags – 30 Sep 2026', 'status' => 'active'],
            ['code' => 'GRATISONGKIR', 'type' => 'Gratis ongkir reguler', 'usage' => '340 / 340', 'period' => '1–15 Ags 2026', 'status' => 'ended'],
        ];

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }
    }
}
