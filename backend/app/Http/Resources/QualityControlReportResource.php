<?php

namespace App\Http\Resources;

use App\Models\QualityControlReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QualityControlReport */
class QualityControlReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_item_id' => $this->order_item_id,
            'is_conforme' => $this->is_conforme,
            'notes' => $this->notes,
            'reported_by' => $this->whenLoaded('reporter', fn () => $this->reporter->name),
            'created_at' => $this->created_at,
        ];
    }
}
