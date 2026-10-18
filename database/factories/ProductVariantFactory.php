<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $options = [
            'Talla S (20 cm)', 'Talla M (30 cm)', 'Talla L (40 cm)',
            'Acabado Nogal Oscuro', 'Acabado Roble Claro', 'Acabado Cera Natural',
            'Esmalte Azul Cobalto', 'Esmalte Verde Olivo', 'Esmalte Terracota',
            'Cuero Marrón Envejecido', 'Cuero Negro Mate', 'Cuero Coñac',
        ];

        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement($options),
            'sku' => fake()->unique()->regexify('VAR-[A-Z0-9]{6}'),
            'price_modifier' => fake()->randomElement([0.00, 5.00, 10.00, 15.00, 20.00, -5.00]),
            'stock' => fake()->numberBetween(1, 10),
        ];
    }
}
