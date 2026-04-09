<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Accesorios para Mascotas', 'description' => 'Collares, correas, juguetes y más', 'is_active' => true],
            ['name' => 'Alimentos',                'description' => 'Comida y snacks para perros y gatos', 'is_active' => true],
            ['name' => 'Higiene y Cuidado',        'description' => 'Shampoos, cepillos y productos de limpieza', 'is_active' => true],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
