<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/cart",
     *     tags={"Carrito"},
     *     summary="Ver carrito actual",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=201, description="Carrito del usuario")
     * )
     */
    public function index(Request $request)
    {
        $cart = Cart::with('cartItems.product')
            ->firstOrCreate(['user_id' => $request->user()->id]);

        $this->authorize('view', $cart);

        return new CartResource($cart);
    }
    
    /**
     * @OA\Post(
     *     path="/cart",
     *     tags={"Carrito"},
     *     summary="Agregar item al carrito",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Item agregado al carrito"),
     *     @OA\Response(response=422, description="Stock insuficiente")
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Cart::class);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if($product->stock < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuficiente. Disponible: ' . $product->stock
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        $item = $cart->cartItems()->where('product_id', $product->id)->first();

        if($item){
            $newQty = $item->quantity + $request->quantity;
            if($product->stock < $newQty){
                return response()->json([
                    'message' => 'Stock insuficiente. Disponible: ' . $product->stock
                ], 422);
            }
            $item->update(['quantity' => $newQty]);
        }else{
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
                'unit_price' => $product->price,
            ]);
        }
        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/cart/{item_id}",
     *     tags={"Carrito"},
     *     summary="Actualizar cantidad de item",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="item_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Cantidad actualizada"),
     *     @OA\Response(response=422, description="Stock insuficiente")
     * )
     */
    public function update(UpdateCartItemRequest $request, $item_id)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('update', $cart);

        $item = $cart->cartItems()->findOrFail($item_id);

        if($item->product->stock < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuficiente. Disponible: ' . $item->product->stock
            ], 422);
        }
        $item->update(['quantity' => $request->quantity]);
        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    /**
     * @OA\Delete(
     *     path="/cart/{item_id}",
     *     tags={"Carrito"},
     *     summary="Eliminar item del carrito",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="item_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Item eliminado")
     * )
     */
    public function destroy(Request $request, $item_id)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('delete', $cart);

        $item = $cart->cartItems()->findOrFail($item_id);
        $item->delete();

        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    /**
     * @OA\Delete(
     *     path="/cart",
     *     tags={"Carrito"},
     *     summary="Vaciar carrito completo",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Carrito vaciado")
     * )
     */
    public function clear(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('delete', $cart);

        $cart->cartItems()->delete();

        $cart->load('cartItems.product');

        return new CartResource($cart);
    }
}
