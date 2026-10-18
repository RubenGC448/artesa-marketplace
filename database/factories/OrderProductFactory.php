<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderProduct>
 */
class OrderProductFactory extends Factory
{
    protected $model = OrderProduct::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 15, 120);

        return [
            'order_id' => Order::factory(),
            'order_shop_id' => OrderShop::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => $unitPrice,
            'applied_tax' => 21.00,
            'artisan_commission_rate' => 10.00,
            'line_status' => fake()->randomElement(['pending', 'preparing', 'ready']),
            'customization_notes' => fake()->boolean(20) ? 'Personalizado con iniciales grabadas' : null,
        ];
    }
}
