<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        Content::create([
            'key' => 'home',
            'data' => [
                'announcement' => 'Gratis kartu ucapan tulis tangan untuk setiap pesanan hadiah',
                'hero' => [
                    'eyebrow' => 'Oleh-oleh Umrah & Hajj',
                    'headline' => "Hadiah dari Tanah Suci,\nuntuk hati yang dekat.",
                    'sub' => 'Koleksi yang dipilih dengan hati — untuk keluarga, sahabat, dan orang-orang terkasih yang menunggu di rumah.',
                    'cta' => ['label' => 'Jelajahi Koleksi', 'to' => '/koleksi'],
                    'ctaSecondary' => ['label' => 'Lihat Gift Set', 'to' => '/koleksi/gift-set'],
                    'note' => 'Dikirim dari Jakarta • Kartu ucapan gratis',
                ],
                'signature' => [
                    'eyebrow' => 'Signature',
                    'title' => 'A Gift Worth Remembering',
                    'body' => 'Satu box yang tidak perlu dijelaskan saat diberikan. Isinya kami susun supaya terasa lengkap — ada yang dinikmati bersama, ada yang dipakai setiap hari.',
                    'productSlug' => 'arafah-premium-box',
                    'cta' => ['label' => 'Discover the Collection', 'to' => '/produk/arafah-premium-box'],
                ],
                'story' => [
                    'eyebrow' => 'Cerita kami',
                    'title' => 'Setiap perjalanan pulang membawa cerita.',
                    'body' => [
                        'ArafahGift dimulai dari satu koper yang selalu kurang muat. Pulang dari tanah suci, yang paling sulit bukan perjalanannya — tapi memilih apa yang pantas dibawa untuk orang di rumah.',
                        'Kami mengurus bagian itu: mencari yang benar-benar bagus, mengemasnya dengan rapi, dan menuliskan kartunya. Sisanya, biar doa dan cerita Anda yang bicara.',
                    ],
                    'signature' => 'Tim ArafahGift, Jakarta',
                ],
                'bulk' => [
                    'eyebrow' => 'Rombongan',
                    'title' => 'Souvenir untuk satu rombongan?',
                    'sub' => 'Pesan dalam jumlah banyak dengan packaging yang tetap cantik. Kami cetak nama jamaah dan tanggal keberangkatan di setiap kartu.',
                    'points' => [
                        'Mulai 50 pcs, harga menyesuaikan jumlah',
                        'Custom kartu, logo travel, dan pita',
                        'Produksi 3–5 hari kerja, kirim ke satu atau banyak alamat',
                    ],
                    'cta' => ['label' => 'Konsultasi via WhatsApp', 'href' => 'https://wa.me/6281234567890'],
                ],
                'instagram' => [
                    'handle' => '@arafahajiumrahgift',
                    'title' => 'Follow the journey',
                    'url' => 'https://www.instagram.com/arafahajiumrahgift/',
                    'posts' => [
                        ['art' => 'giftset', 'caption' => 'Box untuk keluarga Bu Ratna'],
                        ['art' => 'kurma', 'caption' => 'Ajwa baru datang pagi ini'],
                        ['art' => 'sajadah', 'caption' => 'Lipat, masuk tas kabin'],
                        ['art' => 'tasbih', 'caption' => 'Kayu zaitun, 33 butir'],
                        ['art' => 'souvenir', 'caption' => '240 pouch untuk rombongan Solo'],
                        ['art' => 'madu', 'caption' => 'Sidr musim gugur'],
                    ],
                ],
            ],
        ]);
    }
}
