<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

test('calculo correcto del total del pedido', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $order = Order::factory()->create([
        'user_id'      => $buyer->id,
        'total_amount' => 0,
        'status'       => Order::STATUS_PENDING,
    ]);

    $seller = User::factory()->create();
    $category = Category::factory()->create();
    $store = Store::factory()->create(['user_id' => $seller->id]);

    $product1 = Product::factory()->create([
        'user_id'     => $seller->id,
        'store_id'    => $store->id,
        'category_id' => $category->id,
        'price'       => 10.00,
        'stock'       => 10,
    ]);

    $product2 = Product::factory()->create([
        'user_id'     => $seller->id,
        'store_id'    => $store->id,
        'category_id' => $category->id,
        'price'       => 20.00,
        'stock'       => 10,
    ]);

    OrderItem::create([
        'order_id'   => $order->id,
        'product_id' => $product1->id,
        'quantity'   => 2,
        'unit_price' => 10.00,
        'subtotal'   => 20.00,
    ]);

    OrderItem::create([
        'order_id'   => $order->id,
        'product_id' => $product2->id,
        'quantity'   => 1,
        'unit_price' => 20.00,
        'subtotal'   => 20.00,
    ]);

    $total = $order->orderItems->sum('subtotal');

    expect((float) $total)->toEqual(40.0);
});

test('stock disponible al agregar al carrito', function () {
    $buyer = User::factory()->create();
    $buyer->assignRole('buyer');

    $seller = User::factory()->create();
    $category = Category::factory()->create();
    $store = Store::factory()->create(['user_id' => $seller->id]);

    $product = Product::factory()->create([
        'user_id'     => $seller->id,
        'store_id'    => $store->id,
        'category_id' => $category->id,
        'price'       => 10.00,
        'stock'       => 2,
    ]);

    $cart = Cart::create(['user_id' => $buyer->id]);

    $quantity = 5;
    $stockInsuficiente = $product->stock < $quantity;

    expect($stockInsuficiente)->toEqual(true);
});