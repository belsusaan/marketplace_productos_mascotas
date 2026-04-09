<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/categories',
        operationId: 'listCategories',
        summary: 'Listar todas las categorías activas',
        description: 'Devuelve todas las categorías que tienen is_active = true.',
        tags: ['Categorías'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorías',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor')
        ]
    )]
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        return CategoryResource::collection($categories);
    }

    #[OA\Post(
        path: '/categories',
        operationId: 'createCategory',
        summary: 'Crear nueva categoría',
        description: 'Crea una nueva categoría. Requiere autorización.',
        tags: ['Categorías'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Categoría creada exitosamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function store(CreateCategoryRequest $request)
    {
        $this->authorize('create', Category::class);
        $category = Category::create($request->validated());
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/categories/{id}',
        operationId: 'showCategory',
        summary: 'Ver detalle de categoría',
        tags: ['Categorías'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle de categoría',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada'], 404);
        }

        if (!$category->is_active) {
            return response()->json(['message' => 'Categoría no disponible'], 404);
        }

        return new CategoryResource($category);
    }

    #[OA\Put(
        path: '/categories/{id}',
        operationId: 'updateCategory',
        summary: 'Actualizar categoría',
        tags: ['Categorías'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium Actualizado'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría actualizada exitosamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Alimentos Premium Actualizado'),
                        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Comida premium'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);
        $category->update($request->validated());
        return new CategoryResource($category);
    }

    #[OA\Delete(
        path: '/categories/{id}',
        operationId: 'deleteCategory',
        summary: 'Eliminar categoría',
        tags: ['Categorías'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoría eliminada correctamente',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Categoría eliminada correctamente'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Categoría no encontrada'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);
        $category->update(['is_active' => false]);
        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}