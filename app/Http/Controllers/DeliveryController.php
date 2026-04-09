<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DeliveryController extends Controller
{
    #[OA\Get(
        path: '/deliveries/available',
        operationId: 'getAvailableDeliverers',
        summary: 'Ver repartidores disponibles',
        description: 'Devuelve la lista de usuarios con rol "delivery" disponibles para asignación.',
        tags: ['Entregas'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de repartidores',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 3),
                                new OA\Property(property: 'name', type: 'string', example: 'Carlos Repartidor'),
                                new OA\Property(property: 'email', type: 'string', example: 'carlos@example.com'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function available(): JsonResponse
    {
        $this->authorize('viewAny', Delivery::class);

        $deliverers = User::role('delivery')->get(['id', 'name', 'email']);

        return response()->json(['data' => $deliverers]);
    }

    #[OA\Post(
        path: '/deliveries',
        operationId: 'storeDelivery',
        summary: 'Asignar entrega a un repartidor',
        description: 'Crea una asignación de entrega para un pedido. Cambia el estado del pedido a "shipped". Solo un pedido puede tener una entrega activa.',
        tags: ['Entregas'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'user_id'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer', example: 1),
                    new OA\Property(property: 'user_id', type: 'integer', example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entrega asignada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'order_id', type: 'integer', example: 1),
                            new OA\Property(property: 'user_id', type: 'integer', example: 3),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                            new OA\Property(property: 'user', type: 'object', nullable: true),
                            new OA\Property(property: 'order', type: 'object', nullable: true),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'El pedido ya tiene una entrega asignada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Este pedido ya tiene una entrega asignada.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function store(StoreDeliveryRequest $request): JsonResponse|DeliveryResource
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

    #[OA\Patch(
        path: '/deliveries/{id}/accept',
        operationId: 'acceptDelivery',
        summary: 'Repartidor acepta la entrega',
        description: 'El repartidor asignado acepta la entrega. Cambia el estado a "accepted" y registra el timestamp.',
        tags: ['Entregas'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la entrega',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrega aceptada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'status', type: 'string', example: 'accepted'),
                            new OA\Property(property: 'accepted_at', type: 'string', format: 'date-time', example: '2026-04-08T13:00:00.000000Z'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos para aceptar esta entrega'),
            new OA\Response(response: 404, description: 'Entrega no encontrada'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function accept($id): DeliveryResource
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

    #[OA\Patch(
        path: '/deliveries/{id}/reject',
        operationId: 'rejectDelivery',
        summary: 'Repartidor rechaza la entrega',
        description: 'El repartidor rechaza la entrega. Opcionalmente se puede reasignar a otro repartidor enviando un nuevo user_id.',
        tags: ['Entregas'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la entrega',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'user_id',
                        type: 'integer',
                        nullable: true,
                        description: 'ID del nuevo repartidor. Si se omite, solo se rechaza sin reasignar.',
                        example: 4
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrega rechazada o reasignada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 2),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'user_id', type: 'integer', example: 4),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos para rechazar esta entrega'),
            new OA\Response(response: 404, description: 'Entrega no encontrada'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function reject(Request $request, $id): DeliveryResource
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

    #[OA\Get(
        path: '/admin/deliveries',
        operationId: 'getAdminDeliveries',
        summary: 'Ver todas las entregas (admin)',
        description: 'Solo accesible por administradores. Devuelve todas las entregas del sistema con usuario y orden asociados.',
        tags: ['Entregas'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de todas las entregas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'order_id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 3),
                                new OA\Property(property: 'status', type: 'string', example: 'accepted'),
                                new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                                new OA\Property(property: 'accepted_at', type: 'string', format: 'date-time', nullable: true, example: '2026-04-08T13:00:00.000000Z'),
                                new OA\Property(property: 'user', type: 'object'),
                                new OA\Property(property: 'order', type: 'object'),
                            ]
                        )),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos de administrador'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function adminDeliveries(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', Delivery::class);

        $deliveries = Delivery::with('user', 'order')->latest()->get();

        return DeliveryResource::collection($deliveries);
    }
}