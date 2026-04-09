<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registro exitoso devuelve 201 con token y datos del usuario', function () {
    $response = $this->postJson('/api/register', [
        'name'                  => 'Juan Pérez',
        'email'                 => 'juan@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
        'phone'                 => '+54 9 11 1234-5678',
        'address'               => 'Av. Corrientes 1234, CABA',
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure([
                 'token',
                 'user' => ['id', 'name', 'email', 'phone', 'address', 'roles', 'created_at'],
             ]);

    $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
});

test('registro sin email duplicado devuelve 422', function () {
    User::factory()->create(['email' => 'duplicado@example.com']);

    $response = $this->postJson('/api/register', [
        'name'                  => 'Otro Usuario',
        'email'                 => 'duplicado@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('registro sin campos requeridos devuelve 422', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password']);
});
