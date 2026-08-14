<?php

namespace App\Http\Resources;

use App\Models\CustomOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomOrderItem */
class CustomOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boutique' => new BoutiqueResource($this->whenLoaded('boutique')),
            'product_url' => $this->product_url,
            'quantity' => $this->quantity,
            'estimated_price_eur' => $this->estimated_price_eur,
            'notes' => $this->notes,
        ];
    }
}
