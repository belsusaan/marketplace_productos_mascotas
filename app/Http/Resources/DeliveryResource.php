<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id'           => $this->id,
            'order_id'     => $this->order_id,
            'deliverer'    => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],
            'status'       => $this->status,
            'assigned_at'  => $this->assigned_at,
            'accepted_at'  => $this->accepted_at,
            'delivered_at' => $this->delivered_at,
        ];
    }
}
