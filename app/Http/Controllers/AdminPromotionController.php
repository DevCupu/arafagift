<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminPromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/PromotionsPage', [
            'promotions' => Promotion::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:promotions,code'],
            'type' => ['required', 'string', 'max:60'],
            'usage' => ['required', 'string', 'max:40'],
            'period' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(['active', 'ended'])],
        ]);

        Promotion::create($validated);

        return back()->with('success', 'Kode promo dibuat');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('success', "{$promotion->code} dihapus");
    }
}
