<?php

namespace App\Http\Resources;

use App\Models\CustomOrderRequest;
use App\Support\CustomOrderStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomOrderRequest */
class CustomOrderRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stage = CustomOrderStage::forCustomOrderRequest($this->resource);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => __('khidmapp.custom_order_status.'.$this->status),
            'stage' => $stage,
            'stage_label' => $stage !== null ? __('khidmapp.custom_order_stage.'.$stage) : null,
            'stages' => collect(CustomOrderStage::ordered())->map(fn ($key) => [
                'key' => $key,
                'label' => __('khidmapp.custom_order_stage.'.$key),
            ])->all(),
            'address' => new AddressResource($this->whenLoaded('address')),
            'payment_method' => $this->payment_method,
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at,
            'order' => $this->whenLoaded('order', fn () => $this->order !== null ? new OrderResource($this->order) : null),
            'items' => CustomOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
