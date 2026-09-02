<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Faq;
use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ContentController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/ContentPage', [
            'content' => Content::where('key', 'home')->firstOrFail()->data,
            'testimonials' => Testimonial::orderBy('id')->get(),
            'faqs' => Faq::orderBy('sort_order')->get(),
            'featuredProducts' => Product::with(['category', 'supplier', 'occasions'])->where('featured', true)->orderBy('featured_order')->orderBy('id')->get()->map->toCatalog()->values(),
            'availableProducts' => Product::where('featured', false)->orderBy('name')->get(['id', 'name', 'slug']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'slug']),
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
            'hero.ctaSecondary.label' => ['required', 'string', 'max:40'],
            'hero.ctaSecondary.to' => ['required', 'string', 'max:120'],
            'signature.eyebrow' => ['required', 'string', 'max:80'],
            'signature.title' => ['required', 'string', 'max:120'],
            'signature.body' => ['required', 'string', 'max:400'],
            'signature.productSlug' => ['required', 'string', 'exists:products,slug'],
            'signature.cta.label' => ['required', 'string', 'max:40'],
            'signature.cta.to' => ['required', 'string', 'max:120'],
            'bulk.eyebrow' => ['required', 'string', 'max:80'],
            'bulk.title' => ['required', 'string', 'max:120'],
            'bulk.sub' => ['required', 'string', 'max:300'],
            'bulk.points' => ['required', 'array', 'min:1'],
            'bulk.points.*' => ['required', 'string', 'max:200'],
            'bulk.cta.label' => ['required', 'string', 'max:40'],
            'bulk.cta.href' => ['required', 'string', 'max:200'],
            'story.eyebrow' => ['required', 'string', 'max:80'],
            'story.title' => ['required', 'string', 'max:120'],
            'story.body' => ['required', 'array', 'min:1'],
            'story.body.*' => ['required', 'string', 'max:500'],
            'story.signature' => ['required', 'string', 'max:120'],
            'instagram.handle' => ['required', 'string', 'max:60'],
            'instagram.title' => ['required', 'string', 'max:100'],
            'instagram.url' => ['required', 'url'],
            'instagram.posts' => ['required', 'array', 'min:1', 'max:12'],
            'instagram.posts.*.art' => ['required', 'string', Rule::in(['giftset', 'kurma', 'sajadah', 'tasbih', 'madu', 'parfum', 'souvenir'])],
            'instagram.posts.*.caption' => ['required', 'string', 'max:120'],
            'values' => ['required', 'array', 'min:1', 'max:8'],
            'values.*.icon' => ['required', 'string', 'in:Sparkles,Gift,BadgeCheck,Send'],
            'values.*.title' => ['required', 'string', 'max:60'],
            'values.*.body' => ['required', 'string', 'max:240'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $content = Content::where('key', 'home')->firstOrFail();
        $data = $content->data;

        $data['announcement'] = $validated['announcement'];

        $data['hero']['eyebrow'] = $validated['hero']['eyebrow'];
        $data['hero']['headline'] = $validated['hero']['headline'];
        $data['hero']['sub'] = $validated['hero']['sub'];
        $data['hero']['cta']['label'] = $validated['hero']['cta']['label'];
        $data['hero']['cta']['to'] = $validated['hero']['cta']['to'];
        $data['hero']['ctaSecondary']['label'] = $validated['hero']['ctaSecondary']['label'];
        $data['hero']['ctaSecondary']['to'] = $validated['hero']['ctaSecondary']['to'];

        $data['signature']['eyebrow'] = $validated['signature']['eyebrow'];
        $data['signature']['title'] = $validated['signature']['title'];
        $data['signature']['body'] = $validated['signature']['body'];
        $data['signature']['productSlug'] = $validated['signature']['productSlug'];
        $data['signature']['cta']['label'] = $validated['signature']['cta']['label'];
        $data['signature']['cta']['to'] = $validated['signature']['cta']['to'];

        $data['bulk']['eyebrow'] = $validated['bulk']['eyebrow'];
        $data['bulk']['title'] = $validated['bulk']['title'];
        $data['bulk']['sub'] = $validated['bulk']['sub'];
        $data['bulk']['points'] = $validated['bulk']['points'];
        $data['bulk']['cta']['label'] = $validated['bulk']['cta']['label'];
        $data['bulk']['cta']['href'] = $validated['bulk']['cta']['href'];

        $data['story']['eyebrow'] = $validated['story']['eyebrow'];
        $data['story']['title'] = $validated['story']['title'];
        $data['story']['body'] = $validated['story']['body'];
        $data['story']['signature'] = $validated['story']['signature'];

        $data['instagram']['handle'] = $validated['instagram']['handle'];
        $data['instagram']['title'] = $validated['instagram']['title'];
        $data['instagram']['url'] = $validated['instagram']['url'];
        $data['instagram']['posts'] = $validated['instagram']['posts'];

        $data['values'] = $validated['values'];

        if ($request->hasFile('hero_image')) {
            $path = $request->file('hero_image')->store('content', 'public');

            if ($path === false) {
                throw new RuntimeException('Gagal menyimpan gambar hero.');
            }

            $data['hero']['image'] = Storage::disk('public')->url($path);
        }

        $content->update(['data' => $data]);
        Cache::forget('home-content');

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

    public function updateTestimonial(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'quote' => ['required', 'string', 'max:400'],
            'name' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'context' => ['nullable', 'string', 'max:120'],
        ]);

        $testimonial->update($validated);

        return back();
    }

    public function destroyTestimonial(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back();
    }

    public function bulkDestroyTestimonials(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:testimonials,id'],
        ]);

        Testimonial::whereIn('id', $validated['ids'])->delete();

        return back();
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:1000'],
        ]);

        $validated['sort_order'] = (Faq::max('sort_order') ?? -1) + 1;

        Faq::create($validated);

        return back();
    }

    public function updateFaq(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:1000'],
        ]);

        $faq->update($validated);

        return back();
    }

    public function destroyFaq(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back();
    }

    public function bulkDestroyFaqs(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:faqs,id'],
        ]);

        Faq::whereIn('id', $validated['ids'])->delete();

        return back();
    }

    public function reorderFaq(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'max:100'],
            'order.*' => ['required', 'integer', 'exists:faqs,id'],
        ]);

        foreach (array_values($validated['order']) as $position => $id) {
            Faq::whereKey($id)->update(['sort_order' => $position]);
        }

        return back();
    }

    public function addFeatured(Product $product): RedirectResponse
    {
        $next = Product::where('featured', true)->max('featured_order') ?? 0;
        $product->update(['featured' => true, 'featured_order' => $next + 1]);

        return back();
    }

    public function removeFeatured(Product $product): RedirectResponse
    {
        $product->update(['featured' => false]);

        return back();
    }

    public function reorderFeatured(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'max:100'],
            'order.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        foreach (array_values($validated['order']) as $position => $id) {
            Product::whereKey($id)->update(['featured' => true, 'featured_order' => $position + 1]);
        }

        return back();
    }
}
