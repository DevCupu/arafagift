<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class LandingController extends Controller
{
    /**
     * Field yang boleh sampai ke halaman publik. toCatalog() ikut membawa
     * cost/supplier/storageLocation — data internal yang tidak dibutuhkan
     * ProductCard maupun keranjang.
     */
    private const CARD_FIELDS = [
        'id', 'name', 'slug', 'price', 'comparePrice', 'category',
        'art', 'image', 'stock', 'rating', 'reviews', 'badge',
        'short', 'description', 'includes', 'details',
    ];

    public function show(Request $request, string $slug): Response
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        // Draft hanya bisa dilihat admin, supaya bisa di-preview sebelum publish.
        abort_unless($page->status === 'publish' || $request->user()?->is_admin, 404);

        $blocks = $page->activeBlocks();

        $response = Inertia::render('shop/LandingPage', [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
            ],
            'blocks' => $blocks->all(),
            'products' => $this->hydrateProducts($blocks),
        ])->toResponse($request);

        // Aplikasi ini tanpa SSR, jadi <meta robots> baru ada setelah JS jalan.
        // Header ini menjaga halaman iklan tetap tidak terindeks tanpa JS.
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    /**
     * Ambil semua produk yang dirujuk blok produk_unggulan dalam satu query,
     * berapa pun jumlah bloknya, lalu index by id supaya Vue tinggal lookup.
     *
     * @param  Collection<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function hydrateProducts(Collection $blocks): array
    {
        $ids = $blocks
            ->where('type', 'produk_unggulan')
            ->flatMap(fn (array $block): array => $block['content']['productIds'] ?? [])
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return Product::with(['category', 'occasions', 'supplier'])
            ->whereIn('id', $ids)
            ->where('status', 'active')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => Arr::only($product->toCatalog(), self::CARD_FIELDS),
            ])
            ->all();
    }
}
