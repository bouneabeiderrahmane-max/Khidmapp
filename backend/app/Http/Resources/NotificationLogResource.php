<?php

namespace App\Http\Resources;

use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NotificationLog */
class NotificationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'channel' => $this->channel,
            'locale' => $this->locale,
            'template_key' => $this->template_key,
            'title' => $this->title,
            'body' => $this->body,
            'status' => $this->status,
            'error' => $this->error,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
        ];
    }
}
