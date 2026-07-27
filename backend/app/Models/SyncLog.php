<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'errors' => 'array',
        ];
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }
}
