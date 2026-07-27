<?php

namespace App\Http\Resources;

use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderStatusHistory */
class OrderStatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'to_status_label' => __('khidmapp.order_status.'.$this->to_status),
            'actor_type' => $this->actor_type,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
