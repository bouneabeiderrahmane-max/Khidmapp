<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name_snapshot,
            'size' => $this->size,
            'color' => $this->color,
            'quantity' => $this->quantity,
            'unit_price_mru' => $this->unit_price_mru_snapshot,
            'line_subtotal_mru' => round((float) $this->unit_price_mru_snapshot * $this->quantity, 2),
        ];
    }
}
