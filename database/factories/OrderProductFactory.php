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

    /**
     * Línea coherente por defecto: el pedido se toma del subpedido y el
     * producto se crea en el taller del subpedido (OrderLineGuard lo exige).
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 15, 120);

        return [
            'order_shop_id' => OrderShop::factory(),
            'order_id' => fn (array $attributes) => OrderShop::find($attributes['order_shop_id'])->order_id,
            'product_id' => fn (array $attributes) => Product::factory()->create([
                'shop_id' => OrderShop::find($attributes['order_shop_id'])->shop_id,
            ])->id,
            'product_variant_id' => null,
            'product_title' => fn (array $attributes) => Product::withTrashed()->find($attributes['product_id'])?->title,
            'variant_name' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => $unitPrice,
            'applied_tax' => 21.00,
            'artisan_commission_rate' => 10.00,
            'line_status' => fake()->randomElement(['pending', 'preparing', 'ready']),
            'customization_notes' => fake()->boolean(20) ? 'Personalizado con iniciales grabadas' : null,
        ];
    }
}
