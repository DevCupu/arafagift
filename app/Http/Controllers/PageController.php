<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('shop/AboutPage', [
            'content' => Content::where('key', 'home')->firstOrFail()->data,
        ]);
    }

    public function faq(): Response
    {
        return Inertia::render('shop/FaqPage', [
            'faqs' => Faq::orderBy('sort_order')->get()->map(fn (Faq $faq) => ['q' => $faq->question, 'a' => $faq->answer])->values(),
        ]);
    }
}
