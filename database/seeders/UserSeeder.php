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

        // 1. Super Administrador
        $admin = User::firstOrCreate(
            ['email' => 'admin@artesa.local'],
            [
                'name' => 'Administrador Artesa',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'phone' => '600100200',
            ]
        );

        // 2. Moderador de Contenidos y Disputas
        $moderator = User::firstOrCreate(
            ['email' => 'moderador@artesa.local'],
            [
                'name' => 'Moderador Artesa',
                'password' => $defaultPassword,
                'email_verified_at' => now(),
                'phone' => '600100300',
            ]
        );

        // 3. Los 5 Artesanos titulares de talleres
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
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $defaultPassword,
                    'email_verified_at' => now(),
                    'phone' => $data['phone'],
                    'telegram_chat_id' => $data['telegram_chat_id'],
                ]
            );
        }

        // 4. Clientes compradores con sus direcciones de entrega
        $clients = User::factory()->count(10)->create();

        foreach ($clients as $client) {
            // Dirección predeterminada obligatoria
            Address::factory()->default()->create([
                'user_id' => $client->id,
                'name' => $client->name,
            ]);

            // En el 50% de los casos, añadimos una segunda dirección (ej: segunda residencia u oficina)
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
