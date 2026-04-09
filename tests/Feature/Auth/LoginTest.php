<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login con credenciales correctas devuelve 200 con token y datos del usuario', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'token',
                 'user' => ['id', 'name', 'email', 'roles', 'created_at'],
             ]);
});

test('login con contraseña incorrecta devuelve 401', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email'    => $user->email,
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Credenciales inválidas.']);
});

test('login con email inexistente devuelve 401', function () {
    $response = $this->postJson('/api/login', [
        'email'    => 'noexiste@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Credenciales inválidas.']);
});
