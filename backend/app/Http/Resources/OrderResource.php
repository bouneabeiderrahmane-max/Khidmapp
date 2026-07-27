<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => __('khidmapp.order_status.'.$this->status),
            'shipping' => [
                'label' => $this->shipping_label,
                'city' => $this->shipping_city,
                'area' => $this->shipping_area,
                'phone' => $this->shipping_phone,
            ],
            'payment_method' => $this->payment_method,
            'subtotal_mru' => $this->subtotal_mru,
            'delivery_fee_mru' => $this->delivery_fee_snapshot_mru,
            'total_mru' => $this->total_mru,
            'is_manual_order' => $this->created_by_agent_id !== null,
            'cancellation_fee_applicable' => $this->cancellation_fee_applicable,
            'cancellation_reason' => $this->cancellation_reason,
            'refund_reason' => $this->refund_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at,
        ];
    }
}
