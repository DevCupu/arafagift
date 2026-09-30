<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Seo\PageSeo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $term = trim($validated['q']);
        $cacheKey = 'product-search:'.hash('sha256', mb_strtolower($term));

        $products = Cache::flexible($cacheKey, [60, 300], fn () => Product::query()
            ->select(['id', 'category_id', 'name', 'slug', 'price', 'art', 'image'])
            ->with('category:id,name,slug')
            ->where('status', 'active')
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"))
            ->limit(6)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'category' => $product->category->name,
                'price' => $product->price,
                'art' => $product->art,
                'image' => $product->imageUrl(),
            ])
            ->values()
            ->all());

        return response()->json($products)->header('Cache-Control', 'private, max-age=60');
    }

    public function show(Request $request, Product $product): Response
    {
        // Route model binding hanya memfilter berdasarkan slug, jadi produk
        // berstatus draft atau nonaktif masih punya URL yang bisa dibaca publik
        // dan diindeks. Leila Admin masih boleh melihatnya untuk pratinjau.
        abort_unless(
            $product->status === 'active' || $request->user()?->is_admin,
            404,
        );

        return Inertia::render('shop/ProductPage', [
            ...self::page($product),
            'seoHead' => self::seoHead($product),
        ]);
    }

    /**
     * aggregateRating sengaja tidak pernah dikirim. Kolom rating/review di
     * database masih berisi nilai placeholder, dan Google memperlakukan
     * rating yang tidak didukung data nyata sebagai pelanggaran spam
     * structured data, yang bisa costing seluruh halaman produk di rich result.
     *
     * @return list<string>
     */
    public static function seoHead(Product $product): array
    {
        $product->loadMissing(['category', 'occasions', 'supplier']);

        $url = route('product', $product->slug);
        $description = trim((string) ($product->short ?: $product->description));

        if ($description === '') {
            $description = sprintf(
                'Beli %s untuk oleh-olah haji dan umrah di %s. Dikemas rapi dengan kartu ucapan gratis.',
                $product->name,
                PageSeo::BRAND,
            );
        }

        $image = $product->imageUrl();

        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'sku' => $product->sku,
            'category' => $product->category?->name,
            'url' => $url,
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'price' => (float) $product->price,
                'priceCurrency' => 'IDR',
                'availability' => $product->stock > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => ['@type' => 'Organization', 'name' => PageSeo::BRAND],
            ],
        ];

        if ($image !== null) {
            $graph['image'] = [$image];
        }

        return PageSeo::make($product->name, $description)
            ->type('product')
            ->canonical($url)
            ->image($image)
            ->jsonLd([
                '@context' => 'https://schema.org',
                '@graph' => [
                    $graph,
                    self::breadcrumbs($product, $url),
                ],
            ])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private static function breadcrumbs(Product $product, string $url): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
        ];

        $position = 2;

        if ($product->category?->exists) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $product->category->name,
                'item' => route('collection', ['category' => $product->category->slug]),
            ];
        }

        $items[] = ['@type' => 'ListItem', 'position' => $position, 'name' => $product->name, 'item' => $url];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @return array{product: array<string, mixed>, related: array<int, array<string, mixed>>}
     */
    public static function page(Product $product): array
    {
        return Cache::flexible("product-page:{$product->slug}", [600, 3600], function () use ($product): array {
            $product->load(['category', 'occasions', 'supplier']);

            $related = Product::with(['category', 'occasions'])
                ->where('status', 'active')
                ->where('id', '!=', $product->id)
                ->orderByRaw('category_id = ? desc', [$product->category_id])
                ->limit(4)
                ->get();

            return [
                'product' => $product->toCatalog(),
                'related' => $related->map->toCard()->values()->all(),
            ];
        });
    }
}
