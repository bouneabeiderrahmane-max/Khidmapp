<?php

namespace App\Http\Resources;

use App\Models\CategoryMapping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategoryMapping */
class CategoryMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_category_ref' => $this->source_category_ref,
            'category' => $this->category ? new CategoryResource($this->category) : null,
        ];
    }
}
