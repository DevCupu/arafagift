<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('PROMO##??')),
            'type' => 'Potongan 10%',
            'usage' => '0 / 100',
            'period' => '1-31 Ags 2026',
            'status' => 'active',
        ];
    }
}
