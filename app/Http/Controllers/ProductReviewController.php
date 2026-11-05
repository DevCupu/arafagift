<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Services\ProductReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rating.required' => 'Pilih jumlah bintang.',
            'body.required' => 'Tulis ulasan Anda.',
            'body.min' => 'Ulasan minimal 10 karakter.',
        ]);

        $order = ProductReviewService::eligibleOrder($user, $product);

        if (! $order) {
            return back()->withErrors(['review' => 'Ulasan hanya bisa ditulis setelah pesanan produk ini selesai.']);
        }

        if ($product->reviews()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['review' => 'Anda sudah mengulas produk ini.']);
        }

        $product->reviews()->create([
            ...$validated,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'author_name' => $user->name,
            'status' => ProductReview::STATUS_PENDING,
        ]);

        return back()->with('success', 'Terima kasih! Ulasan Anda tampil setelah kami setujui.');
    }
}
