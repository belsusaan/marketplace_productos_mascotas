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
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);
        $product->update($request->validated());
        return new ProductResource($product);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->update(['is_active' => false]);

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);
    }

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
