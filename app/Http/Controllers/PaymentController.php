<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/payments",
     *     tags={"Pagos"},
     *     summary="Registrar pago de un pedido",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id","method","transaction_ref"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="method", type="string", enum={"cash","card","transfer"}),
     *             @OA\Property(property="transaction_ref", type="string", example="TXN123456")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pago registrado"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Pedido ya pagado")
     * )
     */
    public function store(StorePaymentRequest $request)
    {
        $order = Order::findOrFail($request->order_id);

        if($order->user_id !== $request->user()->id){
            return response()->json([
                'message' => 'No autorizado para pagar esta orden.'
            ], 403);
        }

        $this->authorize('create', Payment::class);

        if($order->payment){
            return response()->json([
                'message' => 'Esta orden ya ha sido pagada.'
            ], 422);
        }

        $payment = Payment::create([
            'order_id'          => $order->id,
            'method'            => $request->input('method'),
            'amount'            => $order->total_amount,
            'status'            => Payment::STATUS_PENDING,
            'transaction_ref'   => $request->transaction_ref,
            'paid_at'           => now(),
        ]);
        
        $order->update(['status' => Order::STATUS_CONFIRMED]);

        return new PaymentResource($payment);
    }

    /**
     * @OA\Get(
     *     path="/payments/{order_id}",
     *     tags={"Pagos"},
     *     summary="Ver estado del pago",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Estado del pago"),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Pago no encontrado")
     * )
     */
    public function show(Request $request, $order_id)
    {
        $order = Order::with('payment')->findOrFail($order_id);

        $this->authorize('view', $order->payment ?? new Payment(['order_id' => $order->id]));

        $user  = $request->user();

        if ($user->hasRole('buyer') && $order->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para ver este pago.'], 403);
        }

        if (!$order->payment) {
            return response()->json(['message' => 'No hay pago registrado para este pedido.'], 404);
        }

        return new PaymentResource($order->payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * @OA\Patch(
     *     path="/payments/{id}/confirm",
     *     tags={"Pagos"},
     *     summary="Confirmar pago manualmente",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Pago confirmado"),
     *     @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function confirm($id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('confirm', $payment);
        $payment->update(['status' => Payment::STATUS_CONFIRMED]);

        return new PaymentResource($payment);
    }

}
