<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(50000, 1000000);
        $shippingCost = 22000;

        return [
            'order_number' => 'AGF-'.fake()->unique()->numberBetween(10000, 99999),
            'user_id' => null,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => Arr::random(['Banten', 'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'DI Yogyakarta', 'Bali']),
            'postal_code' => fake()->postcode(),
            'shipping_method_id' => ShippingMethod::factory(),
            'shipping_cost' => $shippingCost,
            'payment_method_id' => PaymentMethod::factory(),
            'status' => 'pending',
            'channel' => 'Website',
            'subtotal' => $subtotal,
            'total' => $subtotal + $shippingCost,
        ];
    }
}
