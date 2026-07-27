<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche produit détaillée (CDC 7.2.2) : images multiples, description
 * traduite, tailles/couleurs disponibles, prix final en MRU par variante,
 * boutique d'origine, délai estimé. Jamais de prix EUR ni d'URL/référence
 * source.
 *
 * @mixin Product
 */
class PublicProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => $this->images,
            'boutique' => [
                'id' => $this->boutique->id,
                'name' => $this->boutique->name,
                'logo_url' => $this->boutique->logo_url,
                'country_code' => $this->boutique->country_code,
            ],
            'category' => $this->category ? new CategoryResource($this->category) : null,
            'variants' => PublicProductVariantResource::collection($this->variants),
            'delivery_estimate_days' => [
                'min' => config('catalog.delivery_estimate_days_min'),
                'max' => config('catalog.delivery_estimate_days_max'),
            ],
        ];
    }
}
