<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $categories = Category::all();

        if ($stores->isEmpty() || $categories->isEmpty()) {
            $this->command->info('No hay tiendas o categorías disponibles para crear productos.');
            return;
        }

        // Datos básicos de productos de ejemplo
        $productsData = [
            ['name' => 'Collar de cuero', 'description' => 'Collar resistente para perros', 'price' => 15.99, 'stock' => 20, 'is_active' => true],
            ['name' => 'Snack Saludable', 'description' => 'Galletas naturales para gatos', 'price' => 5.50, 'stock' => 50, 'is_active' => true],
            ['name' => 'Cepillo de pelo', 'description' => 'Cepillo suave para mascotas', 'price' => 8.75, 'stock' => 30, 'is_active' => true],
        ];

        foreach ($stores as $store) {
            foreach ($productsData as $product) {
                Product::firstOrCreate(
                    ['name' => $product['name'], 'store_id' => $store->id],
                    array_merge(
                        $product,
                        [
                            'user_id' => $store->user_id,
                            'store_id' => $store->id,
                            'category_id' => $categories->random()->id,
                            'image_url' => null
                        ]
                    )
                );
            }
        }
    }
}