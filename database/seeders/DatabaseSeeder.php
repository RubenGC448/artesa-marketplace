<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra y poblamiento ordenado de la base de datos de Artesa.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,       // 1. Admin, moderador, artesanos y compradores con direcciones
            CategorySeeder::class,   // 2. Jerarquía de categorías de artesanía
            TagSeeder::class,        // 3. Materiales y denominaciones de origen
            ShopSeeder::class,       // 4. Talleres artesanos vinculados a los artesanos
            ProductSeeder::class,    // 5. Catálogo con variantes, imágenes y etiquetas
            OrderSeeder::class,      // 6. Pedidos multi-tienda, líneas congeladas, pagos y envíos
            ReviewSeeder::class,     // 7. Reseñas verificadas de compras entregadas
        ]);
    }
}
