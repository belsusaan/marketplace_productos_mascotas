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
     * Store a newly created resource in storage.
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
     * Display the specified resource.
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

    public function confirm($id)
    {
        $payment = Payment::findOrFail($id);
        $this->authorize('confirm', $payment);
        $payment->update(['status' => Payment::STATUS_CONFIRMED]);

        return new PaymentResource($payment);
    }

}
