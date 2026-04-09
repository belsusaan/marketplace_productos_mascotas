<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Store;
use App\Http\Resources\StoreResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/stores",
     *     tags={"Tienda"},
     *     summary="Listar tiendas activas",
     *     @OA\Response(response=200, description="Lista de tiendas")
     * )
     */
    public function index()
    {
        $stores = Store::where('is_active', true)->get(['id', 'name', 'description']);
        return StoreResource::collection($stores);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/stores",
     *     tags={"Tienda"},
     *     summary="Crear perfil de tienda",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Mi Tienda de Mascotas"),
     *             @OA\Property(property="description", type="string", example="La mejor tienda"),
     *             @OA\Property(property="logo_url", type="string", example="https://example.com/logo.png")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Tienda creada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(CreateStoreRequest $request)
    {
        $this->authorize('create', Store::class);
        $store = Store::create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return (new StoreResource($store))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/stores/{id}",
     *     tags={"Tienda"},
     *     summary="Ver perfil de tienda",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de tienda"),
     *     @OA\Response(response=404, description="Tienda no encontrada")
     * )
     */
    public function show($id)
    {
        $store = Store::with('products')->find($id);

    if (!$store) {
        return response()->json([
            'message' => 'Tienda no encontrada'
        ], 404);
    }

    if (!$store->is_active) {
        return response()->json([
            'message' => 'Tienda no disponible'
        ], 404);
    }

    return new StoreResource($store);
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/stores/{id}",
     *     tags={"Tienda"},
     *     summary="Editar perfil de tienda",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Tienda Actualizada"),
     *             @OA\Property(property="description", type="string", example="Nueva descripción"),
     *             @OA\Property(property="logo_url", type="string", example="https://example.com/logo.png"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Tienda actualizada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Tienda no encontrada")
     * )
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $this->authorize('update', $store);
        $store->update($request->validated());
        $this->checkStoreActivation($store);
        return new StoreResource($store);
    }
    
    /**
     * @OA\Get(
     *     path="/seller/store",
     *     tags={"Tienda"},
     *     summary="Ver mi tienda como vendedor",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Mi tienda"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="No tienes tienda creada")
     * )
     */
    public function myStore()
    {
        $store = auth()->user()->store()->with('products')->firstOrFail();
        return new StoreResource($store);
    }

    private function checkStoreActivation(Store $store)
    {
        $isComplete = $store->name && $store->description;
        $hasProducts = $store->products()->where('is_active', true)->exists();

        if ($isComplete && $hasProducts) {
            $store->update(['is_active' => true]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        $this->authorize('update', $store);

    $store->update([
        'is_active' => false
    ]);

    return response()->json([
        'message' => 'Tienda desactivada correctamente'
    ]);
    }
}
