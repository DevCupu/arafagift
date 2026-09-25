<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(Request $request, ?string $category = null): Response
    {
        return Inertia::render('shop/CollectionPage', [
            ...self::payload(),
            'category' => $category ?? 'semua',
            'untuk' => $request->query('untuk'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        return Cache::remember('koleksi-payload', now()->addMinutes(10), fn (): array => [
            'categories' => Category::withCountActiveProducts()->orderBy('id')->get()->map->toCatalog()->values()->all(),
            'occasions' => Occasion::orderBy('id')->get()->map->toCatalog()->values()->all(),
            'products' => Product::with(['category', 'occasions'])->where('status', 'active')->get()->map->toCard()->values()->all(),
        ]);
    }
}
