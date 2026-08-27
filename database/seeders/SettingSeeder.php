<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'store_name' => 'ArafahGift.id',
            'tagline' => 'Oleh-oleh Umrah & Hajj yang dipilih dengan hati.',
            'email' => 'halo@arafahgift.id',
            'whatsapp' => '+62 812-3456-7890',
            'address' => 'Jl. Cikini Raya 45, Jakarta Pusat 10330',
            'free_shipping_from' => 750000,
            'free_shipping_cities' => 'Makassar',
            'bulk_minimum' => 50,
        ]);
    }
}
