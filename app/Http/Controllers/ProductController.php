<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $product->load(['category', 'occasions', 'supplier']);

        $related = Product::with(['category', 'occasions', 'supplier'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->limit(4)
            ->get();

        return Inertia::render('shop/ProductPage', [
            'product' => $product->toCatalog(),
            'related' => $related->map->toCatalog()->values(),
        ]);
    }
}
