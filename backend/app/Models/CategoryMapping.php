<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['boutique_id', 'source_category_ref', 'category_id'])]
class CategoryMapping extends Model
{
    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
