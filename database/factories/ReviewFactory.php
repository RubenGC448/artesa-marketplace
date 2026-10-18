<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $comments = [
            'Una pieza maravillosa, se nota el cariño y la calidad del trabajo artesanal.',
            'Acabados impecables y entrega muy rápida. Volveré a comprar seguro.',
            'Muy satisfecho con la compra. El embalaje venía perfectamente protegido.',
            'Precioso diseño y materiales naturales de primera calidad.',
            'Totalmente fiel a las fotos de la web. Ha sido un regalo perfecto.',
        ];

        return [
            'product_id' => Product::factory(),
            'customer_id' => User::factory(),
            'order_id' => Order::factory(),
            'rating' => fake()->numberBetween(4, 5),
            'comment' => fake()->randomElement($comments),
            'artisan_reply' => fake()->boolean(30) ? '¡Muchas gracias por valorar nuestro trabajo de taller!' : null,
            'is_approved' => true,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }
}
