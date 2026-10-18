<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dispute>
 */
class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        $reasons = [
            'Producto con desperfectos en el barniz/acabado.',
            'Rotura durante el transporte del paquete.',
            'El color recibido difiere notablemente de la fotografía.',
            'Retraso superior a 10 días respecto a la fecha estimada.',
        ];

        return [
            'order_id' => Order::factory(),
            'customer_id' => User::factory(),
            'shop_id' => Shop::factory(),
            'reason' => fake()->randomElement($reasons),
            'status' => 'open',
            'resolution_notes' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolution_notes' => 'El artesano acordó enviar una reposición sin coste adicional.',
        ]);
    }
}
