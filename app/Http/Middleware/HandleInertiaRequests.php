<?php

namespace App\Http\Middleware;

use App\Models\Content;
use App\Models\Setting;
use Illuminate\Http\Request;
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
        $homeData = Content::where('key', 'home')->first()?->data;
        $settings = Setting::first();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'wishlistIds' => $request->user() ? $request->user()->wishlists()->pluck('product_id') : [],
            ],
            'announcement' => is_array($homeData) ? ($homeData['announcement'] ?? '') : '',
            'store' => [
                'whatsapp' => $settings?->whatsapp,
                'freeShippingFrom' => $settings?->free_shipping_from ?? 0,
                'freeShippingCities' => $settings?->freeShippingCitiesList() ?? [],
            ],
        ];
    }
}
