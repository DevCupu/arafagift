<?php

namespace App\Services;

use App\Http\Controllers\ProductController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Aturan ulasan produk: siapa boleh mengulas dan sinkron rating produk.
 */
class ProductReviewService
{
    /**
     * Pesanan selesai milik user yang berisi produk ini, atau null kalau belum
     * pernah membeli. Hanya pembeli sungguhan yang boleh mengulas.
     */
    public static function eligibleOrder(User $user, Product $product): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('items', fn ($items) => $items->where('product_id', $product->id))
            ->latest('id')
            ->first();
    }

    /**
     * Hitung ulang rating & jumlah ulasan dari ulasan yang disetujui, lalu
     * buang cache yang menampilkannya. Panggil setelah ulasan berubah status
     * atau dihapus.
     */
    public static function sync(Product $product): void
    {
        $approved = $product->reviews()->approved();

        $product->forceFill([
            'rating' => round((float) ($approved->avg('rating') ?? 0), 1),
            'reviews_count' => $approved->count(),
        ])->save();

        Cache::forget(ProductController::pageCacheKey($product->slug));
        Cache::forget('home-payload');
        Cache::forget('koleksi-payload');
    }
}
