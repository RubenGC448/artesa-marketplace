<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 25, 250);
        $shipping = fake()->randomElement([0.00, 4.50, 6.95, 9.50]);
        $total = $subtotal + $shipping;

        return [
            'order_number' => fake()->unique()->numerify('ART-2026-######'),
            'customer_id' => User::factory(),
            'address_id' => Address::factory(),
            'status' => fake()->randomElement(['pending', 'paid', 'preparing', 'shipped', 'delivered']),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total' => $total,
            'notes' => fake()->boolean(25) ? fake()->sentence() : null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
