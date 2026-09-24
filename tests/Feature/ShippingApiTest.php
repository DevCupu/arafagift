<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.rajaongkir.key', 'test-api-key');
    config()->set('services.rajaongkir.base_url', 'https://rajaongkir.komerce.id/api/v1');
});

it('successfully searches shipping destinations with the direct search method', function () {
    Http::fake([
        'rajaongkir.komerce.id/*' => Http::response([
            'meta' => ['code' => 200, 'status' => 'success'],
            'data' => [[
                'id' => 17473,
                'label' => 'Bontoala, Makassar, Sulawesi Selatan, 90156',
                'province_name' => 'Sulawesi Selatan',
                'city_name' => 'Makassar',
                'district_name' => 'Bontoala',
                'subdistrict_name' => 'Bontoala',
                'zip_code' => '90156',
            ]],
        ]),
    ]);

    $this->getJson('/api/shipping/destinations?search=Makassar')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.subdistrict_id', '17473')
        ->assertJsonPath('data.0.city', 'Makassar');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_contains($request->url(), '/destination/domestic-destination')
        && $request->hasHeader('key', 'test-api-key')
    );
});

it('successfully calculates shipping cost', function () {
    Http::fake([
        'rajaongkir.komerce.id/*' => Http::response([
            'meta' => ['code' => 200, 'status' => 'success'],
            'data' => [[
                'name' => 'Jalur Nugraha Ekakurir (JNE)',
                'code' => 'jne',
                'service' => 'REG',
                'description' => 'Layanan Reguler',
                'cost' => 18000,
                'etd' => '2-3 day',
            ]],
        ]),
    ]);

    $this->postJson('/api/shipping/cost', [
        'origin' => 4816,
        'destination' => 17473,
        'weight' => 1200,
        'courier' => 'JNE',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.courier_name', 'Jalur Nugraha Ekakurir (JNE)')
        ->assertJsonPath('data.0.service', 'REG')
        ->assertJsonPath('data.0.cost', 18000)
        ->assertJsonPath('data.0.etd', '2-3 day');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && str_contains($request->url(), '/calculate/domestic-cost')
        && $request['origin'] === 4816
        && $request['destination'] === 17473
        && $request['weight'] === 1200
        && $request['courier'] === 'jne'
    );
});

it('returns a consistent response when RajaOngkir fails', function () {
    Http::fake([
        'rajaongkir.komerce.id/*' => Http::response([
            'meta' => ['code' => 500, 'status' => 'error', 'message' => 'Server Error'],
            'data' => null,
        ], 500),
    ]);

    $this->getJson('/api/shipping/destinations?search=Makassar')
        ->assertStatus(502)
        ->assertExactJson([
            'success' => false,
            'message' => 'RajaOngkir gagal memproses permintaan.',
            'data' => null,
        ]);
});

it('rejects invalid shipping cost input', function () {
    Http::fake();

    $this->postJson('/api/shipping/cost', [
        'origin' => 'not-an-id',
        'destination' => 0,
        'weight' => 0,
        'courier' => 'invalid-courier',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null)
        ->assertJsonValidationErrors(['origin', 'destination', 'weight', 'courier']);

    Http::assertNothingSent();
});
