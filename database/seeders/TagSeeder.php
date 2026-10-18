<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Barro Rojo Tradicional',
            'Madera de Olivo Centenario',
            'Lino Natural Ecológico',
            'Cuero Curtido Vegetal',
            'Plata de Ley 925',
            'Cera de Abeja Virgen',
            'Esparto Hilado a Mano',
            'Lana Merina Española',
            'Vidrio Soplado Reciclado',
            'Pigmentos Minerales Naturales',
            'Certificado Artesanía Andaluza',
            'Denominación de Origen Bailén',
            'Piel de Ubrique',
            'Filigrana Cordobesa',
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($tag)],
                ['name' => $tag]
            );
        }
    }
}
