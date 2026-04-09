<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logout revoca el token y usarlo después devuelve 401', function () {
    $user  = User::factory()->create();
    $token = $user->createToken('test_token')->plainTextToken;

    // Logout con el token válido
    $this->withHeader('Authorization', "Bearer $token")
         ->postJson('/api/logout')
         ->assertStatus(200)
         ->assertJson(['message' => 'Sesión cerrada correctamente.']);

    // Forzar que Sanctum re-consulte la DB en el próximo request
    // (el guard cachea el usuario autenticado durante el test)
    $this->app['auth']->forgetGuards();

    // El mismo token ya no debe funcionar
    $this->withHeader('Authorization', "Bearer $token")
         ->getJson('/api/user')
         ->assertStatus(401);
});

test('logout sin token devuelve 401', function () {
    $this->postJson('/api/logout')->assertStatus(401);
});
