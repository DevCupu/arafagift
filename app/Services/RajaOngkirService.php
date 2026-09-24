<?php

namespace App\Services;

use App\Exceptions\RajaOngkirException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RajaOngkirService
{
    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('services.rajaongkir.key', '');
        $this->baseUrl = rtrim((string) config('services.rajaongkir.base_url'), '/');
        $this->timeout = max(1, (int) config('services.rajaongkir.timeout', 5));
    }

    /**
     * Direct Search Method. The returned ID is a subdistrict ID and can be sent
     * directly as origin or destination to the domestic cost endpoint.
     *
     * @return list<array{id: string, subdistrict_id: string, label: string, city: string, province: string, district: string, subdistrict: string, zip: string}>
     */
    public function searchDestinations(string $search): array
    {
        $normalizedSearch = trim($search);
        $cacheKey = 'rajaongkir:destinations:'.hash('sha256', mb_strtolower($normalizedSearch));

        return Cache::remember($cacheKey, now()->addWeek(), function () use ($normalizedSearch): array {
            $response = $this->send(
                operation: 'search_destinations',
                callback: fn (PendingRequest $request) => $request->get('destination/domestic-destination', [
                    'search' => $normalizedSearch,
                    'limit' => 10,
                    'offset' => 0,
                ]),
            );

            // RajaOngkir uses 404 for a valid search with no matching destination.
            if ($response->status() === 404) {
                return [];
            }

            $this->ensureSuccessful($response, 'search_destinations');
            $data = $response->json('data');

            if (! is_array($data)) {
                $this->logFailure('search_destinations', $response->status(), []);

                throw new RajaOngkirException('Respons RajaOngkir tidak valid.', 502);
            }

            $destinations = [];

            foreach ($data as $destination) {
                if (! is_array($destination) || ! isset($destination['id'], $destination['label'])) {
                    continue;
                }

                $id = (string) $destination['id'];
                $destinations[] = [
                    'id' => $id,
                    'subdistrict_id' => $id,
                    'label' => (string) $destination['label'],
                    'city' => (string) ($destination['city_name'] ?? ''),
                    'province' => (string) ($destination['province_name'] ?? ''),
                    'district' => (string) ($destination['district_name'] ?? ''),
                    'subdistrict' => (string) ($destination['subdistrict_name'] ?? ''),
                    'zip' => (string) ($destination['zip_code'] ?? ''),
                ];
            }

            return $destinations;
        });
    }

    /**
     * @return list<array{courier: string, courier_name: string, service: string, description: string, cost: int, etd: string}>
     */
    public function calculateDomesticCost(
        string|int $origin,
        string|int $destination,
        int $weight,
        string $courier,
    ): array {
        $courier = strtolower($courier);
        $cacheKey = sprintf(
            'rajaongkir:cost:%s:%s:%d:%s',
            $origin,
            $destination,
            $weight,
            $courier,
        );

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($origin, $destination, $weight, $courier): array {
            $response = $this->send(
                operation: 'calculate_domestic_cost',
                callback: fn (PendingRequest $request) => $request->asForm()->post('calculate/domestic-cost', [
                    'origin' => $origin,
                    'destination' => $destination,
                    'weight' => $weight,
                    'courier' => $courier,
                ]),
                context: [
                    'origin' => (string) $origin,
                    'destination' => (string) $destination,
                    'weight' => $weight,
                    'courier' => $courier,
                ],
            );

            $this->ensureSuccessful($response, 'calculate_domestic_cost', [
                'origin' => (string) $origin,
                'destination' => (string) $destination,
                'weight' => $weight,
                'courier' => $courier,
            ]);
            $data = $response->json('data');

            if (! is_array($data)) {
                $this->logFailure('calculate_domestic_cost', $response->status(), [
                    'origin' => (string) $origin,
                    'destination' => (string) $destination,
                    'weight' => $weight,
                    'courier' => $courier,
                ]);

                throw new RajaOngkirException('Respons RajaOngkir tidak valid.', 502);
            }

            $options = [];

            foreach ($data as $option) {
                if (! is_array($option) || ! isset($option['service'], $option['cost'])) {
                    continue;
                }

                $options[] = [
                    'courier' => (string) ($option['code'] ?? $courier),
                    'courier_name' => (string) ($option['name'] ?? strtoupper($courier)),
                    'service' => (string) $option['service'],
                    'description' => (string) ($option['description'] ?? ''),
                    'cost' => (int) $option['cost'],
                    'etd' => (string) ($option['etd'] ?? ''),
                ];
            }

            return $options;
        });
    }

    /**
     * Compatibility helper for the existing checkout, which presents several couriers at once.
     *
     * @return list<array{courier: string, courier_name: string, service: string, description: string, cost: int, etd: string}>
     */
    public function getCosts(string|int $origin, string|int $destination, int $weight): array
    {
        $results = [];
        $lastError = null;

        foreach (config('services.rajaongkir.checkout_couriers', ['jne', 'jnt', 'sicepat']) as $courier) {
            try {
                $results = [
                    ...$results,
                    ...$this->calculateDomesticCost($origin, $destination, $weight, (string) $courier),
                ];
            } catch (RajaOngkirException $exception) {
                $lastError = $exception;
            }
        }

        if ($results === [] && $lastError instanceof RajaOngkirException) {
            throw $lastError;
        }

        return $results;
    }

    private function request(): PendingRequest
    {
        if ($this->apiKey === '') {
            Log::error('RajaOngkir API key is not configured.');

            throw new RajaOngkirException('Konfigurasi layanan pengiriman belum lengkap.', 503);
        }

        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withHeaders(['key' => $this->apiKey])
            ->connectTimeout($this->timeout)
            ->timeout($this->timeout);
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     * @param  array<string, scalar>  $context
     */
    private function send(string $operation, callable $callback, array $context = []): Response
    {
        try {
            return $callback($this->request());
        } catch (RajaOngkirException $exception) {
            throw $exception;
        } catch (ConnectionException $exception) {
            $this->logFailure($operation, null, $context);

            throw new RajaOngkirException(
                'Layanan RajaOngkir sedang tidak dapat dihubungi.',
                503,
                $exception,
            );
        } catch (Throwable $exception) {
            $this->logFailure($operation, null, $context);

            throw new RajaOngkirException(
                'Terjadi kesalahan saat menghubungi layanan RajaOngkir.',
                502,
                $exception,
            );
        }
    }

    /**
     * @param  array<string, scalar>  $context
     */
    private function ensureSuccessful(Response $response, string $operation, array $context = []): void
    {
        if ($response->successful()) {
            return;
        }

        $this->logFailure($operation, $response->status(), $context);

        throw new RajaOngkirException('RajaOngkir gagal memproses permintaan.', 502);
    }

    /**
     * Deliberately log only operation metadata. Never log request headers or the API key.
     *
     * @param  array<string, scalar>  $context
     */
    private function logFailure(string $operation, ?int $upstreamStatus, array $context): void
    {
        Log::error('RajaOngkir API request failed.', [
            'operation' => $operation,
            'upstream_status' => $upstreamStatus,
            ...$context,
        ]);
    }
}
