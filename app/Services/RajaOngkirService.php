<?php

namespace App\Services;

use App\Exceptions\RajaOngkirException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
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
        $normalizedSearch = mb_strtolower(trim($search));

        if (mb_strlen($normalizedSearch) > 4) {
            $matches = $this->fromPrefixCache($normalizedSearch);
            if ($matches !== null) {
                return $matches;
            }
        }

        $searchHash = hash('sha256', $normalizedSearch);
        $cacheKey = 'rajaongkir:destinations:'.$searchHash;
        $fallbackKey = 'rajaongkir:fallback:'.$searchHash;

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $destinations = $this->fetchDestinations($normalizedSearch);
        } catch (RajaOngkirException $exception) {
            // Upstream sedang bermasalah: tampilkan hasil yang pernah berhasil dimuat
            // (last-known) agar pencarian kota tetap berfungsi dan checkout tidak macet.
            $lastKnown = Cache::get($fallbackKey);
            if (is_array($lastKnown)) {
                if (mb_strlen($normalizedSearch) > 4) {
                    $this->saveToPrefixCache($normalizedSearch, $lastKnown);
                }

                return $lastKnown;
            }

            throw $exception;
        }

        Cache::put($cacheKey, $destinations, now()->addWeek());
        Cache::put($fallbackKey, $destinations, now()->addDays(30));

        if (mb_strlen($normalizedSearch) > 4) {
            $this->saveToPrefixCache($normalizedSearch, $destinations);
        }

        return $destinations;
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
        $cacheKey = $this->costCacheKey($origin, $destination, $weight, $courier);

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

            return $this->parseCostResponse($response, $courier, [
                'origin' => (string) $origin,
                'destination' => (string) $destination,
                'weight' => $weight,
            ]);
        });
    }

    /**
     * Compatibility helper for the existing checkout, which presents several couriers at once.
     *
     * @return list<array{courier: string, courier_name: string, service: string, description: string, cost: int, etd: string}>
     */
    public function getCosts(string|int $origin, string|int $destination, int $weight): array
    {
        $this->assertConfigured();

        $couriers = collect(config('services.rajaongkir.checkout_couriers', ['jne', 'jnt', 'sicepat']))
            ->map(fn (string|int $courier) => strtolower((string) $courier))
            ->values()
            ->all();

        $results = [];
        $pendingKeys = [];
        $lastError = null;

        foreach ($couriers as $courier) {
            $cacheKey = $this->costCacheKey($origin, $destination, $weight, $courier);
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $results = [...$results, ...$cached];

                continue;
            }

            $pendingKeys[$courier] = $cacheKey;
        }

        if ($pendingKeys === []) {
            return $results;
        }

        $pendingCouriers = array_keys($pendingKeys);

        try {
            $responses = Http::pool(function (Pool $pool) use ($pendingCouriers, $origin, $destination, $weight): array {
                $requests = [];

                foreach ($pendingCouriers as $courier) {
                    $requests[] = $pool->asForm()
                        ->baseUrl($this->baseUrl)
                        ->acceptJson()
                        ->withHeaders(['key' => $this->apiKey])
                        ->connectTimeout($this->timeout)
                        ->timeout($this->timeout)
                        ->post('calculate/domestic-cost', [
                            'origin' => $origin,
                            'destination' => $destination,
                            'weight' => $weight,
                            'courier' => $courier,
                        ]);
                }

                return $requests;
            });
        } catch (ConnectionException $exception) {
            $this->logFailure('calculate_domestic_cost', null, [
                'origin' => (string) $origin,
                'destination' => (string) $destination,
                'weight' => $weight,
            ]);

            throw new RajaOngkirException('Layanan RajaOngkir sedang tidak dapat dihubungi.', 503, $exception);
        } catch (Throwable $exception) {
            $this->logFailure('calculate_domestic_cost', null, [
                'origin' => (string) $origin,
                'destination' => (string) $destination,
                'weight' => $weight,
            ]);

            throw new RajaOngkirException('Terjadi kesalahan saat menghubungi layanan RajaOngkir.', 502, $exception);
        }

        foreach ($responses as $index => $response) {
            if (! isset($pendingCouriers[$index])) {
                continue;
            }

            if (! $response instanceof Response) {
                $lastError ??= new RajaOngkirException('Layanan RajaOngkir sedang tidak dapat dihubungi.', 503);

                continue;
            }

            try {
                $options = $this->parseCostResponse($response, $pendingCouriers[$index], [
                    'origin' => (string) $origin,
                    'destination' => (string) $destination,
                    'weight' => $weight,
                ]);
                Cache::put($pendingKeys[$pendingCouriers[$index]], $options, now()->addMinutes(15));
                $results = [...$results, ...$options];
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
        $this->assertConfigured();

        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withHeaders(['key' => $this->apiKey])
            ->connectTimeout($this->timeout)
            ->timeout($this->timeout);
    }

    private function assertConfigured(): void
    {
        if ($this->apiKey === '') {
            Log::error('RajaOngkir API key is not configured.');

            throw new RajaOngkirException('Konfigurasi layanan pengiriman belum lengkap.', 503);
        }
    }

    /**
     * @return list<array{id: string, subdistrict_id: string, label: string, city: string, province: string, district: string, subdistrict: string, zip: string}>
     */
    private function fetchDestinations(string $normalizedSearch): array
    {
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
    }

    /**
     * Reuse results already fetched for the same 4-character prefix instead of hitting the API again.
     * Returns null when the bucket does not contain a match, so the caller falls back to upstream.
     *
     * @return list<array{id: string, subdistrict_id: string, label: string, city: string, province: string, district: string, subdistrict: string, zip: string}>|null
     */
    private function fromPrefixCache(string $normalizedSearch): ?array
    {
        $bucket = Cache::get($this->prefixCacheKey(mb_substr($normalizedSearch, 0, 4)));

        if (! is_array($bucket)) {
            return null;
        }

        $matches = array_values(array_filter(
            $bucket,
            fn (array $destination): bool => mb_stripos((string) $destination['label'], $normalizedSearch) !== false,
        ));

        return $matches === [] ? null : $matches;
    }

    /**
     * @param  list<array{id: string, subdistrict_id: string, label: string, city: string, province: string, district: string, subdistrict: string, zip: string}>  $destinations
     */
    private function saveToPrefixCache(string $normalizedSearch, array $destinations): void
    {
        $bucketKey = $this->prefixCacheKey(mb_substr($normalizedSearch, 0, 4));
        $bucket = Cache::get($bucketKey);
        $bucket = is_array($bucket) ? $bucket : [];

        foreach ($destinations as $destination) {
            $bucket[$destination['id']] = $destination;
        }

        Cache::put($bucketKey, $bucket, now()->addDay());
    }

    private function prefixCacheKey(string $prefix): string
    {
        return 'rajaongkir:prefix:'.$prefix;
    }

    private function costCacheKey(string|int $origin, string|int $destination, int $weight, string $courier): string
    {
        return sprintf('rajaongkir:cost:%s:%s:%d:%s', $origin, $destination, $weight, $courier);
    }

    /**
     * @param  array<string, scalar>  $context
     * @return list<array{courier: string, courier_name: string, service: string, description: string, cost: int, etd: string}>
     */
    private function parseCostResponse(Response $response, string $courier, array $context = []): array
    {
        $this->ensureSuccessful($response, 'calculate_domestic_cost', $context);
        $data = $response->json('data');

        if (! is_array($data)) {
            $this->logFailure('calculate_domestic_cost', $response->status(), $context);

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
    }

    /**
     * @param  callable(PendingRequest): Response  $callback
     * @param  array<string, scalar>  $context
     */
    private function send(string $operation, callable $callback, array $context = []): Response
    {
        $response = $this->requestUpstream($operation, $callback, $context);

        // Upstream komerce rawan 5xx sekilas yang langsung sukses saat dicoba ulang.
        if ($response->serverError()) {
            Log::warning('RajaOngkir request returned 5xx; retrying once.', [
                'operation' => $operation,
                'upstream_status' => $response->status(),
            ]);
            usleep(150_000);

            $response = $this->requestUpstream($operation, $callback, $context);
        }

        return $response;
    }

    private function requestUpstream(string $operation, callable $callback, array $context = []): Response
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
