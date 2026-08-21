<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/ContentPage', [
            'content' => Content::where('key', 'home')->firstOrFail()->data,
            'testimonials' => Testimonial::orderBy('id')->get(),
            'featuredProducts' => Product::where('featured', true)->orderBy('id')->get()->map->toCatalog()->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'announcement' => ['required', 'string', 'max:180'],
            'hero.eyebrow' => ['required', 'string', 'max:80'],
            'hero.headline' => ['required', 'string', 'max:200'],
            'hero.sub' => ['required', 'string', 'max:300'],
            'hero.cta.label' => ['required', 'string', 'max:40'],
            'hero.cta.to' => ['required', 'string', 'max:120'],
            'signature.title' => ['required', 'string', 'max:120'],
            'signature.body' => ['required', 'string', 'max:400'],
            'signature.productSlug' => ['required', 'string', 'exists:products,slug'],
            'instagram.handle' => ['required', 'string', 'max:60'],
            'instagram.url' => ['required', 'url'],
        ]);

        $content = Content::where('key', 'home')->firstOrFail();
        $data = $content->data;

        $data['announcement'] = $validated['announcement'];
        $data['hero']['eyebrow'] = $validated['hero']['eyebrow'];
        $data['hero']['headline'] = $validated['hero']['headline'];
        $data['hero']['sub'] = $validated['hero']['sub'];
        $data['hero']['cta']['label'] = $validated['hero']['cta']['label'];
        $data['hero']['cta']['to'] = $validated['hero']['cta']['to'];
        $data['signature']['title'] = $validated['signature']['title'];
        $data['signature']['body'] = $validated['signature']['body'];
        $data['signature']['productSlug'] = $validated['signature']['productSlug'];
        $data['instagram']['handle'] = $validated['instagram']['handle'];
        $data['instagram']['url'] = $validated['instagram']['url'];

        $content->update(['data' => $data]);

        return back();
    }

    public function storeTestimonial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'max:400'],
            'name' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'context' => ['nullable', 'string', 'max:120'],
        ]);

        Testimonial::create($validated);

        return back();
    }

    public function destroyTestimonial(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back();
    }

    public function removeFeatured(Product $product): RedirectResponse
    {
        $product->update(['featured' => false]);

        return back();
    }
}
