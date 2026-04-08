<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function available()
    {
        $this->authorize('viewAny', Delivery::class);

        $deliverers = User::role('delivery')->get(['id', 'name', 'email']);

        return response()->json(['data' => $deliverers]);
    }
    public function store(StoreDeliveryRequest $request)
    {
        $this->authorize('create', Delivery::class);

        $order = Order::findOrFail($request->order_id);

        if ($order->delivery) {
            return response()->json(['message' => 'Este pedido ya tiene una entrega asignada.'], 422);
        }

        $delivery = Delivery::create([
            'order_id'    => $order->id,
            'user_id'     => $request->user_id,
            'status'      => Delivery::STATUS_PENDING,
            'assigned_at' => now(),
        ]);

        $order->update(['status' => Order::STATUS_SHIPPED]);

        $delivery->load('user', 'order');

        return new DeliveryResource($delivery);
    }
    public function accept($id)
    {
        $delivery = Delivery::with('order')->findOrFail($id);
        $this->authorize('update', $delivery);
        $delivery->update([
            'status'      => Delivery::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $delivery->order->update(['status' => Order::STATUS_SHIPPED]);
        $delivery->load('user', 'order');

        return new DeliveryResource($delivery);
    }
    public function reject(Request $request, $id)
    {
        $delivery = Delivery::with('order')->findOrFail($id);
        $this->authorize('update', $delivery);
        $delivery->update(['status' => Delivery::STATUS_REJECTED]);

        if ($request->has('user_id')) {
            $newDelivery = Delivery::create([
                'order_id'    => $delivery->order_id,
                'user_id'     => $request->user_id,
                'status'      => Delivery::STATUS_PENDING,
                'assigned_at' => now(),
            ]);

            $newDelivery->load('user', 'order');
            return new DeliveryResource($newDelivery);
        }

        $delivery->load('user', 'order');
        return new DeliveryResource($delivery);
    }
    public function adminDeliveries()
    {
        $this->authorize('viewAny', Delivery::class);

        $deliveries = Delivery::with('user', 'order')->latest()->get();

        return DeliveryResource::collection($deliveries);
    }
}
