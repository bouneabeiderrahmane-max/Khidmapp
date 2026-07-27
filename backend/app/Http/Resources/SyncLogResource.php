<?php

namespace App\Http\Resources;

use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SyncLog */
class SyncLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => __('khidmapp.sync_status.'.$this->status),
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'products_created' => $this->products_created,
            'products_updated' => $this->products_updated,
            'products_disabled' => $this->products_disabled,
            'errors' => $this->errors,
        ];
    }
}
