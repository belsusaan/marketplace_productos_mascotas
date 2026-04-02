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
        $manageCategories = Permission::create(['name' => 'manage categories']);
        $manageProducts = Permission::create(['name' => 'manage products']);
        $buyProducts = Permission::create(['name' => 'buy products']);

        $admin = Role::create(['name' => 'admin']);
        $seller = Role::create(['name' => 'seller']);
        $buyer = Role::create(['name' => 'buyer']);

        $admin->givePermissionTo([$manageCategories, $manageProducts]);
        $seller->givePermissionTo([$manageProducts]);
        $buyer->givePermissionTo($buyProducts)
    }
}
