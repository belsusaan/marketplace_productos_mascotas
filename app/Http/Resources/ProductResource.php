<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price'=> $this->price,
            'stock'=> $this->stock,
            'image_url'=> $this->image_url,
            'is_active'=> $this->is_active,
            'category'=> new CategoryResource($this->whenLoaded('category')),
            'store'=> new StoreResource($this->whenLoaded('store')),
            'created_at'=> $this->created_at,
        ];
    }
}
