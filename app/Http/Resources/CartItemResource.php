<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'product'       =>[
                'id'            => $this->product->id,
                'name'          => $this->product->name,
                'price'         => $this->product->price,
                'stock'         => $this->product->stock,
            ],
            'quantity'      => $this->quantity,
            'unit_price'    => $this->unit_price,
            'subtotal'      => $this->quantity * $this->unit_price,
        ];
    }
}
