<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'rating' => 5,
                'quote' => 'Niatnya cuma cari oleh-oleh untuk keluarga, akhirnya semua orang di rumah minta dibawakan lagi.',
                'name' => 'Ratna Halim', 'city' => 'Surabaya', 'context' => 'Arafah Premium Box',
            ],
            [
                'rating' => 5,
                'quote' => 'Pesan 240 pouch untuk rombongan travel kami. Nama jamaah dicetak satu-satu, sampai tepat waktu, tidak ada yang rusak.',
                'name' => 'H. Zulkarnain', 'city' => 'Solo', 'context' => 'Paket Salam × 240',
            ],
            [
                'rating' => 5,
                'quote' => 'Dikirim langsung ke rumah ibu saya di kampung dan beliau kira saya yang antar sendiri. Packagingnya rapi sekali.',
                'name' => 'Dewi Anggraini', 'city' => 'Makassar', 'context' => 'Family Gift Set',
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
