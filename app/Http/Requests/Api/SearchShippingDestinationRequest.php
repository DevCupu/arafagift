<?php

namespace App\Http\Requests\Api;

class SearchShippingDestinationRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
        ]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['bail', 'required', 'string', 'min:3', 'max:100'],
        ];
    }
}
