<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['q' => 'Apakah bisa pesan dalam jumlah banyak?', 'a' => 'Bisa. Mulai 50 pcs harga sudah menyesuaikan jumlah, dan kami siapkan sample terlebih dulu sebelum produksi penuh. Hubungi kami lewat WhatsApp untuk penawaran.'],
            ['q' => 'Bisa custom packaging?', 'a' => 'Bisa. Kartu ucapan, nama jamaah, logo travel, warna pita, dan sleeve box bisa disesuaikan. Untuk box custom penuh, minimum 100 pcs.'],
            ['q' => 'Bisa kirim langsung ke penerima?', 'a' => 'Bisa. Isi alamat penerima di halaman checkout, lalu tulis pesan Anda di kolom kartu ucapan. Nota harga tidak pernah kami sertakan di dalam paket.'],
            ['q' => 'Berapa lama proses pesanan?', 'a' => 'Pesanan satuan dikemas di hari yang sama jika dibayar sebelum pukul 14.00 WIB. Pesanan rombongan butuh 3–5 hari kerja.'],
            ['q' => 'Apakah tersedia souvenir rombongan?', 'a' => 'Tersedia. Paket Salam adalah pilihan paling sering diambil travel: kurma, tasbih, dan kartu nama jamaah dalam satu pouch.'],
            ['q' => 'Bagaimana cara melakukan pembayaran?', 'a' => 'Transfer bank (BCA, Mandiri, BSI), QRIS, kartu kredit, dan e-wallet. Untuk pesanan rombongan berlaku DP 50% dan pelunasan sebelum pengiriman.'],
        ];

        foreach ($faqs as $i => $faq) {
            Faq::create([
                'question' => $faq['q'],
                'answer' => $faq['a'],
                'sort_order' => $i,
            ]);
        }
    }
}
