<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Product;
use App\Support\Seo\PageSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CollectionController extends Controller
{
    public function index(Request $request, ?string $category = null): Response|RedirectResponse
    {
        $payload = self::payload();
        $category = $category ?? 'semua';

        if ($category !== 'semua') {
            $match = self::matchCategory($payload['categories'], $category);

            // Slug tak dikenal harus jadi 404 sungguhan, bukan 200 dengan
            // katalog kosong seperti yang terjadi di /koleksi/gift-set.
            abort_if($match === null, 404);

            // Kategori produksi pernah tersimpan dengan slug berkapital
            // ("Cemilan", "Parfum"), sehingga /koleksi/Cemilan menyajikan
            // isi yang sama persis dengan /koleksi/kurma tapi dibaca sebagai
            // dua URL berbeda. Redirect 301, bukan canonical silang,
            // memindahkan seluruh sinyal ke versi kanonik satu pintu.
            if ($match['slug'] !== $category) {
                return redirect()->route(
                    'collection',
                    ['category' => $match['slug']] + $request->query(),
                    301,
                );
            }
        }

        return Inertia::render('shop/CollectionPage', [
            ...$payload,
            'category' => $category,
            'untuk' => $request->query('untuk'),
            'seoHead' => self::seoHead($payload['categories'], $category, $request->query('untuk')),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return list<string>
     */
    public static function seoHead(array $categories, string $category, ?string $occasion): array
    {
        $match = $category === 'semua' ? null : self::matchCategory($categories, $category);
        $canonical = $category === 'semua'
            ? route('collection')
            : route('collection', ['category' => $category]);

        if ($match === null) {
            $seo = PageSeo::make(
                'Semua Produk Oleh-oleh Haji & Umrah',
                'Jelajahi seluruh katalog Arafagift: kurma premium, sajadah, tasbih, kalung, sarung, dan gift set hadiah haji umrah dengan packaging elegan.',
            );
        } else {
            $seo = PageSeo::make(
                sprintf('%s — Oleh-oleh Haji & Umrah | %s', $match['name'], PageSeo::BRAND),
                sprintf(
                    'Beli %s untuk hadiah haji dan umrah di Arafagift: pilihan terbaik dengan packaging elegan, siap dikirim ke seluruh Indonesia.',
                    mb_strtolower((string) $match['name']),
                ),
            );
        }

        $seo = $seo->canonical($canonical);

        // Filter ?untuk= membuat kombinasi URL yang banyak tapi isinya hanya
        // subset dari koleksi yang sama. Judul tetap informatif, canonical
        // tetap ke koleksi induk, dan robots=noindex supaya tidak mengikis
        // crawl budget tanpa menolak halaman yang berguna.
        return ($occasion === null ? $seo : $seo->withNoIndex())->toArray();
    }

    /**
     * Cocokkan slug kategori secara case-insensitive, lalu kembalikan versi
     * kanonik yang tersimpan di database.
     *
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>|null
     */
    private static function matchCategory(array $categories, string $slug): ?array
    {
        $needle = mb_strtolower(trim($slug));

        foreach ($categories as $candidate) {
            if (mb_strtolower((string) $candidate['slug']) === $needle) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        return Cache::flexible('koleksi-payload', [600, 3600], fn (): array => [
            'categories' => Category::withCountActiveProducts()->orderBy('id')->get()->map->toCatalog()->values()->all(),
            'occasions' => Occasion::orderBy('id')->get()->map->toCatalog()->values()->all(),
            'products' => Product::with(['category', 'occasions'])->where('status', 'active')->get()->map->toCard()->values()->all(),
        ]);
    }
}
