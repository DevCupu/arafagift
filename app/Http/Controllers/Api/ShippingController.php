<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RajaOngkirException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchShippingDestinationRequest;
use App\Http\Requests\Api\ShippingCostRequest;
use App\Services\RajaOngkirService;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    public function __construct(private readonly RajaOngkirService $rajaOngkir) {}

    public function destinations(SearchShippingDestinationRequest $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Pencarian tujuan berhasil.',
                'data' => $this->rajaOngkir->searchDestinations($request->validated('search')),
            ]);
        } catch (RajaOngkirException $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function cost(ShippingCostRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            return response()->json([
                'success' => true,
                'message' => 'Perhitungan ongkos kirim berhasil.',
                'data' => $this->rajaOngkir->calculateDomesticCost(
                    $data['origin'],
                    $data['destination'],
                    (int) $data['weight'],
                    $data['courier'],
                ),
            ]);
        } catch (RajaOngkirException $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function errorResponse(RajaOngkirException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'data' => null,
        ], $exception->httpStatus());
    }
}
