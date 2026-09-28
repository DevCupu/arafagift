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
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            // Closure mencegah cache/database disentuh pada partial reload
            // Inertia yang tidak meminta prop bersama ini.
            'auth' => function () use ($request): array {
                $user = $request->user();

                return [
                    'user' => $user,
                    'wishlistIds' => $user ? $user->wishlists()->pluck('product_id') : [],
                ];
            },
            'pendingOrdersCount' => fn (): int => $request->user()?->is_admin
                ? Cache::flexible('pending-orders-count', [300, 900], fn (): int => Order::where('status', 'pending')->count())
                : 0,
            'announcement' => function (): string {
                $homeData = Cache::flexible(
                    'home-content',
                    [600, 3600],
                    fn () => Content::where('key', 'home')->first()?->data,
                );

                return is_array($homeData) ? ($homeData['announcement'] ?? '') : '';
            },
            'store' => fn (): array => StoreSettingsCache::store(),
        ];
    }
}
