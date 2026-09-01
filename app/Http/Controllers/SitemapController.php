<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => route('home')],
            ['loc' => route('collection', ['category' => null])],
            ['loc' => route('about')],
            ['loc' => route('faq')],
            ['loc' => route('legal', ['slug' => 'kebijakan-privasi'])],
            ['loc' => route('legal', ['slug' => 'syarat-ketentuan'])],
            ['loc' => route('legal', ['slug' => 'pengiriman-pengembalian'])],
        ];

        foreach (Category::all() as $category) {
            $urls[] = ['loc' => route('collection', ['category' => $category->slug])];
        }

        foreach (Product::where('status', 'active')->get() as $product) {
            $urls[] = ['loc' => route('product', ['product' => $product->slug]), 'lastmod' => $product->updated_at?->toAtomString()];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /akun',
            'Disallow: /checkout',
            'Disallow: /keranjang',
            'Disallow: /lacak-pesanan',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
