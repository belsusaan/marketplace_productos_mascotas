<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Listar productos con filtros y paginación",
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="min_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Lista de productos")
     * )
     */
    public function index()
    {
        $query = Product::with(['category', 'store'])
            ->where('is_active', true);

        if (request('category_id')) {
            $query->where('category_id', request('category_id'));
        }

        if (request('min_price')) {
            $query->where('price', '>=', request('min_price'));
        }

        if (request('max_price')) {
            $query->where('price', '<=', request('max_price'));
        }

        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }

        $perPage = min(request('per_page', 15),50);
        return ProductResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/products",
     *     tags={"Productos"},
     *     summary="Publicar nuevo producto",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category_id","name","price","stock"},
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Collar para perro"),
     *             @OA\Property(property="description", type="string", example="Collar ajustable"),
     *             @OA\Property(property="price", type="number", example=15.99),
     *             @OA\Property(property="stock", type="integer", example=50),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/img.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Producto creado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(CreateProductRequest $request)
    {
        $this->authorize('create', Product::class);
        $store = auth()->user()->store;

        if (!$store) {
            return response()->json(['message' => 'Debes crear una tienda primero'], 422);
        }

        $product = Product::create([
            ...$request->validated(),
            'user_id'  => auth()->id(),
            'store_id' => $store->id,
        ]);

        $this->checkStoreActivation($store);

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Ver detalle de producto",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de producto"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function show($id)
    {
        $product = Product::with(['category', 'store'])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        if (!$product->is_active) {
            return response()->json([
                'message' => 'Producto no disponible'
            ], 404);
        }
        return new ProductResource($product->load(['category', 'store']));
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Editar producto propio",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Collar actualizado"),
     *             @OA\Property(property="price", type="number", example=12.99),
     *             @OA\Property(property="stock", type="integer", example=45),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Producto actualizado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);
        $product->update($request->validated());
        return new ProductResource($product);
    }
    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/products/{id}",
     *     tags={"Productos"},
     *     summary="Eliminar producto propio",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Producto eliminado"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Producto no encontrado")
     * )
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->update(['is_active' => false]);

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/seller/products",
     *     tags={"Productos"},
     *     summary="Listar mis productos como vendedor",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Mis productos"),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */

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
