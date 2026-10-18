<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        $craftTypes = [
            'Alfarería y Cerámica',
            'Ebanistería y Madera',
            'Marroquinería y Cuero',
            'Joyería de Autor',
            'Textil y Telar',
            'Vidrio Soplado',
            'Forja y Metalistería',
            'Cestería y Esparto',
        ];

        $craft = fake()->randomElement($craftTypes);
        $name = 'Taller ' . fake()->unique()->lastName() . ' ' . explode(' ', $craft)[0];

        // Letra inicial válida para CIF español (Sociedades o personas jurídicas)
        $cifLetter = fake()->randomElement(['B', 'A', 'J', 'G', 'E']);
        $cif = $cifLetter . fake()->unique()->numerify('#######') . fake()->randomElement(['A', 'B', '1', '2']);

        return [
            'user_id' => User::factory()->artisan(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'cif_nif' => $cif,
            'bio' => fake()->paragraph(3),
            'craft_type' => $craft,
            // Coordenadas geográficas centradas en España peninsular
            'latitude' => fake()->latitude(36.5, 43.0),
            'longitude' => fake()->longitude(-7.5, 2.5),
            'is_verified' => true,
            'commission_rate' => 10.00,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
        ]);
    }
}
