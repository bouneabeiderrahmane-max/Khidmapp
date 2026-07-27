<?php

namespace App\Http\Resources;

use App\Models\ComplaintMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ComplaintMessage */
class ComplaintMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachments = $this->attachments ?? [];

        return [
            'id' => $this->id,
            'sender_type' => $this->sender_type,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender?->name),
            'message' => $this->message,
            'attachment_urls' => collect($attachments)
                ->map(fn ($_, int $index) => route('complaint-messages.attachment', ['message' => $this->id, 'index' => $index]))
                ->values(),
            'created_at' => $this->created_at,
        ];
    }
}
