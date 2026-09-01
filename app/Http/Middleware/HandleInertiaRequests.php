<?php

namespace App\Http\Middleware;

use App\Models\Content;
use App\Models\Order;
use App\Models\Setting;
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
        $store = Cache::remember('settings-store', now()->addMinutes(10), function () {
            $settings = Setting::first();

            return [
                'name' => $settings?->store_name ?? config('app.name'),
                'address' => $settings?->address,
                'email' => $settings?->email,
                'whatsapp' => $settings?->whatsapp,
                'originCity' => $settings?->origin_city ?? 'Jakarta',
                'freeShippingFrom' => $settings?->free_shipping_from ?? 0,
                'freeShippingCities' => $settings?->freeShippingCitiesList() ?? [],
            ];
        });

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'wishlistIds' => $request->user() ? $request->user()->wishlists()->pluck('product_id') : [],
            ],
            'pendingOrdersCount' => $request->user()?->is_admin
                ? Order::where('status', 'pending')->count()
                : 0,
            'announcement' => is_array($homeData) ? ($homeData['announcement'] ?? '') : '',
            'store' => $store,
        ];
    }
}
