<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $nouns = [
            'Jarrón', 'Cuenco', 'Vajilla', 'Mesa baja', 'Bolsa de hombro', 'Cinturón',
            'Colgante', 'Pendientes', 'Plato decorativo', 'Manta artesana', 'Tapiz',
            'Bandeja', 'Caja de té', 'Escultura', 'Botijo tradicional', 'Lámpara',
        ];

        $adjectives = [
            'esmaltado a mano', 'de olivo rústico', 'curtido al sol', 'de plata pulida',
            'en barro rojo', 'con tintes vegetales', 'de estilo andalusí', 'de diseño minimalista',
            'acabado encerado', 'con filigrana tradicional', 'tejido en telar de bajo lizo',
        ];

        $title = fake()->randomElement($nouns) . ' ' . fake()->randomElement($adjectives);

        return [
            'shop_id' => Shop::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numerify('####'),
            'description' => fake()->paragraph(4),
            'base_price' => fake()->randomFloat(2, 18, 220),
            'stock' => fake()->numberBetween(1, 25),
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'status' => 'active',
            'featured' => fake()->boolean(20),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
