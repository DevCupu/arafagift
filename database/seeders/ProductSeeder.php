<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const ART_IMAGE = [
        'kurma' => '/images/catalog/kurma.jpg',
        'sajadah' => '/images/catalog/sajadah.jpg',
        'tasbih' => '/images/catalog/tasbih.jpg',
        'giftset' => '/images/catalog/bhukur.jpg',
        'madu' => '/images/catalog/kalung.jpg',
        'souvenir' => '/images/catalog/sarung.jpg',
        'parfum' => '/images/catalog/kalung.jpg',
        'kacang' => '/images/catalog/bhukur.jpg',
    ];

    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'slug');
        $occasionIds = Occasion::pluck('id', 'slug');

        $products = [
            [
                'name' => 'Arafah Premium Box', 'slug' => 'arafah-premium-box', 'sku' => 'AGF-BOX-01',
                'category_slug' => 'gift-set', 'price' => 649000, 'compare_price' => 749000, 'cost' => 410000,
                'rating' => 4.9, 'reviews_count' => 214, 'badge' => 'Paling dicari', 'art' => 'giftset',
                'stock' => 34, 'low_stock_threshold' => 10, 'weight' => 1800, 'status' => 'active', 'featured' => true,
                'occasions' => ['keluarga', 'orang-tua', 'guru'],
                'short' => 'Satu box hardcover berisi empat hadiah pilihan — cukup untuk mewakili satu perjalanan.',
                'description' => 'Arafah Premium Box lahir dari pertanyaan yang paling sering kami dengar: "bawa apa untuk di rumah?". Isinya kami susun supaya satu box terasa lengkap tanpa berlebihan — ada yang dimakan bersama, ada yang dipakai setiap hari, dan ada satu kartu yang membuat pemberian ini punya nama.',
                'includes' => ['Kurma Ajwa premium 250 g', 'Tasbih kayu zaitun 33 butir', 'Sajadah travel lipat', 'Kartu ucapan tulis tangan'],
                'details' => [['Dimensi box', '30 × 22 × 9 cm'], ['Material', 'Hardcover linen, foil emas'], ['Berat kirim', '1,8 kg'], ['Custom nama', 'Tersedia, bebas biaya']],
            ],
            [
                'name' => 'Kurma Ajwa Premium 500 g', 'slug' => 'kurma-ajwa-premium-500g', 'sku' => 'AGF-KUR-01',
                'category_slug' => 'kurma', 'price' => 285000, 'compare_price' => null, 'cost' => 190000,
                'rating' => 4.8, 'reviews_count' => 386, 'badge' => 'Best seller', 'art' => 'kurma',
                'stock' => 120, 'low_stock_threshold' => 25, 'weight' => 600, 'status' => 'active', 'featured' => true,
                'occasions' => ['sahabat', 'keluarga', 'tamu'],
                'short' => 'Ajwa Madinah grade A, daging tebal, dikemas ulang di ruang bersih.',
                'description' => 'Kurma Ajwa dari kebun Madinah, disortir manual dan dikemas dalam toples kaca gelap agar teksturnya tetap lembut sampai di rumah. Rasanya tidak terlalu manis — karamel, sedikit rempah.',
                'includes' => ['Toples kaca 500 g', 'Sleeve kertas daur ulang', 'Kartu ucapan'],
                'details' => [['Asal', 'Madinah, Arab Saudi'], ['Grade', 'A — sortir manual'], ['Simpan', 'Suhu ruang, hindari matahari'], ['Kedaluwarsa', '12 bulan sejak kemas']],
            ],
            [
                'name' => 'Sajadah Travel Lipat', 'slug' => 'sajadah-travel-lipat', 'sku' => 'AGF-SAJ-01',
                'category_slug' => 'sajadah', 'price' => 189000, 'compare_price' => 219000, 'cost' => 112000,
                'rating' => 4.7, 'reviews_count' => 141, 'badge' => null, 'art' => 'sajadah',
                'stock' => 58, 'low_stock_threshold' => 15, 'weight' => 420, 'status' => 'active', 'featured' => true,
                'occasions' => ['sahabat', 'guru', 'orang-tua'],
                'short' => 'Setipis syal, sebesar buku saat dilipat. Untuk yang sering di jalan.',
                'description' => 'Ditenun dari katun rayon dengan lapisan anti-selip tipis. Dilipat jadi seukuran buku saku dan masuk ke kantong pouch yang ikut dalam paket.',
                'includes' => ['Sajadah 100 × 60 cm', 'Pouch katun', 'Kartu ucapan'],
                'details' => [['Material', 'Katun rayon, anti-selip'], ['Ukuran', '100 × 60 cm'], ['Perawatan', 'Cuci tangan, jangan diperas'], ['Warna', 'Olive, sand, deep green']],
            ],
            [
                'name' => 'Tasbih Kayu Zaitun', 'slug' => 'tasbih-kayu-zaitun', 'sku' => 'AGF-TAS-01',
                'category_slug' => 'tasbih', 'price' => 95000, 'compare_price' => null, 'cost' => 48000,
                'rating' => 4.9, 'reviews_count' => 302, 'badge' => null, 'art' => 'tasbih',
                'stock' => 8, 'low_stock_threshold' => 20, 'weight' => 90, 'status' => 'active', 'featured' => true,
                'occasions' => ['guru', 'sahabat', 'rombongan'],
                'short' => 'Kayu zaitun Baitul Maqdis, 33 butir, aromanya bertahan bertahun-tahun.',
                'description' => 'Dibubut dari kayu zaitun tua. Setiap butir punya urat yang berbeda, jadi tidak ada dua tasbih yang benar-benar sama. Semakin sering dipakai, warnanya semakin dalam.',
                'includes' => ['Tasbih 33 butir', 'Kantong beludru', 'Kartu ucapan'],
                'details' => [['Material', 'Kayu zaitun'], ['Jumlah butir', '33'], ['Diameter butir', '8 mm'], ['Finishing', 'Minyak alami, tanpa pernis']],
            ],
            [
                'name' => 'Family Gift Set', 'slug' => 'family-gift-set', 'sku' => 'AGF-BOX-02',
                'category_slug' => 'gift-set', 'price' => 1250000, 'compare_price' => 1420000, 'cost' => 820000,
                'rating' => 4.9, 'reviews_count' => 88, 'badge' => 'Untuk satu rumah', 'art' => 'giftset',
                'stock' => 12, 'low_stock_threshold' => 6, 'weight' => 4200, 'status' => 'active', 'featured' => true,
                'occasions' => ['keluarga', 'orang-tua'],
                'short' => 'Delapan item dalam satu koper kardus — cukup untuk dibagi satu rumah.',
                'description' => 'Dibuat untuk keluarga besar: dua toples kurma, dua sajadah, tiga tasbih, dan madu. Semua sudah dipisah dalam kantong kecil sehingga mudah dibagikan tanpa perlu dibongkar ulang.',
                'includes' => ['Kurma Ajwa & Sukkari, masing-masing 500 g', '2 sajadah travel', '3 tasbih kayu zaitun', 'Madu Sidr 250 g', '4 kartu ucapan'],
                'details' => [['Dimensi box', '42 × 30 × 14 cm'], ['Isi', '8 item'], ['Berat kirim', '4,2 kg'], ['Custom nama', 'Tersedia, bebas biaya']],
            ],
            [
                'name' => 'Madu Sidr Yaman 250 g', 'slug' => 'madu-sidr-yaman-250g', 'sku' => 'AGF-MAD-01',
                'category_slug' => 'oleh-oleh', 'price' => 320000, 'compare_price' => null, 'cost' => 205000,
                'rating' => 4.8, 'reviews_count' => 96, 'badge' => null, 'art' => 'madu',
                'stock' => 41, 'low_stock_threshold' => 12, 'weight' => 400, 'status' => 'active', 'featured' => true,
                'occasions' => ['orang-tua', 'tamu'],
                'short' => 'Panen musim gugur, kental, aroma kayu dan karamel.',
                'description' => 'Madu Sidr murni dari lembah Hadramaut. Kental sampai sendok berdiri, dengan rasa yang lebih hangat dan sedikit pahit di ujung dibanding madu bunga biasa.',
                'includes' => ['Botol kaca 250 g', 'Sendok kayu', 'Kartu ucapan'],
                'details' => [['Asal', 'Hadramaut, Yaman'], ['Panen', 'Musim gugur'], ['Isi', '250 g'], ['Sertifikat', 'Uji lab keaslian']],
            ],
            [
                'name' => 'Kurma Sukkari Royal 1 kg', 'slug' => 'kurma-sukkari-royal-1kg', 'sku' => 'AGF-KUR-02',
                'category_slug' => 'kurma', 'price' => 175000, 'compare_price' => 210000, 'cost' => 108000,
                'rating' => 4.7, 'reviews_count' => 174, 'badge' => null, 'art' => 'kurma',
                'stock' => 3, 'low_stock_threshold' => 20, 'weight' => 1100, 'status' => 'active', 'featured' => false,
                'occasions' => ['keluarga', 'rombongan', 'tamu'],
                'short' => 'Lembut, manis karamel, paling aman untuk semua umur.',
                'description' => 'Sukkari dari Qassim dengan tekstur basah yang lumer. Kemasan 1 kg cocok untuk dibagi ulang menjadi paket-paket kecil.',
                'includes' => ['Kemasan 1 kg', 'Kantong hadiah', 'Kartu ucapan'],
                'details' => [['Asal', 'Qassim, Arab Saudi'], ['Tekstur', 'Lembut, basah'], ['Isi', '1 kg'], ['Kedaluwarsa', '10 bulan']],
            ],
            [
                'name' => 'Sajadah Turki Mihrab', 'slug' => 'sajadah-turki-mihrab', 'sku' => 'AGF-SAJ-02',
                'category_slug' => 'sajadah', 'price' => 425000, 'compare_price' => null, 'cost' => 260000,
                'rating' => 4.9, 'reviews_count' => 64, 'badge' => 'Edisi terbatas', 'art' => 'sajadah',
                'stock' => 0, 'low_stock_threshold' => 8, 'weight' => 1300, 'status' => 'active', 'featured' => false,
                'occasions' => ['orang-tua', 'guru'],
                'short' => 'Tenun Turki tebal dengan motif mihrab klasik dan bordir emas tipis.',
                'description' => 'Ditenun di Kayseri dengan kepadatan tinggi sehingga empuk saat sujud dan tidak licin. Motif mihrabnya sengaja dibiarkan polos di bagian tengah.',
                'includes' => ['Sajadah 110 × 70 cm', 'Kotak hadiah', 'Kartu ucapan'],
                'details' => [['Asal', 'Kayseri, Turki'], ['Material', 'Chenille, katun'], ['Ukuran', '110 × 70 cm'], ['Berat', '1,3 kg']],
            ],
            [
                'name' => 'Tasbih Kristal Amber', 'slug' => 'tasbih-kristal-amber', 'sku' => 'AGF-TAS-02',
                'category_slug' => 'tasbih', 'price' => 145000, 'compare_price' => null, 'cost' => 78000,
                'rating' => 4.6, 'reviews_count' => 57, 'badge' => null, 'art' => 'tasbih',
                'stock' => 27, 'low_stock_threshold' => 10, 'weight' => 120, 'status' => 'active', 'featured' => false,
                'occasions' => ['sahabat', 'tamu'],
                'short' => 'Butiran kaca amber yang menangkap cahaya sore.',
                'description' => 'Butiran kaca tebal warna amber dengan tali sutra hijau tua. Terasa dingin dan berbobot di tangan.',
                'includes' => ['Tasbih 33 butir', 'Kantong beludru', 'Kartu ucapan'],
                'details' => [['Material', 'Kaca amber'], ['Jumlah butir', '33'], ['Tali', 'Sutra hijau tua'], ['Berat', '120 g']],
            ],
            [
                'name' => 'Paket Salam — Souvenir Rombongan', 'slug' => 'paket-salam-souvenir-rombongan', 'sku' => 'AGF-SOU-01',
                'category_slug' => 'souvenir-rombongan', 'price' => 27500, 'compare_price' => null, 'cost' => 15000,
                'rating' => 4.8, 'reviews_count' => 39, 'badge' => 'Min. 50 pcs', 'art' => 'souvenir',
                'stock' => 640, 'low_stock_threshold' => 100, 'weight' => 160, 'status' => 'active', 'featured' => false,
                'occasions' => ['rombongan', 'tamu'],
                'short' => 'Kurma 6 butir + tasbih + kartu nama jamaah, dalam pouch kecil.',
                'description' => 'Dirancang untuk dibagikan sepulang dari bandara. Kami cetak nama jamaah dan tanggal keberangkatan di kartu, jadi penerima tahu ini titipan dari siapa.',
                'includes' => ['Kurma 6 butir', 'Tasbih 33 butir', 'Kartu nama jamaah', 'Pouch kraft'],
                'details' => [['Minimum order', '50 pcs'], ['Waktu produksi', '3–5 hari kerja'], ['Custom kartu', 'Gratis, termasuk logo travel'], ['Berat/pcs', '160 g']],
            ],
            [
                'name' => 'Parfum Oud Attar 6 ml', 'slug' => 'parfum-oud-attar-6ml', 'sku' => 'AGF-PAR-01',
                'category_slug' => 'oleh-oleh', 'price' => 135000, 'compare_price' => null, 'cost' => 62000,
                'rating' => 4.5, 'reviews_count' => 71, 'badge' => null, 'art' => 'parfum',
                'stock' => 76, 'low_stock_threshold' => 20, 'weight' => 80, 'status' => 'active', 'featured' => false,
                'occasions' => ['sahabat', 'guru', 'tamu'],
                'short' => 'Attar non-alkohol, oud dan mawar, sekali oles bertahan seharian.',
                'description' => 'Minyak wangi pekat tanpa alkohol dalam botol kaca ukir. Wanginya berat di awal lalu turun jadi mawar dan cendana.',
                'includes' => ['Botol kaca 6 ml', 'Kotak beludru', 'Kartu ucapan'],
                'details' => [['Isi', '6 ml'], ['Base', 'Minyak, non-alkohol'], ['Aroma', 'Oud, mawar, cendana'], ['Ketahanan', '8–10 jam']],
            ],
            [
                'name' => 'Kacang Arab Panggang 400 g', 'slug' => 'kacang-arab-panggang-400g', 'sku' => 'AGF-OLE-01',
                'category_slug' => 'oleh-oleh', 'price' => 68000, 'compare_price' => null, 'cost' => 34000,
                'rating' => 4.4, 'reviews_count' => 52, 'badge' => null, 'art' => 'kacang',
                'stock' => 92, 'low_stock_threshold' => 25, 'weight' => 500, 'status' => 'draft', 'featured' => false,
                'occasions' => ['keluarga', 'tamu'],
                'short' => 'Dipanggang tanpa minyak, asin tipis, renyah sampai toples habis.',
                'description' => 'Kacang arab panggang klasik yang biasa dibagi saat kumpul keluarga. Kami kurangi garamnya supaya rasa kacangnya lebih keluar.',
                'includes' => ['Pouch zip 400 g', 'Kartu ucapan'],
                'details' => [['Isi', '400 g'], ['Proses', 'Panggang, tanpa minyak'], ['Kedaluwarsa', '6 bulan'], ['Alergen', 'Kacang-kacangan']],
            ],
        ];

        foreach ($products as $data) {
            $occasionSlugs = $data['occasions'];
            $categorySlug = $data['category_slug'];
            unset($data['occasions'], $data['category_slug']);

            $data['category_id'] = $categoryIds[$categorySlug];
            $data['image'] = self::ART_IMAGE[$data['art']];

            $product = Product::create($data);
            $product->occasions()->attach(array_map(fn ($slug) => $occasionIds[$slug], $occasionSlugs));

            if (($product->stock ?? 0) > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'initial',
                    'delta' => $product->stock,
                    'balance_before' => 0,
                    'balance_after' => $product->stock,
                    'note' => 'Saldo awal data contoh',
                    'document_number' => sprintf('IN-%s-%04d', now()->format('Ymd'), $product->id),
                ]);
            }
        }
    }
}
