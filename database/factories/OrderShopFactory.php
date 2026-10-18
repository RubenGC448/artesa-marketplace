<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderShop>
 */
class OrderShopFactory extends Factory
{
    protected $model = OrderShop::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 20, 150);
        $shipping = fake()->randomElement([0.00, 4.50, 6.00]);
        $total = $subtotal + $shipping;

        return [
            'order_id' => Order::factory(),
            'shop_id' => Shop::factory(),
            'status' => fake()->randomElement(['pending', 'preparing', 'ready', 'shipped', 'delivered']),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'total' => $total,
        ];
    }
}
