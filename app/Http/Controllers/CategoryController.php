<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Resources\CategoryResource;


class CategoryController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     tags={"Categorías"},
     *     summary="Listar todas las categorías",
     *     @OA\Response(response=200, description="Lista de categorías")
     * )
     */

    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        return CategoryResource::collection($categories);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     tags={"Categorías"},
     *     summary="Crear categoría",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Alimentos"),
     *             @OA\Property(property="description", type="string", example="Comida para mascotas"),
     *             @OA\Property(property="slug", type="string", example="alimentos"),
     *             @OA\Property(property="image_url", type="string", example="https://example.com/img.jpg"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoría creada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(CreateCategoryRequest $request)
    {
        $this->authorize('create', Category::class);
        $category = Category::create($request->validated());
        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     tags={"Categorías"},
     *     summary="Ver detalle de categoría",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Detalle de categoría"),
     *     @OA\Response(response=404, description="Categoría no encontrada")
     * )
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'message' => 'Categoría no encontrada'
            ],  404);
    }
        if (!$category->is_active) {
            return response()->json([
                'message' => 'Categoría no disponible'
            ], 404);
        }

        return new CategoryResource($category);
    }

   /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     tags={"Categorías"},
     *     summary="Editar categoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Alimentos Premium"),
     *             @OA\Property(property="description", type="string", example="Comida premium"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Categoría actualizada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Categoría no encontrada")
     * )
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);
        $category->update($request->validated());
        return new CategoryResource($category);
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     tags={"Categorías"},
     *     summary="Eliminar categoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoría eliminada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Categoría no encontrada")
     * )
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->update(['is_active' => false]);

        return response()->json([
            'message' => 'Categoría eliminada correctamente'
        ]);
    }
}
