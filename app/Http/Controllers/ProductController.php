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
        $term = $validated['q'];

        $products = Product::with(['category', 'supplier'])
            ->where('status', 'active')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('description', 'like', "%{$term}%"))
            ->limit(6)
            ->get();

        return response()->json($products->map->toCatalog()->values());
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
        return Cache::remember("product-page:{$product->slug}", now()->addMinutes(10), function () use ($product): array {
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
