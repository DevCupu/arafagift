<?php

namespace App\Http\Middleware;

use App\Models\Content;
use App\Models\Order;
use App\Support\StoreSettingsCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // ponytail: 10-min TTL, invalidated explicitly on save (ContentController/AdminSettingsController) — no need for
        // anything smarter at this scale. Caching plain arrays, not the Eloquent models — caching model instances hits
        // PHP unserialize() class-loading-order issues on some cache drivers.
        $homeData = Cache::remember('home-content', now()->addMinutes(10), fn () => Content::where('key', 'home')->first()?->data);
        $store = StoreSettingsCache::store();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'wishlistIds' => $request->user() ? $request->user()->wishlists()->pluck('product_id') : [],
            ],
            'pendingOrdersCount' => $request->user()?->is_admin
                ? Cache::remember('pending-orders-count', now()->addMinutes(5), fn () => Order::where('status', 'pending')->count())
                : 0,
            'announcement' => is_array($homeData) ? ($homeData['announcement'] ?? '') : '',
            'store' => $store,
        ];
    }
}
