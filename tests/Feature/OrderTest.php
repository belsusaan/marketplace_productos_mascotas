<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('crear pedido desde el carrito', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $category = Category::factory()->create();
    $store = Store::factory()->create(['user_id' => $seller->id]);

    $product = Product::factory()->create([
        'user_id'     => $seller->id,
        'store_id'    => $store->id,
        'category_id' => $category->id,
        'price'       => 10.00,
        'stock'       => 5,
    ]);

    $cart = Cart::create(['user_id' => $buyer->id]);
    CartItem::create([
        'cart_id'    => $cart->id,
        'product_id' => $product->id,
        'quantity'   => 2,
        'unit_price' => $product->price,
    ]);

    Sanctum::actingAs($buyer);

    $response = $this->postJson('/api/orders', [
        'shipping_address' => 'Calle Falsa 123',
        'notes'            => 'Sin notas',
    ]);

    $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJson(['data' => ['total_amount' => 20]]);
});

test('actualizar estado de pedido como vendedor', function () {
    $seller = User::factory()->create();
    $seller->assignRole('seller');

    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_PENDING,
    ]);

    Sanctum::actingAs($seller);

    $response = $this->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'confirmed',
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.status', 'confirmed');
});

test('actualizar estado de pedido como comprador', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status'  => Order::STATUS_PENDING,
    ]);

    Sanctum::actingAs($buyer);

    $response = $this->patchJson("/api/orders/{$order->id}/status", [
        'status' => 'confirmed',
    ]);

    $response->assertStatus(403);
});
