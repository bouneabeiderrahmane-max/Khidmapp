<?php

namespace App\Http\Resources;

use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Boutique */
class BoutiqueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'base_url' => $this->base_url,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'status' => $this->status,
            'status_label' => __('khidmapp.boutique_status.'.$this->status),
            'default_margin_percent' => $this->when(
                $request->user()?->can('boutiques.manage'),
                $this->default_margin_percent
            ),
            'sync_config' => $this->when(
                $request->user()?->can('boutiques.manage'),
                $this->sync_config
            ),
            'created_at' => $this->created_at,
        ];
    }
}
