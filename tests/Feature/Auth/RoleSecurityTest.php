<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear roles con el guard 'api' que usa el modelo User
    Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'api']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
});

test('usuario con rol buyer intenta acceder a ruta admin y obtiene 403', function () {
    $user = User::factory()->create();
    $user->assignRole('buyer');

    $this->actingAs($user, 'sanctum')
         ->getJson('/api/admin/test')
         ->assertStatus(403);
});

test('usuario con rol admin accede a ruta admin y obtiene 200', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user, 'sanctum')
         ->getJson('/api/admin/test')
         ->assertStatus(200);
});

test('usuario sin rol intenta acceder a ruta admin y obtiene 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
         ->getJson('/api/admin/test')
         ->assertStatus(403);
});
