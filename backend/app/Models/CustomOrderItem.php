<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomOrderItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'estimated_price_eur' => 'decimal:2',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(CustomOrderRequest::class, 'custom_order_request_id');
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    /**
     * Ligne de commande créée à l'approbation (CustomOrderRequestService::
     * approve()) — null tant que la demande est en attente ou si elle a
     * été rejetée.
     */
    public function orderItem(): HasOne
    {
        return $this->hasOne(OrderItem::class);
    }
}
