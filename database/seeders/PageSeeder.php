<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $productIds = Product::where('status', 'active')
            ->pluck('id', 'slug');

        foreach ($this->pages($productIds->all()) as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'status' => $page['status'],
                    'blocks' => $page['blocks'],
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $productIds
     * @return array<int, array<string, mixed>>
     */
    private function pages(array $productIds): array
    {
        return [
            [
                'slug' => 'arafa-travel',
                'title' => 'Arafa untuk Travel',
                'status' => 'publish',
                'blocks' => [
                    $this->hero(
                        'Untuk Agen Travel Umrah & Haji',
                        "Souvenir rombongan\nsiap dibagikan tanpa repot",
                        'Paket oleh-oleh dan gift set yang bisa disesuaikan dengan jumlah jemaah, jadwal keberangkatan, dan identitas travel.',
                        '/images/assets/souvenir-satu-rombongan.webp',
                        'Konsultasi Paket Travel',
                    ),
                    $this->nilai('Yang travel butuhkan', [
                        ['Users', 'Satu rombongan rapi', 'Paket bisa disiapkan per nama, per bus, atau per kloter.'],
                        ['Truck', 'Siap kirim terjadwal', 'Produksi dan pengiriman mengikuti tanggal kepulangan jemaah.'],
                        ['BadgeCheck', 'Bisa pakai logo travel', 'Kartu ucapan, sleeve, dan label dapat disesuaikan dengan branding travel.'],
                    ]),
                    $this->produk(
                        'Paket rombongan',
                        'Pilihan untuk Travel',
                        'Produk yang mudah dihitung per jemaah dan tetap terasa pantas saat diterima keluarga di rumah.',
                        $this->pickProducts($productIds, [
                            'paket-salam-souvenir-rombongan',
                            'kurma-ajwa-premium-500g',
                            'tasbih-kayu-zaitun',
                        ]),
                        [
                            $this->customProduct(
                                'Paket Barokah Travel',
                                'Souvenir Rombongan',
                                38500,
                                '/images/catalog/sarung.jpg',
                                'Min. 100 pcs',
                                'Kurma, tasbih, kartu nama jemaah, dan pouch kraft untuk dibagikan saat kepulangan.',
                            ),
                        ],
                    ),
                    $this->testimoni('Dipercaya penyelenggara perjalanan', [
                        ['H. Farid', 'Owner Travel Umrah', 'Tim Arafa membantu packing per kloter. Barang datang rapi dan tidak membuat tim bandara kami kewalahan.'],
                        ['Nur Aini', 'Koordinator Operasional', 'Desain kartu dan labelnya cepat disetujui. Cocok untuk paket jemaah yang butuh tampilan profesional.'],
                    ]),
                    $this->faq([
                        ['Apakah bisa pesan sesuai jumlah jemaah?', 'Bisa. Paket dapat dibuat mulai dari puluhan sampai ratusan pcs dengan opsi custom label dan kartu.'],
                        ['Kapan sebaiknya order?', 'Idealnya 7-14 hari sebelum tanggal kepulangan agar produksi dan pengiriman lebih aman.'],
                        ['Apakah bisa dikirim ke kantor travel?', 'Bisa, pengiriman dapat diarahkan ke kantor travel, hotel transit, atau alamat koordinator.'],
                    ]),
                    $this->cta('Siapkan souvenir rombongan sebelum jemaah pulang', 'Ceritakan jumlah jemaah dan tanggal kebutuhan. Tim Arafa bantu susun paket yang pas.', 'Chat Admin Travel'),
                ],
            ],
            [
                'slug' => 'arafa-jemaah',
                'title' => 'Arafa untuk Jemaah',
                'status' => 'publish',
                'blocks' => [
                    $this->hero(
                        'Untuk Jemaah dan Keluarga',
                        "Oleh-oleh pulang ibadah\nyang terasa personal",
                        'Pilih hadiah yang rapi, mudah dibawa, dan pantas diberikan kepada orang tua, sahabat, guru, atau keluarga di rumah.',
                        '/images/assets/perjalanan-pulang-membawa-cerita.webp',
                        'Pilih Oleh-oleh',
                    ),
                    $this->nilai('Pulang dengan tenang', [
                        ['Gift', 'Tidak bingung memilih', 'Produk sudah dikurasi untuk kebutuhan oleh-oleh umrah dan haji.'],
                        ['Heart', 'Bisa dibuat personal', 'Tambahkan nama, kartu ucapan, atau pilihan isi sesuai penerima.'],
                        ['Shield', 'Kemasan aman', 'Paket dipilih agar tetap rapi saat dibawa pulang atau dikirim ke rumah.'],
                    ]),
                    $this->produk(
                        'Pilihan jemaah',
                        'Hadiah untuk orang tersayang',
                        'Dari kurma premium sampai gift set keluarga, semuanya siap diberikan tanpa repacking.',
                        $this->pickProducts($productIds, [
                            'arafah-premium-box',
                            'kurma-ajwa-premium-500g',
                            'sajadah-travel-lipat',
                            'tasbih-kayu-zaitun',
                        ]),
                        [],
                    ),
                    $this->testimoni('Cerita dari pelanggan Arafa', [
                        ['Siti Rahmah', 'Jemaah Umrah', 'Saya tinggal pilih paket untuk keluarga. Sampai rumah tinggal dibagikan, kemasannya bagus sekali.'],
                        ['Abdullah', 'Pelanggan', 'Kurma dan tasbihnya terasa premium. Kartu ucapannya bikin hadiah jadi lebih berkesan.'],
                    ]),
                    $this->faq([
                        ['Apakah bisa beli satuan?', 'Bisa. Beberapa produk tersedia satuan, dan beberapa paket dibuat untuk keluarga atau rombongan.'],
                        ['Apakah bisa dikirim ke rumah?', 'Bisa. Pesanan dapat dikirim ke alamat rumah atau alamat penerima hadiah.'],
                        ['Bisa minta rekomendasi sesuai budget?', 'Bisa. Admin akan bantu susun pilihan berdasarkan budget dan jumlah penerima.'],
                    ]),
                    $this->cta('Bawa pulang hadiah yang layak dikenang', 'Pilih penerima dan budget, kami bantu rekomendasikan paket terbaik.', 'Chat untuk Rekomendasi'),
                ],
            ],
            [
                'slug' => 'arafa-exclusive-1',
                'title' => 'Arafa Exclusive 1',
                'status' => 'publish',
                'blocks' => [
                    $this->hero(
                        'Exclusive Series 01',
                        "Hadiah premium\nuntuk keluarga inti",
                        'Gift set elegan untuk orang tua, pasangan, dan keluarga terdekat sepulang dari Tanah Suci.',
                        '/images/assets/gift-worth-remembering.webp',
                        'Pesan Exclusive 1',
                    ),
                    $this->nilai('Kesan premium sejak dibuka', [
                        ['Sparkles', 'Box elegan', 'Kemasan hardcover dengan tampilan hangat dan rapi.'],
                        ['Star', 'Isi pilihan', 'Kurma, tasbih, sajadah, dan kartu ucapan dalam satu paket.'],
                        ['Heart', 'Personal touch', 'Nama penerima dapat ditambahkan pada kartu ucapan.'],
                    ]),
                    $this->produk(
                        'Exclusive 1',
                        'Paket premium personal',
                        'Untuk hadiah yang ingin terasa lebih dekat dan tidak sekadar oleh-oleh.',
                        $this->pickProducts($productIds, [
                            'arafah-premium-box',
                            'madu-sidr-yaman-250g',
                            'tasbih-kayu-zaitun',
                        ]),
                        [],
                    ),
                    $this->cta('Buat hadiah pulang ibadah terasa lebih personal', 'Cocok untuk orang tua, pasangan, dan keluarga inti.', 'Chat Exclusive 1'),
                ],
            ],
            [
                'slug' => 'arafa-exclusive-2',
                'title' => 'Arafa Exclusive 2',
                'status' => 'publish',
                'blocks' => [
                    $this->hero(
                        'Exclusive Series 02',
                        "Set keluarga besar\nyang siap dibagi",
                        'Paket bernilai lebih besar untuk keluarga, kerabat dekat, atau tamu penting yang ingin diberi hadiah istimewa.',
                        '/images/assets/section-lebih-dari-oleh-oleh.webp',
                        'Pesan Exclusive 2',
                    ),
                    $this->nilai('Untuk momen yang lebih besar', [
                        ['Users', 'Isi lebih lengkap', 'Cukup untuk beberapa penerima dalam satu keluarga.'],
                        ['Gift', 'Mudah dibagikan', 'Item dapat dipisah dan diberikan sesuai kebutuhan.'],
                        ['BadgeCheck', 'Tampil pantas', 'Setiap item tetap terlihat rapi saat diterima.'],
                    ]),
                    $this->produk(
                        'Exclusive 2',
                        'Paket keluarga besar',
                        'Pilihan untuk penerima lebih banyak tanpa kehilangan kesan premium.',
                        $this->pickProducts($productIds, [
                            'family-gift-set',
                            'kurma-sukkari-royal-1kg',
                            'sajadah-travel-lipat',
                            'parfum-oud-attar-6ml',
                        ]),
                        [],
                    ),
                    $this->faq([
                        ['Apakah isinya bisa diganti?', 'Bisa, selama stok tersedia. Admin akan bantu menyesuaikan isi paket.'],
                        ['Apakah bisa kirim ke beberapa alamat?', 'Bisa dibantu, terutama untuk pesanan keluarga atau corporate gift.'],
                    ]),
                    $this->cta('Siapkan hadiah keluarga besar tanpa bongkar-pasang sendiri', 'Kami bantu susun paket agar rapi sejak diterima.', 'Chat Exclusive 2'),
                ],
            ],
            [
                'slug' => 'arafa-exclusive-3',
                'title' => 'Arafa Exclusive 3',
                'status' => 'publish',
                'blocks' => [
                    $this->hero(
                        'Exclusive Series 03',
                        "Paket kehormatan\nuntuk tamu istimewa",
                        'Pilihan premium untuk guru, relasi, tokoh keluarga, atau tamu kehormatan yang membutuhkan tampilan lebih eksklusif.',
                        '/images/assets/img-4.webp',
                        'Pesan Exclusive 3',
                    ),
                    $this->nilai('Lebih berkelas, tetap hangat', [
                        ['Shield', 'Kurasi premium', 'Isi dipilih untuk tampilan yang lebih formal dan bernilai.'],
                        ['Star', 'Detail rapi', 'Kemasan dan kartu ucapan dirancang untuk kesan pertama yang kuat.'],
                        ['Send', 'Bisa langsung kirim', 'Hadiah dapat dikirim langsung ke penerima dengan catatan personal.'],
                    ]),
                    $this->produk(
                        'Exclusive 3',
                        'Paket tamu kehormatan',
                        'Untuk pemberian yang harus terlihat serius, sopan, dan berkesan.',
                        $this->pickProducts($productIds, [
                            'sajadah-turki-mihrab',
                            'madu-sidr-yaman-250g',
                            'kurma-ajwa-premium-500g',
                            'tasbih-kristal-amber',
                        ]),
                        [
                            $this->customProduct(
                                'Arafa Signature Hamper',
                                'Exclusive Hamper',
                                875000,
                                '/images/catalog/bhukur.jpg',
                                'Signature',
                                'Hamper khusus berisi pilihan premium dengan kartu ucapan personal dan tampilan eksklusif.',
                            ),
                        ],
                    ),
                    $this->testimoni('Untuk pemberian yang lebih resmi', [
                        ['Hj. Maryam', 'Pelanggan Corporate', 'Kami pakai untuk tamu undangan keluarga. Tampilannya pantas dan tidak perlu repacking lagi.'],
                        ['Rizal', 'Pelanggan', 'Admin membantu pilih isi yang cocok untuk guru ngaji saya. Hasilnya elegan dan sopan.'],
                    ]),
                    $this->cta('Kirim hadiah kehormatan dengan tampilan terbaik', 'Sampaikan kebutuhan dan penerima, kami bantu pilihkan komposisi yang pas.', 'Chat Exclusive 3'),
                ],
            ],
        ];
    }

    private function hero(string $eyebrow, string $headline, string $sub, string $image, string $label): array
    {
        return [
            'type' => 'hero',
            'enabled' => true,
            'content' => [
                'eyebrow' => $eyebrow,
                'headline' => $headline,
                'sub' => $sub,
                'image' => $image,
                'cta' => ['label' => $label, 'href' => ''],
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $items
     */
    private function nilai(string $title, array $items): array
    {
        return [
            'type' => 'nilai',
            'enabled' => true,
            'content' => [
                'title' => $title,
                'items' => array_map(fn (array $item): array => [
                    'icon' => $item[0],
                    'image' => '',
                    'title' => $item[1],
                    'body' => $item[2],
                ], $items),
            ],
        ];
    }

    /**
     * @param  array<int, int>  $productIds
     * @param  array<int, array<string, mixed>>  $customItems
     */
    private function produk(string $eyebrow, string $title, string $intro, array $productIds, array $customItems): array
    {
        return [
            'type' => 'produk_unggulan',
            'enabled' => true,
            'content' => [
                'eyebrow' => $eyebrow,
                'title' => $title,
                'intro' => $intro,
                'productIds' => $productIds,
                'customItems' => $customItems,
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string}>  $items
     */
    private function testimoni(string $title, array $items): array
    {
        return [
            'type' => 'testimoni',
            'enabled' => true,
            'content' => [
                'title' => $title,
                'items' => array_map(fn (array $item): array => [
                    'name' => $item[0],
                    'role' => $item[1],
                    'quote' => $item[2],
                    'avatar' => '',
                ], $items),
            ],
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $items
     */
    private function faq(array $items): array
    {
        return [
            'type' => 'faq',
            'enabled' => true,
            'content' => [
                'title' => 'Pertanyaan yang sering ditanyakan',
                'items' => array_map(fn (array $item): array => [
                    'q' => $item[0],
                    'a' => $item[1],
                ], $items),
            ],
        ];
    }

    private function cta(string $headline, string $sub, string $label): array
    {
        return [
            'type' => 'cta',
            'enabled' => true,
            'content' => [
                'headline' => $headline,
                'sub' => $sub,
                'note' => 'Admin Arafa siap bantu hitungkan pilihan paket.',
                'cta' => ['label' => $label, 'href' => ''],
            ],
        ];
    }

    private function customProduct(
        string $name,
        string $category,
        int $price,
        string $image,
        string $badge,
        string $description,
    ): array {
        return [
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'comparePrice' => null,
            'image' => $image,
            'badge' => $badge,
            'rating' => 4.9,
            'reviews' => 37,
            'description' => $description,
            'includes' => [
                ['value' => 'Pilihan isi sesuai kebutuhan'],
                ['value' => 'Kemasan hadiah rapi'],
                ['value' => 'Kartu ucapan personal'],
            ],
            'details' => [
                ['label' => 'Minimum order', 'value' => 'Konsultasikan dengan admin'],
                ['label' => 'Custom label', 'value' => 'Tersedia'],
            ],
        ];
    }

    /**
     * @param  array<string, int>  $productIds
     * @param  array<int, string>  $slugs
     * @return array<int, int>
     */
    private function pickProducts(array $productIds, array $slugs): array
    {
        return array_values(array_filter(
            array_map(fn (string $slug): ?int => $productIds[$slug] ?? null, $slugs),
        ));
    }
}
