<?php

use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('asignar entrega a repartidor como admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $deliverer = User::factory()->create();
    $deliverer->assignRole('delivery');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_CONFIRMED,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/deliveries', [
        'order_id' => $order->id,
        'user_id'  => $deliverer->id,
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.status', Delivery::STATUS_PENDING);
});

test('asignar entrega como vendedor', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $deliverer = User::factory()->create();
    $deliverer->assignRole('delivery');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_CONFIRMED,
    ]);

    Sanctum::actingAs($seller);

    $response = $this->postJson('/api/deliveries', [
        'order_id' => $order->id,
        'user_id'  => $deliverer->id,
    ]);

    $response->assertStatus(403);
});