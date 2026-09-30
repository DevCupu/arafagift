<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            // Brand tanpa huruf h, sama dengan domain arafagift.id dan APP_NAME.
            // Nilai ini juga masuk ke structured data Store, jadi harus persis
            // sama dengan nama di Google Business Profile.
            'store_name' => 'Arafagift',
            'tagline' => 'Oleh-oleh Haji & Umrah yang dipilih dengan hati.',
            'email' => 'halo@arafagift.id',
            // Nomor ini ikut masuk ke structured data Store, jadi harus sama
            // persis dengan WhatsApp di Google Business Profile. Nilai lama
            // "+62 812-3456-7890" adalah placeholder dan tidak boleh.seed ulang.
            'whatsapp' => '08192242444',
            'address' => 'Jl. Abdullah Daeng Sirua No. 61, Lantai 2, Tamamaung, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231',
            'origin_city' => 'Makassar',
            'free_shipping_from' => 750000,
            'free_shipping_cities' => 'Makassar',
            'bulk_minimum' => 50,
        ]);
    }
}
