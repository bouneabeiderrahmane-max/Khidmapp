<?php

namespace App\Http\Resources;

use App\Models\DeliveryFeeTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeliveryFeeTier */
class DeliveryFeeTierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zone' => $this->zone,
            'min_price_mru' => $this->min_price_mru,
            'max_price_mru' => $this->max_price_mru,
            'fee_mru' => $this->fee_mru,
        ];
    }
}
