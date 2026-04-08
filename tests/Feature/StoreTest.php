<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

function createSellerStore(): User
{
    $user = User::factory()->create();
    $user->assignRole('seller');
    return $user;
}

function createBuyerStore(): User
{
    $user = User::factory()->create();
    $user->assignRole('buyer');
    return $user;
}

test('crear perfil de tienda como vendedor', function () {
    $seller = createSellerStore();
    Sanctum::actingAs($seller);

    $response = $this->postJson('/api/stores', [
        'name'=> 'Tienda de productos para Mascotas',
        'description' => 'La mejor tienda',
        'logo_url'=> 'https://example.com/logo.png',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('stores', ['name' => 'Tienda de productos para Mascotas']);
});

test('crear perfil de tienda como comprador', function () {
    $buyer = createBuyerStore();
    Sanctum::actingAs($buyer);

    $response = $this->postJson('/api/stores', [
        'name'=> 'Mi Tienda',
        'description'=> 'Descripción',
    ]);

    $response->assertStatus(403);
});
