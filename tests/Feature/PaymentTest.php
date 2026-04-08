<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('registrar pago de un pedido', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_PENDING,
    ]);

    Sanctum::actingAs($buyer);

    $response = $this->postJson('/api/payments', [
        'order_id'        => $order->id,
        'method'          => 'card',
        'transaction_ref' => 'TXN123456',
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.status', Payment::STATUS_PENDING)
             ->assertJsonPath('data.order_id', $order->id);
});

test('confirmar pago como admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_CONFIRMED,
    ]);

    $payment = Payment::create([
        'order_id'        => $order->id,
        'method'          => 'card',
        'amount'          => $order->total_amount,
        'status'          => Payment::STATUS_PENDING,
        'transaction_ref' => 'TXN123456',
        'paid_at'         => now(),
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/payments/{$payment->id}/confirm");

    $response->assertStatus(200)
             ->assertJsonPath('data.status', Payment::STATUS_CONFIRMED);
});

test('confirmar pago como comprador', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_CONFIRMED,
    ]);

    $payment = Payment::create([
        'order_id'        => $order->id,
        'method'          => 'card',
        'amount'          => $order->total_amount,
        'status'          => Payment::STATUS_PENDING,
        'transaction_ref' => 'TXN123456',
        'paid_at'         => now(),
    ]);

    Sanctum::actingAs($buyer);

    $response = $this->patchJson("/api/payments/{$payment->id}/confirm");
    $response->assertStatus(403);
});