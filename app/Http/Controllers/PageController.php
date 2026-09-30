<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Faq;
use App\Support\Seo\PageSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function about(): Response
    {
        $content = Cache::flexible(
            'home-content',
            [600, 3600],
            fn () => Content::where('key', 'home')->firstOrFail()->data,
        );

        return Inertia::render('shop/AboutPage', [
            'content' => $content,
            'seoHead' => PageSeo::make(
                'Tentang Arafagift — Toko Oleh-oleh Haji & Umrah',
                'Cerita di balik Arafagift: bagaimana kami memilih, mengemas, dan menuliskan kartu untuk oleh-oleh haji dan umrah yang kembali ke Makassar.',
            )
                ->canonical(route('about'))
                ->toArray(),
        ]);
    }

    public function faq(): Response
    {
        $faqs = Cache::flexible('faq-list', [600, 3600], fn (): array => Faq::orderBy('sort_order')
            ->get()
            ->map(fn (Faq $faq): array => ['q' => $faq->question, 'a' => $faq->answer])
            ->values()
            ->all());

        return Inertia::render('shop/FaqPage', [
            'faqs' => $faqs,
            'seoHead' => PageSeo::make(
                'FAQ Oleh-oleh Haji & Umrah',
                'Pertanyaan yang sering masuk: kurma mana yang cocok, cara pemesanan, ongkir, garansi, dan kebijakan pengembalian Arafagift.',
            )
                ->canonical(route('faq'))
                ->jsonLd([
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_values(array_map(
                        fn (array $faq): array => [
                            '@type' => 'Question',
                            'name' => $faq['q'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $faq['a'],
                            ],
                        ],
                        array_filter($faqs, fn (array $faq): bool => trim((string) ($faq['q'] ?? '')) !== ''),
                    )),
                ])
                ->toArray(),
        ]);
    }

    public function legal(Request $request, string $slug): Response
    {
        $pages = $this->legalPages();

        if (! isset($pages[$slug])) {
            throw new NotFoundHttpException;
        }

        return Inertia::render('shop/LegalPage', [
            'title' => $pages[$slug]['title'],
            'updated' => $pages[$slug]['updated'],
            'sections' => $pages[$slug]['sections'],
            'seoHead' => PageSeo::make(
                sprintf('%s — %s', $pages[$slug]['title'], PageSeo::BRAND),
                sprintf('Dokumen %s Arafagift. Terakhir diperbarui %s.', mb_strtolower($pages[$slug]['title']), $pages[$slug]['updated']),
            )
                ->canonical(route('legal', $slug))
                ->toArray(),
        ]);
    }

    /**
     * @return array<string, array{title: string, updated: string, sections: array<int, array{heading: string, body: string}>}>
     */
    private function legalPages(): array
    {
        return [
            'kebijakan-privasi' => [
                'title' => 'Kebijakan Privasi',
                'updated' => '29 Agustus 2026',
                'sections' => [
                    [
                        'heading' => 'Data yang kami kumpulkan',
                        'body' => 'Saat Anda memesan, kami meminta nama, nomor WhatsApp, alamat pengiriman, kode pos, dan email (opsional). Untuk checkout, Anda perlu masuk atau membuat akun terlebih dahulu — ini membantu kami mengonfirmasi dan melacak pesanan Anda dengan lebih akurat.',
                    ],
                    [
                        'heading' => 'Bagaimana data digunakan',
                        'body' => 'Data yang Anda berikan dipakai untuk memproses pesanan, mengirim konfirmasi via WhatsApp, dan mengirim barang ke alamat Anda. Kami tidak menjual atau membagikan data Anda ke pihak ketiga untuk kepentingan iklan.',
                    ],
                    [
                        'heading' => 'Penyimpanan & keamanan',
                        'body' => 'Data pesanan disimpan di server kami dan hanya dapat diakses oleh admin toko yang berwenang. Kami berupaya menjaga keamanannya, namun tidak ada sistem yang bebas risiko 100%.',
                    ],
                    [
                        'heading' => 'Hak Anda',
                        'body' => 'Anda berhak meminta salinan, koreksi, atau penghapusan data pesanan Anda kapan saja dengan menghubungi kami via WhatsApp.',
                    ],
                ],
            ],
            'syarat-ketentuan' => [
                'title' => 'Syarat & Ketentuan',
                'updated' => '29 Agustus 2026',
                'sections' => [
                    [
                        'heading' => 'Pemesanan',
                        'body' => 'Pesanan dibuat lewat halaman checkout di situs ini, lalu dikonfirmasi ulang oleh admin kami via WhatsApp. Harga dan ketersediaan stok yang berlaku adalah yang tertera saat pesanan dibuat; perubahan harga di kemudian hari tidak berlaku surut ke pesanan yang sudah dikonfirmasi.',
                    ],
                    [
                        'heading' => 'Pembayaran',
                        'body' => 'Saat ini pembayaran dilakukan secara manual (transfer bank/e-wallet) berdasarkan instruksi yang kami kirim via WhatsApp setelah pesanan dikonfirmasi. Kami belum menerima pembayaran otomatis lewat payment gateway di situs.',
                    ],
                    [
                        'heading' => 'Pembatalan',
                        'body' => 'Pesanan dapat dibatalkan tanpa biaya selama belum berstatus "Diproses" atau "Dikirim". Hubungi WhatsApp kami dengan menyertakan nomor pesanan Anda.',
                    ],
                    [
                        'heading' => 'Perubahan ketentuan',
                        'body' => 'Kami dapat memperbarui syarat & ketentuan ini sewaktu-waktu. Perubahan berlaku untuk pesanan baru setelah tanggal pembaruan.',
                    ],
                ],
            ],
            'pengiriman-pengembalian' => [
                'title' => 'Pengiriman & Pengembalian',
                'updated' => '29 Agustus 2026',
                'sections' => [
                    [
                        'heading' => 'Pengiriman',
                        'body' => 'Ongkos kirim belum kami hitung otomatis di situs — admin kami menghitung dan mengonfirmasinya via WhatsApp setelah pesanan dibuat, menyesuaikan kota tujuan dan kurir yang tersedia. Estimasi waktu kirim mengikuti jasa kurir yang dipilih.',
                    ],
                    [
                        'heading' => 'Pengemasan',
                        'body' => 'Setiap pesanan mendapat kartu ucapan tulisan tangan gratis (opsional) dan Anda bisa meminta nota harga disembunyikan di dalam paket saat checkout.',
                    ],
                    [
                        'heading' => 'Barang rusak atau tidak sesuai',
                        'body' => 'Kalau barang Anda diterima dalam kondisi rusak atau tidak sesuai pesanan, laporkan ke WhatsApp kami maksimal 2×24 jam setelah paket diterima, sertakan foto/video kondisi barang dan kemasannya. Kami akan bantu proses penggantian atau pengembalian dana sesuai kesepakatan.',
                    ],
                    [
                        'heading' => 'Yang tidak bisa dikembalikan',
                        'body' => 'Karena berupa makanan (mis. kurma) atau barang yang sudah dipersonalisasi (kartu ucapan), produk ini hanya bisa dikembalikan jika terbukti rusak atau salah kirim dari pihak kami.',
                    ],
                ],
            ],
        ];
    }
}
