<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request): Response
    {
        $productIds = $request->user()->wishlists()->pluck('product_id');

        return Inertia::render('account/WishlistPage', [
            'products' => Product::with('category')->whereIn('id', $productIds)->get()->map->toCatalog()->values(),
        ]);
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $wishlist = $request->user()->wishlists();
        $existing = $wishlist->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $wishlist->create(['product_id' => $product->id]);
        }

        return back();
    }
}
