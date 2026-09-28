<?php

namespace App\Http\Controllers;

use App\Models\Product;
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

    public function show(Product $product): Response
    {
        return Inertia::render('shop/ProductPage', self::page($product));
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
