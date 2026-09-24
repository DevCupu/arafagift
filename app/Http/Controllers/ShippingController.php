<?php

namespace App\Http\Controllers;

use App\Exceptions\RajaOngkirException;
use App\Models\Setting;
use App\Services\OrderPricing;
use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    public function destinations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        try {
            return response()->json($this->rajaOngkir->searchDestinations($validated['q']));
        } catch (RajaOngkirException) {
            return response()->json([]);
        }
    }

    public function cost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_id' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $origin = Setting::first()?->origin_destination_id;
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
