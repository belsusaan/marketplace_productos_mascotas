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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cart = Cart::with('cartItems.product')
            ->firstOrCreate(['user_id' => $request->user()->id]);

        $this->authorize('view', $cart);

        return new CartResource($cart);
    }

    /**
     * Store a newly created resource in storage.
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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

    public function clear(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('delete', $cart);
        
        $cart->cartItems()->delete();

        $cart->load('cartItems.product');

        return new CartResource($cart);
    }
}
