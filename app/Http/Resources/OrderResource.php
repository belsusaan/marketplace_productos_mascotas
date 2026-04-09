<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'               => $this->id,
            'status'           => $this->status,
            'total_amount'     => $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'notes'            => $this->notes,
            'items'            => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'payment'          => new PaymentResource($this->whenLoaded('payment')),
            'delivery'         => new DeliveryResource($this->whenLoaded('delivery')),
            'created_at'       => $this->created_at,
        ];
    }
}
