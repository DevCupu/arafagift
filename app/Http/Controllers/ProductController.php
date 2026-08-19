<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load(['category', 'occasions']);

        $sameCategory = Product::with(['category', 'occasions'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->get();

        $others = Product::with(['category', 'occasions'])
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where('category_id', '!=', $product->category_id)
            ->get();

        $related = $sameCategory->concat($others)->take(4);

        return Inertia::render('shop/ProductPage', [
            'product' => $product->toCatalog(),
            'related' => $related->map->toCatalog()->values(),
        ]);
    }
}
