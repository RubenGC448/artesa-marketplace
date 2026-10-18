<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = [
            'Cerámica y Barro' => [
                'icon' => 'icons/ceramica.svg',
                'children' => [
                    'Vajillas y Platos',
                    'Jarrones y Vasijas',
                    'Azulejos Decorativos',
                ],
            ],
            'Madera y Ebanistería' => [
                'icon' => 'icons/madera.svg',
                'children' => [
                    'Muebles Rústicos',
                    'Tablas y Menaje de Cocina',
                    'Cajas y Baúles',
                ],
            ],
            'Textil y Telar' => [
                'icon' => 'icons/textil.svg',
                'children' => [
                    'Mantas y Plaids de Lana',
                    'Tapices de Pared',
                    'Bufandas y Chales',
                ],
            ],
            'Joyería de Autor' => [
                'icon' => 'icons/joyeria.svg',
                'children' => [
                    'Anillos de Plata',
                    'Colgantes y Gargantillas',
                    'Pendientes de Filigrana',
                ],
            ],
            'Cuero y Marroquinería' => [
                'icon' => 'icons/cuero.svg',
                'children' => [
                    'Bolsos y Mochilas',
                    'Cinturones de Piel',
                    'Carteras y Monederos',
                ],
            ],
        ];

        foreach ($taxonomy as $parentName => $data) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'icon' => $data['icon'],
                    'parent_id' => null,
                ]
            );

            foreach ($data['children'] as $childName) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'name' => $childName,
                        'icon' => null,
                        'parent_id' => $parent->id,
                    ]
                );
            }
        }
    }
}
