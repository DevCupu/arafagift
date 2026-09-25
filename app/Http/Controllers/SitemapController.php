<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = view('sitemap', ['urls' => self::urls()])->render();

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * @return array<int, array{loc: string, lastmod?: string}>
     */
    public static function urls(): array
    {
        return Cache::remember('sitemap-urls', now()->addHours(24), function (): array {
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

            return $urls;
        });
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /akun',
            'Disallow: /checkout',
            'Disallow: /lacak-pesanan',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
