<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use App\Http\Resources\StoreResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StoreController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/stores',
        operationId: 'listStores',
        summary: 'Listar tiendas activas',
        tags: ['Tienda'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de tiendas',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Mi Tienda de Mascotas'),
                            new OA\Property(property: 'description', type: 'string', example: 'La mejor tienda'),
                            new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                        ]
                    )
                )
            )
        ]
    )]
    public function index()
    {
        $stores = Store::where('is_active', true)->get(['id', 'name', 'description', 'logo_url', 'is_active', 'created_at']);
        return StoreResource::collection($stores);
    }

    #[OA\Post(
        path: '/stores',
        operationId: 'createStore',
        summary: 'Crear perfil de tienda',
        tags: ['Tienda'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Mi Tienda de Mascotas'),
                    new OA\Property(property: 'description', type: 'string', example: 'La mejor tienda'),
                    new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tienda creada',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Mi Tienda de Mascotas'),
                        new OA\Property(property: 'description', type: 'string', example: 'La mejor tienda'),
                        new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(CreateStoreRequest $request)
    {
        $this->authorize('create', Store::class);
        $store = Store::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return (new StoreResource($store))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/stores/{id}',
        operationId: 'showStore',
        summary: 'Ver perfil de tienda',
        tags: ['Tienda'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle de tienda',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Mi Tienda de Mascotas'),
                        new OA\Property(property: 'description', type: 'string', example: 'La mejor tienda'),
                        new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Tienda no encontrada'),
        ]
    )]
    public function show($id)
    {
        $store = Store::with('products')->find($id);

        if (!$store) return response()->json(['message' => 'Tienda no encontrada'], 404);
        if (!$store->is_active) return response()->json(['message' => 'Tienda no disponible'], 404);

        return new StoreResource($store);
    }

    #[OA\Put(
        path: '/stores/{id}',
        operationId: 'updateStore',
        summary: 'Editar perfil de tienda',
        tags: ['Tienda'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Tienda Actualizada'),
                    new OA\Property(property: 'description', type: 'string', example: 'Nueva descripción'),
                    new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tienda actualizada',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Tienda Actualizada'),
                        new OA\Property(property: 'description', type: 'string', example: 'Nueva descripción'),
                        new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Tienda no encontrada'),
        ]
    )]
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $this->authorize('update', $store);
        $store->update($request->validated());
        $this->checkStoreActivation($store);
        return new StoreResource($store);
    }

    #[OA\Get(
        path: '/seller/store',
        operationId: 'myStore',
        summary: 'Ver mi tienda como vendedor',
        tags: ['Tienda'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mi tienda',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Mi Tienda de Mascotas'),
                        new OA\Property(property: 'description', type: 'string', example: 'La mejor tienda'),
                        new OA\Property(property: 'logo_url', type: 'string', example: 'https://example.com/logo.png'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No tienes tienda creada'),
        ]
    )]
    public function myStore()
    {
        $store = auth()->user()->store()->with('products')->firstOrFail();
        return new StoreResource($store);
    }

    public function destroy(Store $store)
    {
        $this->authorize('update', $store);
        $store->update(['is_active' => false]);
        return response()->json(['message' => 'Tienda desactivada correctamente']);
    }

    private function checkStoreActivation(Store $store)
    {
        $isComplete = $store->name && $store->description;
        $hasProducts = $store->products()->where('is_active', true)->exists();
        if ($isComplete && $hasProducts) {
            $store->update(['is_active' => true]);
        }
    }
}