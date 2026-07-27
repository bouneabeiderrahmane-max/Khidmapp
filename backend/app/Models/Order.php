<?php

namespace App\Models;

use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subtotal_eur' => 'decimal:2',
            'exchange_rate_snapshot' => 'decimal:6',
            'subtotal_mru' => 'decimal:2',
            'delivery_fee_snapshot_mru' => 'decimal:2',
            'total_mru' => 'decimal:2',
            'cancellation_fee_applicable' => 'boolean',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdByAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_agent_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('created_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::CANCELLED, OrderStatus::REFUNDED]);
    }

    public function isTerminal(): bool
    {
        return OrderStatus::isTerminal($this->status);
    }
}
