<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $allTags = Tag::all();

        // 1. Catálogo Taller Cerámica Al-Ándalus
        $shop1 = Shop::where('slug', 'taller-ceramica-al-andalus')->first();
        if ($shop1) {
            $catVajillas = Category::where('slug', 'vajillas-y-platos')->first();
            $catJarrones = Category::where('slug', 'jarrones-y-vasijas')->first();
            $catAzulejos = Category::where('slug', 'azulejos-decorativos')->first();

            $productsShop1 = [
                [
                    'title' => 'Jarrón Ánfora Esmaltado en Cobalto',
                    'category_id' => $catJarrones?->id,
                    'price' => 65.00,
                    'stock' => 8,
                    'sku' => 'CER-JAR-001',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Tamaño Mediano (25 cm)', 'price_modifier' => 0.00, 'stock' => 5],
                        ['name' => 'Tamaño Grande (35 cm)', 'price_modifier' => 15.00, 'stock' => 3],
                    ],
                ],
                [
                    'title' => 'Juego de Vajilla Rústica 6 Piezas',
                    'category_id' => $catVajillas?->id,
                    'price' => 120.00,
                    'stock' => 4,
                    'sku' => 'CER-VAJ-002',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Esmalte Blanco Antiguo', 'price_modifier' => 0.00, 'stock' => 2],
                        ['name' => 'Esmalte Verde Cobre', 'price_modifier' => 5.00, 'stock' => 2],
                    ],
                ],
                [
                    'title' => 'Cuenco Hondo de Barro Rojo Torneado',
                    'category_id' => $catVajillas?->id,
                    'price' => 22.00,
                    'stock' => 15,
                    'sku' => 'CER-CUE-003',
                    'featured' => false,
                    'variants' => [],
                ],
                [
                    'title' => 'Panel de Azulejos de Arista Nazarí',
                    'category_id' => $catAzulejos?->id,
                    'price' => 135.00,
                    'stock' => 3,
                    'sku' => 'CER-AZU-004',
                    'featured' => false,
                    'variants' => [],
                ],
            ];

            $this->seedProductsForShop($shop1, $productsShop1, $allTags, ['Barro Rojo Tradicional', 'Denominación de Origen Bailén']);
        }

        // 2. Catálogo Ebanistería Olivo Vivo
        $shop2 = Shop::where('slug', 'ebanisteria-olivo-vivo')->first();
        if ($shop2) {
            $catMuebles = Category::where('slug', 'muebles-rusticos')->first();
            $catTablas = Category::where('slug', 'tablas-y-menaje-de-cocina')->first();
            $catCajas = Category::where('slug', 'cajas-y-baules')->first();

            $productsShop2 = [
                [
                    'title' => 'Mesa de Centro en Rodaja de Olivo Macizo',
                    'category_id' => $catMuebles?->id,
                    'price' => 195.00,
                    'stock' => 2,
                    'sku' => 'MAD-MES-001',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Patas de Hierro Negro Mate', 'price_modifier' => 0.00, 'stock' => 1],
                        ['name' => 'Patas de Latón Cepillado', 'price_modifier' => 25.00, 'stock' => 1],
                    ],
                ],
                [
                    'title' => 'Tabla de Corte y Servir en Madera de Olivo',
                    'category_id' => $catTablas?->id,
                    'price' => 45.00,
                    'stock' => 12,
                    'sku' => 'MAD-TAB-002',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Mediana (35x20 cm)', 'price_modifier' => 0.00, 'stock' => 7],
                        ['name' => 'Grande con Ranura (45x25 cm)', 'price_modifier' => 12.00, 'stock' => 5],
                    ],
                ],
                [
                    'title' => 'Caja Joyero con Tapa de Taracea Granadina',
                    'category_id' => $catCajas?->id,
                    'price' => 60.00,
                    'stock' => 6,
                    'sku' => 'MAD-CAJ-003',
                    'featured' => false,
                    'variants' => [],
                ],
            ];

            $this->seedProductsForShop($shop2, $productsShop2, $allTags, ['Madera de Olivo Centenario', 'Cera de Abeja Virgen']);
        }

        // 3. Catálogo Cueros y Curtidos del Sur
        $shop3 = Shop::where('slug', 'cueros-y-curtidos-del-sur')->first();
        if ($shop3) {
            $catBolsos = Category::where('slug', 'bolsos-y-mochilas')->first();
            $catCinturones = Category::where('slug', 'cinturones-de-piel')->first();
            $catCarteras = Category::where('slug', 'carteras-y-monederos')->first();

            $productsShop3 = [
                [
                    'title' => 'Bolso Bandolera en Cuero Envejecido',
                    'category_id' => $catBolsos?->id,
                    'price' => 110.00,
                    'stock' => 5,
                    'sku' => 'CUE-BOL-001',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Color Cuero Natural', 'price_modifier' => 0.00, 'stock' => 3],
                        ['name' => 'Color Marrón Habana', 'price_modifier' => 0.00, 'stock' => 2],
                    ],
                ],
                [
                    'title' => 'Cinturón Clásico con Hebilla de Latón Macizo',
                    'category_id' => $catCinturones?->id,
                    'price' => 42.00,
                    'stock' => 14,
                    'sku' => 'CUE-CIN-002',
                    'featured' => false,
                    'variants' => [
                        ['name' => 'Talla 90 cm', 'price_modifier' => 0.00, 'stock' => 5],
                        ['name' => 'Talla 100 cm', 'price_modifier' => 0.00, 'stock' => 5],
                        ['name' => 'Talla 110 cm', 'price_modifier' => 0.00, 'stock' => 4],
                    ],
                ],
                [
                    'title' => 'Cartera Billetera Cosida a Dos Agujas',
                    'category_id' => $catCarteras?->id,
                    'price' => 38.00,
                    'stock' => 10,
                    'sku' => 'CUE-CAR-003',
                    'featured' => true,
                    'variants' => [],
                ],
            ];

            $this->seedProductsForShop($shop3, $productsShop3, $allTags, ['Cuero Curtido Vegetal', 'Piel de Ubrique']);
        }

        // 4. Catálogo Joyería Filigrana Real
        $shop4 = Shop::where('slug', 'joyeria-filigrana-real')->first();
        if ($shop4) {
            $catAnillos = Category::where('slug', 'anillos-de-plata')->first();
            $catColgantes = Category::where('slug', 'colgantes-y-gargantillas')->first();
            $catPendientes = Category::where('slug', 'pendientes-de-filigrana')->first();

            $productsShop4 = [
                [
                    'title' => 'Anillo Flor de Azahar en Plata de Ley',
                    'category_id' => $catAnillos?->id,
                    'price' => 52.00,
                    'stock' => 8,
                    'sku' => 'JOY-ANI-001',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Talla 12', 'price_modifier' => 0.00, 'stock' => 3],
                        ['name' => 'Talla 14', 'price_modifier' => 0.00, 'stock' => 3],
                        ['name' => 'Talla 16', 'price_modifier' => 0.00, 'stock' => 2],
                    ],
                ],
                [
                    'title' => 'Colgante Filigrana Cordobesa con Cadena',
                    'category_id' => $catColgantes?->id,
                    'price' => 68.00,
                    'stock' => 6,
                    'sku' => 'JOY-COL-002',
                    'featured' => true,
                    'variants' => [],
                ],
                [
                    'title' => 'Pendientes Lágrima Calados a Mano',
                    'category_id' => $catPendientes?->id,
                    'price' => 58.00,
                    'stock' => 9,
                    'sku' => 'JOY-PEN-003',
                    'featured' => false,
                    'variants' => [],
                ],
            ];

            $this->seedProductsForShop($shop4, $productsShop4, $allTags, ['Plata de Ley 925', 'Filigrana Cordobesa']);
        }

        // 5. Catálogo Telar y Lana Merina
        $shop5 = Shop::where('slug', 'telar-y-lana-merina')->first();
        if ($shop5) {
            $catMantas = Category::where('slug', 'mantas-y-plaids-de-lana')->first();
            $catTapices = Category::where('slug', 'tapices-de-pared')->first();
            $catBufandas = Category::where('slug', 'bufandas-y-chales')->first();

            $productsShop5 = [
                [
                    'title' => 'Manta de Cama en Pura Lana Merina de Trashumancia',
                    'category_id' => $catMantas?->id,
                    'price' => 125.00,
                    'stock' => 5,
                    'sku' => 'TEX-MAN-001',
                    'featured' => true,
                    'variants' => [
                        ['name' => 'Gris Natural y Crudo (140x180 cm)', 'price_modifier' => 0.00, 'stock' => 3],
                        ['name' => 'Marrón Pardo Natural (160x200 cm)', 'price_modifier' => 20.00, 'stock' => 2],
                    ],
                ],
                [
                    'title' => 'Tapiz Mural Texturas Naturales en Telar',
                    'category_id' => $catTapices?->id,
                    'price' => 85.00,
                    'stock' => 3,
                    'sku' => 'TEX-TAP-002',
                    'featured' => false,
                    'variants' => [],
                ],
                [
                    'title' => 'Bufanda Chal Tejida con Flecos Manuales',
                    'category_id' => $catBufandas?->id,
                    'price' => 45.00,
                    'stock' => 11,
                    'sku' => 'TEX-BUF-003',
                    'featured' => true,
                    'variants' => [],
                ],
            ];

            $this->seedProductsForShop($shop5, $productsShop5, $allTags, ['Lana Merina Española', 'Lino Natural Ecológico']);
        }
    }

    private function seedProductsForShop(Shop $shop, array $productsData, $allTags, array $tagNames): void
    {
        foreach ($productsData as $data) {
            $product = Product::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'shop_id' => $shop->id,
                    'category_id' => $data['category_id'],
                    'title' => $data['title'],
                    'slug' => Str::slug($data['title']),
                    'description' => "Pieza elaborada artesanalmente por {$shop->name} siguiendo técnicas tradicionales transmitidas entre generaciones.",
                    'base_price' => $data['price'],
                    'stock' => $data['stock'],
                    'status' => 'active',
                    'featured' => $data['featured'],
                ]
            );

            // Variantes
            foreach ($data['variants'] as $v) {
                ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'name' => $v['name'],
                    ],
                    [
                        'sku' => $product->sku . '-' . strtoupper(substr(md5($v['name']), 0, 4)),
                        'price_modifier' => $v['price_modifier'],
                        'stock' => $v['stock'],
                    ]
                );
            }

            // Fotos simuladas de galería
            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'sort_order' => 0],
                ['image_path' => 'products/' . $product->slug . '-1.jpg', 'is_primary' => true]
            );

            ProductImage::firstOrCreate(
                ['product_id' => $product->id, 'sort_order' => 1],
                ['image_path' => 'products/' . $product->slug . '-2.jpg', 'is_primary' => false]
            );

            // Etiquetas (Tags)
            foreach ($tagNames as $index => $tagName) {
                $tag = $allTags->firstWhere('name', $tagName);
                if ($tag && ! $product->tags()->where('tag_id', $tag->id)->exists()) {
                    $product->tags()->attach($tag->id, [
                        'is_primary' => $index === 0,
                        'origin_certified' => true,
                    ]);
                }
            }
        }
    }
}
