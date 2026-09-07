<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RajaOngkir
{
    // ponytail: fixed courier set instead of admin-configurable — the 3 biggest domestic couriers cover
    // the vast majority of shipments here; add more codes to this list if a courier is missing. Not every
    // courier is necessarily enabled on the account's API plan (e.g. a starter key may only have JNE) —
    // getCosts() below tolerates individual couriers failing and just returns fewer options.
    private const COURIERS = ['jne', 'jnt', 'sicepat'];

    /**
     * @return list<array{id: int, label: string, city: string, province: string, zip: string}>
     */
    public static function searchDestination(string $keyword): array
    {
        return Cache::remember("rajaongkir:destination:{$keyword}", now()->addWeek(), function () use ($keyword) {
            $response = Http::baseUrl(config('services.rajaongkir.base_url'))
                ->withHeaders(['key' => config('services.rajaongkir.key')])
                ->timeout(5)
                ->get('destination/domestic-destination', [
                    'search' => $keyword,
                    'limit' => 10,
                    'offset' => 0,
                ])
                ->throw();

            return collect($response->json('data', []))
                ->map(fn (array $d) => [
                    'id' => $d['id'],
                    'label' => $d['label'],
                    'city' => $d['city_name'],
                    'province' => $d['province_name'],
                    'zip' => $d['zip_code'],
                ])
                ->all();
        });
    }

    /**
     * @return list<array{courier: string, service: string, description: string, cost: int, etd: string}>
     */
    public static function getCosts(string $originId, string $destinationId, int $weightGrams): array
    {
        return Cache::remember(
            "rajaongkir:cost:{$originId}:{$destinationId}:{$weightGrams}",
            now()->addMinutes(15),
            function () use ($originId, $destinationId, $weightGrams) {
                $results = [];
                $lastError = null;

                foreach (self::COURIERS as $courier) {
                    try {
                        $results = [...$results, ...self::costsForCourier($originId, $destinationId, $weightGrams, $courier)];
                    } catch (ConnectionException|RequestException $e) {
                        $lastError = $e;
                    }
                }

                // Kalau semua kurir gagal (API down, atau tidak satupun aktif di plan ini), lempar exception
                // terakhir supaya caller (ShippingController/CheckoutController) jatuh ke fallback manual —
                // bukan diam-diam mengembalikan array kosong yang terlihat seperti "memang tidak ada opsi".
                if (! $results && $lastError) {
                    throw $lastError;
                }

                return $results;
            },
        );
    }

    /**
     * @return list<array{courier: string, service: string, description: string, cost: int, etd: string}>
     */
    private static function costsForCourier(string $originId, string $destinationId, int $weightGrams, string $courier): array
    {
        $response = Http::baseUrl(config('services.rajaongkir.base_url'))
            ->withHeaders(['key' => config('services.rajaongkir.key')])
            ->timeout(5)
            ->asForm()
            ->post('calculate/domestic-cost', [
                'origin' => $originId,
                'destination' => $destinationId,
                'weight' => $weightGrams,
                'courier' => $courier,
            ])
            ->throw();

        return collect($response->json('data', []))
            ->map(fn (array $d) => [
                'courier' => $d['code'],
                'service' => $d['service'],
                'description' => $d['description'],
                'cost' => $d['cost'],
                'etd' => $d['etd'],
            ])
            ->all();
    }
}
