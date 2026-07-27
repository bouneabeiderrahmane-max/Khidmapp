<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'locale' => $this->locale,
            'roles' => $this->getRoleNames(),
            'is_blocked' => $this->isBlocked(),
            'blocked_at' => $this->blocked_at,
            'blocked_until' => $this->blocked_until,
            'blocked_reason' => $this->blocked_reason,
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
