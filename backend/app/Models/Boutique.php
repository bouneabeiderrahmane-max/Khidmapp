<?php

namespace App\Models;

use App\Support\BoutiqueStatus;
use Database\Factories\BoutiqueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'slug', 'logo_url', 'banner_url', 'base_url',
    'country_code', 'currency_code', 'status', 'default_margin_percent', 'sync_config',
])]
class Boutique extends Model
{
    /** @use HasFactory<BoutiqueFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $boutique): void {
            if (empty($boutique->slug)) {
                $boutique->slug = Str::slug($boutique->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'default_margin_percent' => 'decimal:2',
            'sync_config' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BoutiqueStatus::ACTIVE);
    }

    /**
     * Règle CDC 8.1.2 : suppression impossible s'il existe une commande
     * active liée à la boutique. Le modèle Commande n'existe pas encore
     * (Sprint 6) — cette méthode retourne toujours true pour l'instant et
     * DOIT être complétée dès que les commandes existent, avant toute mise
     * en production.
     */
    public function canBeDeleted(): bool
    {
        return true;
    }
}
