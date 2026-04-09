<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // Primero, necesitamos usuarios que sean vendedores
        $sellers = User::where('email', 'seller@demo.com')->get();

        $storesData = [
            ['name' => 'Tienda Peludos', 'description' => 'Todo para tus mascotas', 'logo_url' => null, 'is_active' => true],
            ['name' => 'Mascotas Felices', 'description' => 'Alimentos y accesorios de calidad', 'logo_url' => null, 'is_active' => true],
        ];

        foreach ($sellers as $index => $seller) {
            $data = $storesData[$index % count($storesData)];
            Store::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['user_id' => $seller->id])
            );
        }
    }
}