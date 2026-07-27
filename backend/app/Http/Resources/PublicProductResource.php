<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche produit "liste" pour le catalogue public. Le client ne voit jamais
 * le prix en euros, l'URL de la boutique source ni la référence externe —
 * Khidmapp est le seul point de contact (CDC section 5).
 *
 * @mixin Product
 */
class PublicProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->images[0] ?? null,
            'boutique' => [
                'id' => $this->boutique->id,
                'name' => $this->boutique->name,
                'logo_url' => $this->boutique->logo_url,
            ],
            'category' => $this->category ? new CategoryResource($this->category) : null,
            'price_from_mru' => $this->price_from_mru,
        ];
    }
}
