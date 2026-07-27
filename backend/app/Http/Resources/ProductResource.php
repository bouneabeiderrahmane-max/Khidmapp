<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource d'administration uniquement : expose le prix en euros de la
 * boutique source. Le catalogue public (Sprint 5) affichera un prix final
 * en ouguiya calculé par le moteur de prix (Sprint 4), jamais ce prix EUR.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boutique_id' => $this->boutique_id,
            'external_ref' => $this->external_ref,
            'category' => $this->category ? new CategoryResource($this->category) : null,
            'name' => $this->name,
            'description' => $this->description,
            'translation_locked' => $this->translation_locked,
            'images' => $this->images,
            'base_price_eur' => $this->base_price_eur,
            'status' => $this->status,
            'status_label' => __('khidmapp.product_status.'.$this->status),
            'source_url' => $this->source_url,
            'last_synced_at' => $this->last_synced_at,
            'unavailable_since' => $this->unavailable_since,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
