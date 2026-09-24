<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class ShippingCostRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'courier' => strtolower(trim((string) $this->input('courier', ''))),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'origin' => ['bail', 'required', 'integer', 'min:1'],
            'destination' => ['bail', 'required', 'integer', 'min:1'],
            'weight' => ['bail', 'required', 'integer', 'min:1'],
            'courier' => [
                'bail',
                'required',
                'string',
                Rule::in(config('services.rajaongkir.allowed_couriers', [])),
            ],
        ];
    }
}
