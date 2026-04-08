<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

function createAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    return $user;
}

function createSeller(): User
{
    $user = User::factory()->create();
    $user->assignRole('seller');
    return $user;
}


test('crear categoria como admin', function () {
    $admin = createAdmin();
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/categories', [
        'name'=> 'Alimentos',
        'description'=> 'Comida para mascotas',
        'slug'=> 'alimentos',
        'is_active'=> true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('categories', ['name' => 'Alimentos']);
});

test('crear categoria como vendedor', function () {
    $seller = createSeller();
    Sanctum::actingAs($seller);

    $response = $this->postJson('/api/categories', [
        'name'=> 'Alimentos',
        'description'=> 'Comida para mascotas',
    ]);

    $response->assertStatus(403);
});

