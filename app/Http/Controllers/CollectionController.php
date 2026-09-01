<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request, ?string $category = null): Response
    {
        return Inertia::render('shop/CollectionPage', [
            'categories' => Category::orderBy('id')->get()->map->toCatalog()->values(),
            'occasions' => Occasion::orderBy('id')->get()->map->toCatalog()->values(),
            'products' => Product::with(['category', 'occasions', 'supplier'])->where('status', 'active')->get()->map->toCatalog()->values(),
            'category' => $category ?? 'semua',
            'untuk' => $request->query('untuk'),
        ]);
    }
}
