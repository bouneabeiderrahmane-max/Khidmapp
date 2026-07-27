<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginRule extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
            'effective_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeEffectiveAsOf(Builder $query, string $scopeType, ?int $scopeId, ?DateTimeInterface $at = null): Builder
    {
        return $query->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('effective_at', '<=', $at ?? now())
            ->orderByDesc('effective_at');
    }
}
