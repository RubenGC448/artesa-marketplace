<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        // 1. Super Administrador (Rol: admin)
        $admin = User::firstOrCreate(
            ['email' => 'admin@artesa.local'],
            [
                'name' => 'Administrador Artesa',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'phone' => '600100200',
            ]
        );
        $admin->syncRoles(['admin']);

        // 2. Moderador de Contenidos y Disputas (Rol: moderator)
        $moderator = User::firstOrCreate(
            ['email' => 'moderador@artesa.local'],
            [
                'name' => 'Moderador Artesa',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'phone' => '600100300',
            ]
        );
        $moderator->syncRoles(['moderator']);

        // 3. Repartidor de Logística Local (Rol: courier)
        $courier = User::firstOrCreate(
            ['email' => 'repartidor@artesa.local'],
            [
                'name' => 'Repartidor Local',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'phone' => '600100400',
            ]
        );
        $courier->syncRoles(['courier']);

        // 4. Los 5 Artesanos titulares de talleres (Rol: artisan)
        $artisans = [
            [
                'name' => 'Mateo Benítez (Alfarero)',
                'email' => 'alfarero@artesa.local',
                'phone' => '611223344',
                'telegram_chat_id' => '101202303',
            ],
            [
                'name' => 'Clara Morales (Ebanista)',
                'email' => 'ebanista@artesa.local',
                'phone' => '622334455',
                'telegram_chat_id' => '202303404',
            ],
            [
                'name' => 'Ignacio Vega (Marroquinero)',
                'email' => 'marroquinero@artesa.local',
                'phone' => '633445566',
                'telegram_chat_id' => '303404505',
            ],
            [
                'name' => 'Sofía Alarcón (Joyera)',
                'email' => 'joyero@artesa.local',
                'phone' => '644556677',
                'telegram_chat_id' => '404505606',
            ],
            [
                'name' => 'Elena Prieto (Tejedora Textil)',
                'email' => 'textil@artesa.local',
                'phone' => '655667788',
                'telegram_chat_id' => '505606707',
            ],
        ];

        foreach ($artisans as $data) {
            $artisan = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'phone' => $data['phone'],
                    'telegram_chat_id' => $data['telegram_chat_id'],
                ]
            );
            $artisan->syncRoles(['artisan']);
        }

        // 5. Clientes compradores con sus direcciones de entrega (Rol: customer)
        $clients = User::factory()->count(10)->create();

        foreach ($clients as $client) {
            $client->syncRoles(['customer']);

            // Dirección predeterminada obligatoria
            Address::factory()->default()->create([
                'user_id' => $client->id,
                'name' => $client->name,
            ]);

            // En el 50% de los casos, añadimos una segunda dirección
            if (fake()->boolean(50)) {
                Address::factory()->create([
                    'user_id' => $client->id,
                    'name' => $client->name . ' (Segunda Residencia)',
                    'is_default' => false,
                ]);
            }
        }
    }
}
