<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Auth / Perfil del usuario
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/user',     [AuthController::class, 'me']);
    Route::put('/user',     [AuthController::class, 'update']);
    Route::delete('/user',  [AuthController::class, 'destroy']);

    // Carrito
    Route::get('/cart',              [CartController::class, 'index']);
    Route::post('/cart',             [CartController::class, 'store']);
    Route::put('/cart/{item_id}',    [CartController::class, 'update']);
    Route::delete('/cart/{item_id}', [CartController::class, 'destroy']);
    Route::delete('/cart',           [CartController::class, 'clear']);

    // Pedidos
    Route::post('/orders',     [OrderController::class, 'store']);
    Route::get('/orders',      [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Vendedor y Admin
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('role:seller|admin');

    // Vendedor
    Route::get('/seller/orders', [OrderController::class, 'sellerOrders'])
        ->middleware('role:seller');

    // Admin
    Route::get('/admin/orders', [OrderController::class, 'adminOrders'])
        ->middleware('role:admin');

    // Pagos
    Route::post('/payments',               [PaymentController::class, 'store']);
    Route::get('/payments/{order_id}',     [PaymentController::class, 'show']);
    Route::patch('/payments/{id}/confirm', [PaymentController::class, 'confirm'])
        ->middleware('role:admin');

    // Entregas
    Route::middleware('role:admin')->group(function () {
        Route::get('/deliveries/available',     [DeliveryController::class, 'available']);
        Route::post('/deliveries',              [DeliveryController::class, 'store']);
        Route::patch('/deliveries/{id}/accept', [DeliveryController::class, 'accept']);
        Route::patch('/deliveries/{id}/reject', [DeliveryController::class, 'reject']);
        Route::get('/admin/deliveries',         [DeliveryController::class, 'adminDeliveries']);
    });

});

// Ruta dummy para tests de autorización por rol — no expone lógica real
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/test', fn() => response()->json(['ok' => true]));
});