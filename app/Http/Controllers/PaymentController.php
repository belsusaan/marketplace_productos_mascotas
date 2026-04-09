<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{
    #[OA\Post(
        path: '/payments',
        operationId: 'storePayment',
        summary: 'Registrar pago de un pedido',
        description: 'Crea un pago asociado a una orden. El monto se toma automáticamente del total de la orden.',
        tags: ['Pagos'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'method', 'transaction_ref'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer', example: 1),
                    new OA\Property(property: 'method', type: 'string', enum: ['cash', 'card', 'transfer'], example: 'card'),
                    new OA\Property(property: 'transaction_ref', type: 'string', example: 'TXN123456'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pago registrado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'order_id', type: 'integer', example: 1),
                            new OA\Property(property: 'method', type: 'string', example: 'card'),
                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00),
                            new OA\Property(property: 'status', type: 'string', example: 'pending'),
                            new OA\Property(property: 'transaction_ref', type: 'string', example: 'TXN123456'),
                            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado para pagar esta orden',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No autorizado para pagar esta orden.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'La orden ya fue pagada o error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Esta orden ya ha sido pagada.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function store(StorePaymentRequest $request): JsonResponse|PaymentResource
    {
        $order = Order::findOrFail($request->order_id);

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No autorizado para pagar esta orden.'
            ], 403);
        }

        $this->authorize('create', Payment::class);

        if ($order->payment) {
            return response()->json([
                'message' => 'Esta orden ya ha sido pagada.'
            ], 422);
        }

        $payment = Payment::create([
            'order_id'        => $order->id,
            'method'          => $request->input('method'),
            'amount'          => $order->total_amount,
            'status'          => Payment::STATUS_PENDING,
            'transaction_ref' => $request->transaction_ref,
            'paid_at'         => now(),
        ]);

        $order->update(['status' => Order::STATUS_CONFIRMED]);

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    #[OA\Get(
        path: '/payments/{order_id}',
        operationId: 'showPayment',
        summary: 'Ver estado del pago de un pedido',
        description: 'Devuelve el pago asociado a la orden indicada. Los buyers solo pueden ver sus propios pagos.',
        tags: ['Pagos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'order_id',
                in: 'path',
                required: true,
                description: 'ID de la orden',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del pago',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'order_id', type: 'integer', example: 1),
                            new OA\Property(property: 'method', type: 'string', example: 'card'),
                            new OA\Property(property: 'amount', type: 'number', format: 'float', example: 150.00),
                            new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
                            new OA\Property(property: 'transaction_ref', type: 'string', example: 'TXN123456'),
                            new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'No autorizado para ver este pago',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No autorizado para ver este pago.'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'No hay pago registrado para este pedido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No hay pago registrado para este pedido.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function show(Request $request, $order_id): JsonResponse|PaymentResource
    {
        $order = Order::with('payment')->findOrFail($order_id);

        $this->authorize('view', $order->payment ?? new Payment(['order_id' => $order->id]));

        $user = $request->user();

        if ($user->hasRole('buyer') && $order->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver este pago.'], 403);
        }

        if (!$order->payment) {
            return response()->json(['message' => 'No hay pago registrado para este pedido.'], 404);
        }

        return new PaymentResource($order->payment);
    }

    #[OA\Patch(
        path: '/payments/{id}/confirm',
        operationId: 'confirmPayment',
        summary: 'Confirmar pago manualmente',
        description: 'Permite a un administrador o vendedor confirmar un pago pendiente.',
        tags: ['Pagos'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del pago',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pago confirmado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permisos para confirmar pagos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This action is unauthorized.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Pago no encontrado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function confirm($id): PaymentResource
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('confirm', $payment);
        $payment->update(['status' => Payment::STATUS_CONFIRMED]);

        return new PaymentResource($payment);
    }
}