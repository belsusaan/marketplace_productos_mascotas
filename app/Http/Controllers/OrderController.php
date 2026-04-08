<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with('orderItems.product', 'payment', 'delivery')
            ->where('user_id', request()->user()->id)
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $this->authorize('create', Order::class);
        
        $cart = Cart::with('cartItems.product')
            ->where('user_id', $request->user()->id)
            ->first();

        if(!$cart || $cart->cartItems->isEmpty()){
            return response()->json([
                'message' => 'El carrito está vacío.'
            ], 422);
        }

        //Validar stock
        foreach($cart->cartItems as $item){
            if($item->product->stock < $item->quantity){
                return response()->json([
                     'message' => "Stock insuficiente para: {$item->product->name}",
                ], 422);
            }
        }

        $total = $cart->cartItems->sum(fn($item) => $item->quantity * $item->unit_price);

        $order = Order::create([
            'user_id'          => $request->user()->id,
            'total_amount'     => $total,
            'status'           => Order::STATUS_PENDING,
            'shipping_address' => $request->shipping_address,
            'notes'            => $request->notes,
        ]);

        foreach($cart->cartItems as $item){
            $order->orderItems()->create([
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal'   => $item->quantity * $item->unit_price,
            ]);

            //Descontar stock
            $item->product->decrement('stock', $item->quantity);
        }

        //Vaciar carrito
        $cart->cartItems()->delete();

        $order->load('orderItems.product', 'payment', 'delivery');

        return new OrderResource($order);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $order = Order::with('orderItems.product', 'payment', 'delivery')
            ->findOrFail($id);

        $this->authorize('view', $order);

        $user = $request->user();

        if($user->hasRole('buyer') && $order->user_id !== $user->id){
            return response()->json([
                'message' => 'No tienes permiso para esta acción.'
            ], 403);
        }

        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $this->authorize('updateStatus', $order);
        $order->update(['status' => $request->status]);
        $order->load('orderItems.product', 'payment', 'delivery');

        return new OrderResource($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function sellerOrders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $sellerId = $request->user()->id;

        $orders = Order::with('orderItems.product', 'payment', 'delivery')
            ->whereHas('orderItems.product', function ($q) use ($sellerId) {
                $q->where('user_id', $sellerId);
            })
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function adminOrders()
    {
        $this->authorize('viewAll', Order::class);
        
        $orders = Order::with('orderItems.product', 'payment', 'delivery')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }
}
