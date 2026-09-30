<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\Product;
use App\Models\Testimonial;
use App\Support\Seo\PageSeo;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shop/HomePage', [
            ...self::payload(),
            'seoHead' => self::seoHead(),
        ]);
    }

    /**
     * Brand ditulis di depan, bukan di belakang. Untuk query "arafagift" Google
     * lebih respek terhadap microfluid ketika brand muncul sedekat mungkin ke
     * awal judul, dan kata kunci inti ("oleh-oleh haji", "kurma", "gift set")
     * tetap muat dalam 60 karakter.
     */
    public static function seoHead(): array
    {
        return PageSeo::make(
            'Arafagift — Toko Oleh-oleh Haji & Umrah, Kurma & Gift Set',
            'Toko oleh-oleh haji & umrah di Makassar: kurma Ajwa premium, sajadah, tasbih, dan gift set hadiah dengan packaging elegan. Kartu ucapan gratis tiap pesanan.',
        )
            ->withoutBrandSuffix()
            ->canonical(route('home'))
            ->image('/images/assets/hero-arafahgift-v2.png')
            ->jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => PageSeo::BRAND,
                'url' => route('home'),
                'inLanguage' => 'id-ID',
            ])
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        return Cache::flexible('home-payload', [600, 3600], function (): array {
            $content = Content::where('key', 'home')->firstOrFail()->data;

            $signatureProduct = Product::with(['category', 'occasions', 'supplier'])
                ->where('slug', $content['signature']['productSlug'] ?? null)
                ->first();

            return [
                // Scope wajib dipanggil di sini. Tanpa itu products_active_count
                // null, lalu toCatalog() jatuh ke kolom product_count lama yang
                // di-seed dan tidak pernah ikut berubah — homepage lalu
                // menampilkan "0 produk" untuk kategori yang jelas berisi barang.
                'categories' => Category::withCountActiveProducts()->orderBy('id')->get()->map->toCatalog()->values()->all(),
                'occasions' => Occasion::orderBy('id')->get()->map->toCatalog()->values()->all(),
                'featuredProducts' => Product::with(['category', 'occasions'])->where('featured', true)->orderBy('featured_order')->orderBy('id')->get()->map->toCard()->values()->all(),
                'signatureProduct' => $signatureProduct?->toCatalog(),
                'content' => $content,
                'testimonials' => Testimonial::orderBy('id')->get()->map(fn (Testimonial $t) => $t->only(['id', 'rating', 'quote', 'name', 'city', 'context', 'avatar']))->values()->all(),
                'faqs' => Faq::orderBy('sort_order')->get()->map(fn (Faq $faq) => ['q' => $faq->question, 'a' => $faq->answer])->values()->all(),
            ];
        });
    }
}
