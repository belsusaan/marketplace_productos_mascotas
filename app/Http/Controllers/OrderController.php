<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    #[OA\Get(
        path: '/orders',
        operationId: 'getMyOrders',
        summary: 'Ver mis pedidos',
        description: 'Devuelve todos los pedidos del usuario autenticado (rol buyer), con items, pago y entrega.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de pedidos del comprador',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 150.00),
                                new OA\Property(property: 'status', type: 'string', example: 'pending'),
                                new OA\Property(property: 'shipping_address', type: 'string', example: 'Calle Falsa 123'),
                                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Sin notas'),
                                new OA\Property(property: 'order_items', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'payment', type: 'object', nullable: true),
                                new OA\Property(property: 'delivery', type: 'object', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
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
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with('orderItems.product', 'payment', 'delivery')
            ->where('user_id', request()->user()->id)
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    #[OA\Post(
        path: '/orders',
        operationId: 'storeOrder',
        summary: 'Crear pedido desde el carrito',
        description: 'Convierte el carrito actual en una orden. Descuenta stock, vacía el carrito y retorna la orden creada.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['shipping_address'],
                properties: [
                    new OA\Property(property: 'shipping_address', type: 'string', example: 'Calle Falsa 123'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Sin notas'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pedido creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 150.00),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'shipping_address', type: 'string', example: 'Calle Falsa 123'),
                            new OA\Property(property: 'order_items', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'payment', type: 'object', nullable: true),
                            new OA\Property(property: 'delivery', type: 'object', nullable: true),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Carrito vacío o stock insuficiente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El carrito está vacío.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function store(StoreOrderRequest $request): JsonResponse|OrderResource
    {
        $this->authorize('create', Order::class);

        $cart = Cart::with('cartItems.product')
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'El carrito está vacío.'], 422);
        }

        foreach ($cart->cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
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

        foreach ($cart->cartItems as $item) {
            $order->orderItems()->create([
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal'   => $item->quantity * $item->unit_price,
            ]);
            $item->product->decrement('stock', $item->quantity);
        }

        $cart->cartItems()->delete();
        $order->load('orderItems.product', 'payment', 'delivery');

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/orders/{id}',
        operationId: 'showOrder',
        summary: 'Ver detalle de un pedido',
        description: 'Devuelve el detalle completo de un pedido. Los buyers solo pueden ver sus propios pedidos.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pedido',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del pedido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
                            new OA\Property(property: 'total_amount', type: 'number', format: 'float', example: 150.00),
                            new OA\Property(property: 'order_items', type: 'array', items: new OA\Items(type: 'object')),
                            new OA\Property(property: 'payment', type: 'object', nullable: true),
                            new OA\Property(property: 'delivery', type: 'object', nullable: true),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permisos para ver este pedido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No tienes permiso para esta acción.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Pedido no encontrado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function show(Request $request, $id): JsonResponse|OrderResource
    {
        $order = Order::with('orderItems.product', 'payment', 'delivery')->findOrFail($id);
        $this->authorize('view', $order);

        $user = $request->user();
        if ($user->hasRole('buyer') && $order->user_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para esta acción.'], 403);
        }

        return new OrderResource($order);
    }

    #[OA\Patch(
        path: '/orders/{id}/status',
        operationId: 'updateOrderStatus',
        summary: 'Actualizar estado del pedido',
        description: 'Permite a admin o seller cambiar el estado de un pedido.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pedido',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        enum: ['confirmed', 'shipped', 'delivered', 'cancelled'],
                        example: 'shipped'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'status', type: 'string', example: 'shipped'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permisos para actualizar el estado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Pedido no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function updateStatus(Request $request, $id): OrderResource
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

    #[OA\Get(
        path: '/seller/orders',
        operationId: 'getSellerOrders',
        summary: 'Ver pedidos recibidos como vendedor',
        description: 'Devuelve los pedidos que contienen productos del vendedor autenticado.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de pedidos del vendedor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function sellerOrders(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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

    #[OA\Get(
        path: '/admin/orders',
        operationId: 'getAdminOrders',
        summary: 'Ver todos los pedidos (admin)',
        description: 'Solo accesible por administradores. Devuelve todos los pedidos del sistema.',
        tags: ['Pedidos'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de todos los pedidos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos de administrador'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function adminOrders(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAll', Order::class);

        $orders = Order::with('orderItems.product', 'payment', 'delivery')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }
}