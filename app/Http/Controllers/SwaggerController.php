<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Marketplace de Productos para Mascotas API",
 *     version="1.0.0",
 *     description="API REST para marketplace de mascotas"
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api",
 *     description="Servidor local"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format: Bearer {token}"
 * )
 */
class SwaggerController extends Controller
{
}