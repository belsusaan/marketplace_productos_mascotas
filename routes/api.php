<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/user',     [AuthController::class, 'me']);
    Route::put('/user',     [AuthController::class, 'update']);
    Route::delete('/user',  [AuthController::class, 'destroy']);
});

// Ruta dummy para tests de autorización por rol — no expone lógica real
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/test', fn() => response()->json(['ok' => true]));
});
