<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'rating' => fake()->numberBetween(1, 5),
            'quote' => fake()->sentence(12),
            'name' => fake()->name(),
            'city' => fake()->city(),
            'context' => null,
        ];
    }
}
