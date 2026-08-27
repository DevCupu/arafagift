<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = rtrim(fake()->unique()->sentence(3), '.');

        return [
            'category_id' => Category::factory(),
            'unit' => 'pcs',
            'storage_location' => null,
            'supplier_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('AGF-???-##')),
            'price' => fake()->numberBetween(50000, 500000),
            'art' => 'giftset',
            'stock' => fake()->numberBetween(0, 100),
            'low_stock_threshold' => 10,
            'weight' => fake()->numberBetween(100, 2000),
            'status' => 'active',
            'short' => fake()->sentence(),
        ];
    }
}
