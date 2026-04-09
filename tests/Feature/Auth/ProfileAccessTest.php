<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GET /api/user sin token devuelve 401', function () {
    $this->getJson('/api/user')->assertStatus(401);
});

test('GET /api/user con token válido devuelve 200 con datos del usuario', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
         ->getJson('/api/user')
         ->assertStatus(200)
         ->assertJsonStructure([
             'id', 'name', 'email', 'phone', 'address', 'roles', 'created_at',
         ]);
});
