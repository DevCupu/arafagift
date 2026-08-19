<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            OccasionSeeder::class,
            ProductSeeder::class,
            ShippingMethodSeeder::class,
            PaymentMethodSeeder::class,
            PromotionSeeder::class,
            SettingSeeder::class,
            ContentSeeder::class,
            TestimonialSeeder::class,
            FaqSeeder::class,
            UserSeeder::class,
            AddressSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
