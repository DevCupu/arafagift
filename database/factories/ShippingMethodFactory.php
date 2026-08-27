<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected $model = ShippingMethod::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('ship-????'),
            'name' => 'Reguler',
            'eta' => '2-3 hari',
            'price' => 22000,
        ];
    }
}
