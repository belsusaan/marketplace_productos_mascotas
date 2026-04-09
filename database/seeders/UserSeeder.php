<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin Demo',
                'email'    => 'admin@demo.com',
                'password' => Hash::make('password123'),
                'phone'    => '1100000001',
                'address'  => 'Calle Admin 1',
                'role'     => 'admin',
            ],
            [
                'name'     => 'Vendedor Demo',
                'email'    => 'seller@demo.com',
                'password' => Hash::make('password123'),
                'phone'    => '1100000002',
                'address'  => 'Calle Seller 2',
                'role'     => 'seller',
            ],
            [
                'name'     => 'Comprador Demo',
                'email'    => 'buyer@demo.com',
                'password' => Hash::make('password123'),
                'phone'    => '1100000003',
                'address'  => 'Calle Buyer 3',
                'role'     => 'buyer',
            ],
            [
                'name'     => 'Repartidor Demo',
                'email'    => 'delivery@demo.com',
                'password' => Hash::make('password123'),
                'phone'    => '1100000004',
                'address'  => 'Calle Delivery 4',
                'role'     => 'delivery',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->syncRoles([$role]);
        }
    }
}
