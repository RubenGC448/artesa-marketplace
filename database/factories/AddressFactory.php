<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Modelo asociado a esta fábrica.
     */
    protected $model = Address::class;

    /**
     * Definición del molde base de una dirección postal española.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Si no se le pasa un user_id, fabrica automáticamente un usuario nuevo
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'street' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->numerify('#####'),
            'country' => 'ES',
            'phone' => fake()->numerify('6########'),
            'is_default' => false,
        ];
    }

    /**
     * Estado: marcar como la dirección predeterminada del usuario.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
