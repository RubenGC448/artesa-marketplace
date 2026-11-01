<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Siembra los 5 roles del sistema Artesa y su catálogo de permisos.
     */
    public function run(): void
    {
        // 1. Limpiar caché de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Catálogo de Permisos del Dominio
        $permissions = [
            // Talleres
            'shops.view',
            'shops.create',
            'shops.update',
            'shops.verify',
            'shops.delete',

            // Catálogo y Productos
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.publish',

            // Pedidos y Envíos
            'orders.view',
            'orders.create',
            'orders.update_status',
            'orders.cancel',

            // Reseñas y Valoraciones
            'reviews.create',
            'reviews.reply',
            'reviews.moderate',
            'reviews.delete',

            // Disputas e Incidencias
            'disputes.open',
            'disputes.reply',
            'disputes.resolve',

            // Liquidaciones y Métricas
            'settlements.view',
            'metrics.view',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 3. Crear los 5 Roles del Sistema
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $artisanRole = Role::firstOrCreate(['name' => 'artisan', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $courierRole = Role::firstOrCreate(['name' => 'courier', 'guard_name' => 'web']);

        // 4. Asignar Permisos a Roles

        // Super Admin: Tiene pase total
        $adminRole->syncPermissions(Permission::all());

        // Moderador: Verificación de talleres, moderación de reseñas y mediación en disputas
        $moderatorRole->syncPermissions([
            'shops.view',
            'shops.verify',
            'products.view',
            'orders.view',
            'reviews.moderate',
            'reviews.delete',
            'disputes.reply',
            'disputes.resolve',
            'metrics.view',
        ]);

        // Artesano: Gestión de su propio taller, catálogo, actualización de sus subpedidos y réplica a reseñas
        $artisanRole->syncPermissions([
            'shops.view',
            'shops.update',
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.publish',
            'orders.view',
            'orders.update_status',
            'reviews.reply',
            'disputes.reply',
            'settlements.view',
        ]);

        // Cliente: Compra, consulta de sus pedidos, apertura de disputas y valoración
        $customerRole->syncPermissions([
            'shops.view',
            'products.view',
            'orders.view',
            'orders.create',
            'orders.cancel',
            'reviews.create',
            'disputes.open',
            'disputes.reply',
        ]);

        // Repartidor: Visualización de albaranes de ruta y actualización de estados de envío
        $courierRole->syncPermissions([
            'orders.view',
            'orders.update_status',
        ]);
    }
}
