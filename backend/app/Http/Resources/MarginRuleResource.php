<?php

namespace App\Http\Resources;

use App\Models\MarginRule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarginRule */
class MarginRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'percent' => $this->percent,
            'effective_at' => $this->effective_at,
            'created_by' => $this->created_by,
        ];
    }
}
