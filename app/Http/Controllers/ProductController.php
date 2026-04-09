<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    use AuthorizesRequests;

    #[OA\Get(
        path: '/products',
        operationId: 'listProducts',
        summary: 'Listar productos con filtros y paginación',
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'category_id', type: 'integer', example: 1),
                            new OA\Property(property: 'store_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Collar para perro'),
                            new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                            new OA\Property(property: 'price', type: 'number', example: 15.99),
                            new OA\Property(property: 'stock', type: 'integer', example: 50),
                            new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
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
        $query = Product::with(['category', 'store'])->where('is_active', true);

        if (request('category_id')) $query->where('category_id', request('category_id'));
        if (request('min_price')) $query->where('price', '>=', request('min_price'));
        if (request('max_price')) $query->where('price', '<=', request('max_price'));
        if (request('search')) $query->where('name', 'like', '%' . request('search') . '%');

        $perPage = min(request('per_page', 15), 50);
        return ProductResource::collection($query->paginate($perPage));
    }

    #[OA\Post(
        path: '/products',
        operationId: 'createProduct',
        summary: 'Publicar nuevo producto',
        tags: ['Productos'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_id','name','price','stock'],
                properties: [
                    new OA\Property(property: 'category_id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Collar para perro'),
                    new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                    new OA\Property(property: 'price', type: 'number', example: 15.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 50),
                    new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto creado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'store_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Collar para perro'),
                        new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                        new OA\Property(property: 'price', type: 'number', example: 15.99),
                        new OA\Property(property: 'stock', type: 'integer', example: 50),
                        new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
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
    public function store(CreateProductRequest $request)
    {
        $this->authorize('create', Product::class);
        $store = auth()->user()->store;

        if (!$store) return response()->json(['message' => 'Debes crear una tienda primero'], 422);

        $product = Product::create([
            ...$request->validated(),
            'user_id'  => auth()->id(),
            'store_id' => $store->id,
        ]);

        $this->checkStoreActivation($store);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/products/{id}',
        operationId: 'showProduct',
        summary: 'Ver detalle de producto',
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle de producto',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'store_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Collar para perro'),
                        new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                        new OA\Property(property: 'price', type: 'number', example: 15.99),
                        new OA\Property(property: 'stock', type: 'integer', example: 50),
                        new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function show($id)
    {
        $product = Product::with(['category', 'store'])->find($id);

        if (!$product) return response()->json(['message' => 'Producto no encontrado'], 404);
        if (!$product->is_active) return response()->json(['message' => 'Producto no disponible'], 404);

        return new ProductResource($product->load(['category', 'store']));
    }

    #[OA\Put(
        path: '/products/{id}',
        operationId: 'updateProduct',
        summary: 'Editar producto propio',
        tags: ['Productos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Collar actualizado'),
                    new OA\Property(property: 'price', type: 'number', example: 12.99),
                    new OA\Property(property: 'stock', type: 'integer', example: 45),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto actualizado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'store_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Collar actualizado'),
                        new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                        new OA\Property(property: 'price', type: 'number', example: 12.99),
                        new OA\Property(property: 'stock', type: 'integer', example: 45),
                        new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);
        $product->update($request->validated());
        return new ProductResource($product);
    }

    #[OA\Delete(
        path: '/products/{id}',
        operationId: 'deleteProduct',
        summary: 'Eliminar producto propio',
        tags: ['Productos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto eliminado',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado correctamente'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
        ]
    )]
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        $product->update(['is_active' => false]);
        return response()->json(['message' => 'Producto eliminado correctamente']);
    }

    #[OA\Get(
        path: '/seller/products',
        operationId: 'myProducts',
        summary: 'Listar mis productos como vendedor',
        tags: ['Productos'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mis productos',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'category_id', type: 'integer', example: 1),
                            new OA\Property(property: 'store_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Collar para perro'),
                            new OA\Property(property: 'description', type: 'string', example: 'Collar ajustable'),
                            new OA\Property(property: 'price', type: 'number', example: 15.99),
                            new OA\Property(property: 'stock', type: 'integer', example: 50),
                            new OA\Property(property: 'image_url', type: 'string', example: 'https://example.com/img.jpg'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-09T12:00:00.000000Z'),
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function myProducts()
    {
        $products = auth()->user()->products()->with(['category', 'store'])->paginate(15);
        return ProductResource::collection($products);
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