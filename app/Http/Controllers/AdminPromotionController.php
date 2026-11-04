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
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:0', 'max:100000000'],
            'min_purchase' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'applies_to' => ['required', Rule::in(['all', 'category', 'product'])],
            'applies_value' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['min_purchase'] = $validated['min_purchase'] ?? 0;
        $validated['usage_limit'] = $validated['usage_limit'] ?? null;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['code'] = strtoupper(trim($validated['code']));

        Promotion::create($validated);

        return back()->with('success', 'Kode promo dibuat');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('promotions', 'code')->ignore($promotion->id)],
            'type' => ['required', 'string', 'max:60'],
            'usage' => ['required', 'string', 'max:40'],
            'period' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(['active', 'ended'])],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'integer', 'min:0', 'max:100000000'],
            'min_purchase' => ['nullable', 'integer', 'min:0'],
            'max_discount' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'applies_to' => ['required', Rule::in(['all', 'category', 'product'])],
            'applies_value' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['min_purchase'] = $validated['min_purchase'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['code'] = strtoupper(trim($validated['code']));

        $promotion->update($validated);

        return back()->with('success', "Kode promo {$promotion->code} diperbarui");
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return back()->with('success', "{$promotion->code} dihapus");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:promotions,id'],
        ]);

        $count = Promotion::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', "{$count} kode promo dihapus");
    }
}
