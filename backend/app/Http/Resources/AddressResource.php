<?php

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'city' => $this->city,
            'area' => $this->area,
            'geo_lat' => $this->geo_lat,
            'geo_lng' => $this->geo_lng,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
        ];
    }
}
