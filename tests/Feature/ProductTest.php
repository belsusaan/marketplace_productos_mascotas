<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

function createSellerProduct(): User
{
    $user = User::factory()->create();
    $user->assignRole('seller');
    return $user;
}

function createBuyerProduct(): User
{
    $user = User::factory()->create();
    $user->assignRole('buyer');
    return $user;
}

function createAdminProduct(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');
    return $user;
}

test('listar productos publicamente sin autenticacion', function () {
    Product::factory()->count(3)->create();

    $response = $this->getJson('/api/products');

    $response->assertStatus(200);
});

test('crear producto como vendedor', function () {
    $seller = createSellerProduct();
    $store = Store::factory()->create(['user_id' => $seller->id]);
    $category = Category::factory()->create();
    Sanctum::actingAs($seller);

    $response = $this->postJson('/api/products', [
        'category_id' => $category->id,
        'name'=> 'Collar para perro',
        'description'=> 'Collar ajustable',
        'price'=> 15.99,
        'stock'=> 50,
        'is_active'=> true,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('products', ['name' => 'Collar para perro']);
});

test('crear producto como comprador', function () {
    $buyer = createBuyerProduct();
    $category = Category::factory()->create();
    Sanctum::actingAs($buyer);

    $response = $this->postJson('/api/products', [
        'category_id' => $category->id,
        'name'=> 'Collar para perro',
        'price'=> 15.99,
        'stock'=> 50,
    ]);

    $response->assertStatus(403);
});

test('editar producto de otro vendedor', function () {
    $seller = createSellerProduct();
    $otroVendedor = createSellerProduct();
    $store = Store::factory()->create(['user_id' => $otroVendedor->id]);
    $product = Product::factory()->create([
        'user_id'=> $otroVendedor->id,
        'store_id'=> $store->id,
    ]);
    Sanctum::actingAs($seller);

    $response = $this->putJson("/api/products/{$product->id}", [
        'price' => 5.00,
    ]);

    $response->assertStatus(403);
});

test('vendedor intenta eliminar producto de otro vendedor', function () {
    $seller = createSellerProduct();
    $otroVendedor = createSellerProduct();
    $store = Store::factory()->create(['user_id' => $otroVendedor->id]);
    $product = Product::factory()->create([
        'user_id'=> $otroVendedor->id,
        'store_id'=> $store->id,
    ]);
    Sanctum::actingAs($seller);

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(403);
});