<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $shops = [
            [
                'email' => 'alfarero@artesa.local',
                'name' => 'Taller Cerámica Al-Ándalus',
                'slug' => 'taller-ceramica-al-andalus',
                'cif_nif' => 'B41098765',
                'craft_type' => 'Alfarería y Cerámica',
                'bio' => 'Taller tradicional sevillano dedicado a la alfarería en torno y azulejería con técnica de cuerda seca y arista.',
                'latitude' => 37.3890924,
                'longitude' => -5.9844589, // Sevilla
                'is_verified' => true,
                'commission_rate' => 10.00,
            ],
            [
                'email' => 'ebanista@artesa.local',
                'name' => 'Ebanistería Olivo Vivo',
                'slug' => 'ebanisteria-olivo-vivo',
                'cif_nif' => 'B18234567',
                'craft_type' => 'Ebanistería y Madera',
                'bio' => 'Piezas únicas de mobiliario y menaje talladas en madera recuperada de olivo centenario con acabado en cera de abeja.',
                'latitude' => 37.1773363,
                'longitude' => -3.5985571, // Granada
                'is_verified' => true,
                'commission_rate' => 10.00,
            ],
            [
                'email' => 'marroquinero@artesa.local',
                'name' => 'Cueros y Curtidos del Sur',
                'slug' => 'cueros-y-curtidos-del-sur',
                'cif_nif' => 'B11456789',
                'craft_type' => 'Marroquinería y Cuero',
                'bio' => 'Marroquinería artesanal en Ubrique con curtición vegetal pura y cosido guarnicionero tradicional a dos agujas.',
                'latitude' => 36.6781234,
                'longitude' => -5.4498765, // Ubrique
                'is_verified' => true,
                'commission_rate' => 10.00,
            ],
            [
                'email' => 'joyero@artesa.local',
                'name' => 'Joyería Filigrana Real',
                'slug' => 'joyeria-filigrana-real',
                'cif_nif' => 'B14567890',
                'craft_type' => 'Joyería de Autor',
                'bio' => 'Joyería contemporánea inspirada en la filigrana cordobesa, engastada a mano en plata de ley 925 y piedras semipreciosas.',
                'latitude' => 37.8881751,
                'longitude' => -4.7793835, // Córdoba
                'is_verified' => true,
                'commission_rate' => 10.00,
            ],
            [
                'email' => 'textil@artesa.local',
                'name' => 'Telar y Lana Merina',
                'slug' => 'telar-y-lana-merina',
                'cif_nif' => 'B37654321',
                'craft_type' => 'Textil y Telar',
                'bio' => 'Tejidos en telar manual de bajo lizo utilizando lana merina de ganadería extensiva y tintes extraídos de plantas silvestres.',
                'latitude' => 40.9701039,
                'longitude' => -5.6635397, // Salamanca
                'is_verified' => true,
                'commission_rate' => 10.00,
            ],
        ];

        foreach ($shops as $data) {
            $user = User::where('email', $data['email'])->first();

            if ($user) {
                Shop::firstOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'user_id' => $user->id,
                        'name' => $data['name'],
                        'cif_nif' => $data['cif_nif'],
                        'craft_type' => $data['craft_type'],
                        'bio' => $data['bio'],
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'is_verified' => $data['is_verified'],
                        'commission_rate' => $data['commission_rate'],
                    ]
                );
            }
        }
    }
}
