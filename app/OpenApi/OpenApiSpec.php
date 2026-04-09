<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Anotaciones globales de la especificación OpenAPI.
 * Solo contiene metadatos del spec: Info, Server, SecurityScheme y Tags.
 * Los endpoints van en sus respectivos controllers.
 */
#[OA\Info(
    title: 'Marketplace de Productos para Mascotas API',
    version: '1.0.0',
    description: 'API RESTful para el marketplace de productos para mascotas. Permite gestionar usuarios, productos, tiendas, carritos y pedidos mediante autenticación Bearer Token (Sanctum).',
    contact: new OA\Contact(
        name: 'Equipo Marketplace Mascotas',
        email: 'admin@marketplace-mascotas.test'
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'API local'
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Ingresá el token obtenido en /api/login. Formato: Bearer {token}'
)]
#[OA\Tag(name: 'Auth', description: 'Registro, login, logout y gestión del perfil del usuario autenticado')]
#[OA\Tag(name: 'Users', description: 'Operaciones sobre el perfil del usuario autenticado')]
class OpenApiSpec {}
