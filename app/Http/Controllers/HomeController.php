<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shop/HomePage', self::payload());
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        return Cache::remember('home-payload', now()->addMinutes(10), function (): array {
            $content = Content::where('key', 'home')->firstOrFail()->data;

            $signatureProduct = Product::with(['category', 'occasions', 'supplier'])
                ->where('slug', $content['signature']['productSlug'] ?? null)
                ->first();

            return [
                'categories' => Category::orderBy('id')->get()->map->toCatalog()->values()->all(),
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
