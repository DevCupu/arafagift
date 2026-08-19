<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Faq;
use App\Models\Occasion;
use App\Models\Product;
use App\Models\Testimonial;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $content = Content::where('key', 'home')->firstOrFail()->data;

        return Inertia::render('shop/HomePage', [
            'categories' => Category::orderBy('id')->get()->map->toCatalog()->values(),
            'occasions' => Occasion::orderBy('id')->get()->map->toCatalog()->values(),
            'featuredProducts' => Product::with(['category', 'occasions'])->where('featured', true)->get()->map->toCatalog()->values(),
            'signatureProduct' => Product::with(['category', 'occasions'])->where('slug', $content['signature']['productSlug'])->firstOrFail()->toCatalog(),
            'content' => $content,
            'testimonials' => Testimonial::orderBy('id')->get(),
            'faqs' => Faq::orderBy('sort_order')->get()->map(fn (Faq $faq) => ['q' => $faq->question, 'a' => $faq->answer])->values(),
        ]);
    }
}
