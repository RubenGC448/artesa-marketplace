<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $deliveredOrders = Order::where('status', 'delivered')->with(['products', 'customer'])->get();

        $comments = [
            'La calidad de los materiales y el nivel de detalle en el acabado superaron mis expectativas.',
            'Se nota que es una pieza hecha a mano con mimo. Ha llegado perfectamente embalada.',
            'Una maravilla de artesanía local. Queda precioso en el salón y todo el mundo me pregunta.',
            'Envío rápido y atención impecable por parte del artesano. Volveré a comprar seguro.',
            'Totalmente fiel a las fotos. Es un placer apoyar el comercio local y los oficios tradicionales.',
            'El tacto de la pieza y los tintes naturales transmiten una calidez única.',
        ];

        $replies = [
            '¡Muchísimas gracias por tus palabras! Nos alegra enormemente que disfrutes de la pieza en tu hogar.',
            'Gracias por apoyar nuestro taller artesanal. ¡Ha sido un placer elaborarla para ti!',
            'Un fuerte abrazo de parte de todo el equipo del taller. ¡Hasta la próxima!',
        ];

        foreach ($deliveredOrders as $order) {
            foreach ($order->products as $product) {
                // Evitamos duplicar si ya existe review para esta combinación
                Review::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'customer_id' => $order->customer_id,
                        'rating' => fake()->randomElement([4, 5, 5, 5]),
                        'comment' => fake()->randomElement($comments),
                        'artisan_reply' => fake()->boolean(40) ? fake()->randomElement($replies) : null,
                        'is_approved' => true,
                        'created_at' => $order->created_at->addDays(4),
                        'updated_at' => $order->created_at->addDays(4),
                    ]
                );
            }
        }
    }
}
