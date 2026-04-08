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
    public function index()
    {
        $stores = Store::where('is_active', true)->get(['id', 'name', 'description']);
        return StoreResource::collection($stores);
    }

    /**
     * Store a newly created resource in storage.
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
    public function update(UpdateStoreRequest $request, Store $store)
    {
        $this->authorize('update', $store);
        $store->update($request->validated());
        $this->checkStoreActivation($store);
        return new StoreResource($store);
    }

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
