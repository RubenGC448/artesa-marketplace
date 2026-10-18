<?php

namespace Database\Factories;

use App\Models\OrderShop;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $carrier = fake()->randomElement(['MRW', 'SEUR', 'Correos Express', 'GLS', 'Reparto Local']);

        return [
            'order_shop_id' => OrderShop::factory(),
            'carrier' => $carrier,
            'tracking_number' => strtoupper(substr($carrier, 0, 3)) . '-' . fake()->numerify('##########'),
            'status' => 'in_transit',
            'shipped_at' => now()->subDay(),
            'delivered_at' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
}
