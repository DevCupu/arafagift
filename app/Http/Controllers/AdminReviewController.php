<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Services\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $reviews = ProductReview::query()
            ->with(['product:id,name,slug', 'order:id,order_number', 'user:id,email', 'moderator:id,name'])
            ->when(in_array($status, ProductReview::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->limit(300)
            ->get();

        return Inertia::render('admin/ReviewsPage', [
            'reviews' => $reviews->map->toCatalog()->values(),
            'counts' => ProductReview::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'status' => in_array($status, ProductReview::STATUSES, true) ? $status : null,
        ]);
    }

    public function update(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ProductReview::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $review->update([
            ...$validated,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        ProductReviewService::sync($review->product);

        return back()->with('success', 'Ulasan diperbarui');
    }

    public function destroy(ProductReview $review): RedirectResponse
    {
        $product = $review->product;
        $review->delete();

        ProductReviewService::sync($product);

        return back()->with('success', 'Ulasan dihapus');
    }
}
