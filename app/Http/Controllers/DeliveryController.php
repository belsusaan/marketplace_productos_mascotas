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
    /**
     * @OA\Get(
     *     path="/deliveries/available",
     *     tags={"Entregas"},
     *     summary="Ver repartidores disponibles",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Lista de repartidores")
     * )
     */
    public function available()
    {
        $this->authorize('viewAny', Delivery::class);

        $deliverers = User::role('delivery')->get(['id', 'name', 'email']);

        return response()->json(['data' => $deliverers]);
    }

    /**
     * @OA\Post(
     *     path="/deliveries",
     *     tags={"Entregas"},
     *     summary="Crear asignacion de entrega a repartidor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id","user_id"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Entrega asignada"),
     *     @OA\Response(response=422, description="Pedido ya tiene entrega asignada")
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/deliveries/{id}/accept",
     *     tags={"Entregas"},
     *     summary="Repartidor acepta la entrega",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Entrega aceptada")
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/deliveries/{id}/reject",
     *     tags={"Entregas"},
     *     summary="Repartidor rechaza y reasigna entrega",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Entrega rechazada o reasignada")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/admin/deliveries",
     *     tags={"Entregas"},
     *     summary="Ver todas las entregas",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Lista de todas las entregas")
     * )
     */
    public function adminDeliveries()
    {
        $this->authorize('viewAny', Delivery::class);

        $deliveries = Delivery::with('user', 'order')->latest()->get();

        return DeliveryResource::collection($deliveries);
    }
}
