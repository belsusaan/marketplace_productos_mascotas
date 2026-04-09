<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Categorias
            'manage categories',
            // Tienda
            'create store',
            'edit store',
            'view store',
            // Productos
            'create product',
            'edit own product',
            'delete own product',
            'delete any product',
            'view products',
            // Pedidos
            'create order',
            'view own orders',
            'view all orders',
            'update order status',
            // Pagos
            'create payment',
            'confirm payment',
            'view payment',
            // Entregas
            'manage deliveries',
            'view deliveries',
            // Usuarios
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'api',
            ]);
        }

        $admin  = Role::firstOrCreate(['name' => 'admin',  'guard_name' => 'api']);
        $seller = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'api']);
        $buyer  = Role::firstOrCreate(['name' => 'buyer',  'guard_name' => 'api']);
        $delivery = Role::firstOrCreate(['name' => 'delivery', 'guard_name' => 'api']);

        $admin->givePermissionTo([
            'manage categories',
            'create store',
            'edit store',
            'view store',
            'create product',
            'edit own product',
            'delete own product',
            'delete any product',
            'view products',
            'view own orders',
            'view all orders',
            'update order status',
            'confirm payment',
            'view payment',
            'manage deliveries',
            'view deliveries',
            'manage users',
        ]);

        $seller->givePermissionTo([
            'create store',
            'edit store',
            'view store',
            'create product',
            'edit own product',
            'delete own product',
            'view products',
            'view own orders',
            'update order status',
            'view payment',
        ]);

        $buyer->givePermissionTo([
            'view products',
            'create order',
            'view own orders',
            'create payment',
            'view payment',
        ]);
    }
}
