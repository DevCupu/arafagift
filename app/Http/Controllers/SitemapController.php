<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * /sitemap.xml adalah satu-satunya peta URL yang dibaca Google untuk toko ini.
 * Karena itu endpoint ini tidak boleh pernah 500: satu baris produk yang
 * bermasalah lebih baik membuat sitemap berisi URL statis saja daripada membuat
 * seluruh toko tidak terdiscover. Perubahan katalog di panel admin sudah
 * meng-invalidasi cache ini lewat SitemapController::flush().
 */
class SitemapController extends Controller
{
    /**
     * Versi ikut naik setiap kali bentuk entri cache berubah. Key lama masih
     * hidup di cache file/redis produksi sampai 24-48 jam, dan entri lamanya
     * tidak punya kunci yang dipakai view sekarang.
     *
     * Ini bukan hypothetis:_entri basi dengan bentuk lama sempat membuat
     * /sitemap.xml membalas 500 karena "Undefined array key" di view. Karena
     * error itu tidak muncul di test (PHPUnit memakai error handler sendiri),
     * bentuk cache harus dijaga lewat nomor versi, bukan hope.
     */
    private const CACHE_KEY = 'sitemap-urls-v2';

    /**
     * Dipanggil panel admin dan CacheWarm setiap kali katalog berubah.
     * Sengaja ada method ini supaya nama key hanya hidup di satu tempat â€”
     * Kalau key ditulis ulang di beberapa pemanggil, lupa satu di antaranya
     * berarti sitemap tetap menyajikan URL produk yang sudah dihapus.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function index(): Response
    {
        try {
            $xml = view('sitemap', ['urls' => self::urls()])->render();
        } catch (Throwable $e) {
            // Lapisan paling luar. Guard di catalogUrls() hanya menutup masalah
            // di dalam query katalog; view dan cache di luar sana belum
            // tersentuh. Endpoint ini tidak boleh 500 dalam keadaan apa pun,
            // karena satu halaman error di sini berarti Google kehilangan peta
            // URL seluruh toko.
            Log::error('Sitemap gagal dirender, memakai fallback statis.', [
                'message' => $e->getMessage(),
            ]);

            // Fallback TIDAK memakai view() lagi — kalau compiled view rusak
            // (mis. syntax error dari cache lama), view fallback pun akan gagal.
            // Generate XML langsung di PHP agar selalu 200 tanpa Blade sama sekali.
            $xml = self::buildXml(self::staticUrls());
        }

        return response($xml, 200)->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Generate sitemap XML tanpa Blade — aman dipakai saat template corrupt.
     *
     * @param array<int, array{loc: string, changefreq?: string, priority?: string, lastmod?: string}> $urls
     */
    private static function buildXml(array $urls): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url['loc'] ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>';
            if (! empty($url['lastmod'])) {
                $lines[] = '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8').'</lastmod>';
            }
            if (! empty($url['changefreq'])) {
                $lines[] = '    <changefreq>'.htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_COMPAT, 'UTF-8').'</changefreq>';
            }
            if (! empty($url['priority'])) {
                $lines[] = '    <priority>'.htmlspecialchars($url['priority'], ENT_XML1 | ENT_COMPAT, 'UTF-8').'</priority>';
            }
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /akun',
            'Disallow: /checkout',
            'Disallow: /lacak-pesanan',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    /**
     * @return array<int, array{loc: string, lastmod?: string, changefreq: string, priority: string}>
     */
    public static function urls(): array
    {
        return Cache::flexible(self::CACHE_KEY, [86400, 172800], function (): array {
            $urls = self::staticUrls();

            foreach (self::catalogUrls() as $url) {
                $urls[] = $url;
            }

            // Kategori tanpa produk aktif, atau yang slug-nya bentrok dengan
            // /koleksi, akan menghasilkan <loc> dobel. Google mengabaikan
            // duplikat tapi tetap menghitungnya sebagai error di Search Console.
            return array_values(array_reduce(
                $urls,
                function (array $carry, array $url): array {
                    $carry[$url['loc']] ??= $url;

                    return $carry;
                },
                [],
            ));
        });
    }

    /**
     * @return array<int, array{loc: string, changefreq: string, priority: string}>
     */
    private static function staticUrls(): array
    {
        return [
            self::url(route('home'), 'daily', '1.0'),
            self::url(route('collection'), 'daily', '0.9'),
            self::url(route('about'), 'monthly', '0.5'),
            self::url(route('faq'), 'monthly', '0.5'),
            self::url(route('legal', ['slug' => 'kebijakan-privasi']), 'yearly', '0.3'),
            self::url(route('legal', ['slug' => 'syarat-ketentuan']), 'yearly', '0.3'),
            self::url(route('legal', ['slug' => 'pengiriman-pengembalian']), 'yearly', '0.3'),
        ];
    }

    /**
     * @return array<int, array{loc: string, changefreq: string, priority: string, lastmod?: string}>
     */
    private static function catalogUrls(): array
    {
        try {
            $urls = [];

            // cursor(), bukan get(): daftar produk bisa tumbuh jauh melewati
            // memory_limit shared hosting, dan sitemap tidak butuh model penuh.
            foreach (Category::query()->select(['id', 'slug'])->orderBy('id')->cursor() as $category) {
                $urls[] = self::url(route('collection', ['category' => $category->slug]), 'weekly', '0.8');
            }

            foreach (Product::query()
                ->select(['id', 'slug', 'updated_at'])
                ->where('status', 'active')
                ->orderBy('id')
                ->cursor() as $product) {
                $url = self::url(route('product', ['product' => $product->slug]), 'weekly', '0.7');

                if ($product->updated_at !== null) {
                    $url['lastmod'] = $product->updated_at->toAtomString();
                }

                $urls[] = $url;
            }

            return $urls;
        } catch (Throwable $e) {
            // Log supaya penyebabnya terlihat di sana, tapi tetap balas sitemap
            // yang valid. Endpoint yang balas 200XML seadanya jauh lebih
            // berguna daripada yang balas 500.
            Log::error('Sitemap katalog gagal dibangun, turun ke URL statis saja.', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array{loc: string, changefreq: string, priority: string}
     */
    private static function url(string $loc, string $changefreq, string $priority): array
    {
        return [
            // route() sudah menghasilkan URL tanpa query, tapi jaga ulangannya
            // tetap murni karena sitemap ditelan Google apa adanya.
            'loc' => Str::before($loc, '?'),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
