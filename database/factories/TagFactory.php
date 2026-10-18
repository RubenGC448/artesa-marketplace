<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $materials = [
            'Barro Rojo Tradicional',
            'Madera de Olivo Centenario',
            'Lino Natural Ecológico',
            'Cuero Curtido Vegetal',
            'Plata de Ley 925',
            'Cera de Abeja Pura',
            'Esparto Hilado a Mano',
            'Lana Merina Virgen',
            'Vidrio Reciclado',
            'Pigmentos Minerales Naturales',
        ];

        $name = fake()->unique()->randomElement($materials);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
