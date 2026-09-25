<?php

namespace App\Http\Controllers;

use App\Exceptions\RajaOngkirException;
use App\Models\Product;
use App\Services\OrderPricing;
use App\Services\RajaOngkirService;
use App\Support\StoreSettingsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    public function cost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $ids = array_values(array_unique(array_column($validated['items'], 'id')));
        if (Product::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['items' => 'Salah satu produk tidak tersedia.']);
        }

        $origin = StoreSettingsCache::store()['originDestinationId'];
        if (! $origin) {
            return response()->json(['available' => false]);
        }

        $priced = OrderPricing::priceItems($validated['items']);
        $weight = OrderPricing::totalWeightGrams($priced['lines']);

        try {
            return response()->json([
                'available' => true,
                'weight' => $weight,
                'options' => $this->rajaOngkir->getCosts($origin, $validated['destination_id'], $weight),
            ]);
        } catch (RajaOngkirException) {
            return response()->json(['available' => false]);
        }
    }
}
