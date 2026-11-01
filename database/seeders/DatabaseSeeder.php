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
            RoleAndPermissionSeeder::class, // 0. Creación de los 5 roles y matriz de permisos Spatie
            UserSeeder::class,              // 1. Admin, moderador, repartidor, artesanos y compradores con direcciones y roles
            CategorySeeder::class,          // 2. Jerarquía de categorías de artesanía
            TagSeeder::class,               // 3. Materiales y denominaciones de origen
            ShopSeeder::class,              // 4. Talleres artesanos vinculados a los artesanos
            ProductSeeder::class,           // 5. Catálogo con variantes, imágenes y etiquetas
            OrderSeeder::class,             // 6. Pedidos multi-tienda, líneas congeladas, pagos y envíos
            ReviewSeeder::class,            // 7. Reseñas verificadas de compras entregadas
        ]);
    }
}
