<?php

use App\Support\MarginScope;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La marge par défaut est désormais calculée par paliers de prix EUR
 * (PriceMarginTier) plutôt que par une règle "global" plate en base — voir
 * PricingService::resolveMarginPercentForScope() et PricingSeeder. Cette
 * migration retire la règle "global" que PricingSeeder créait
 * automatiquement (created_by = null, donc jamais créée à la main par un
 * administrateur) pour que les paliers s'appliquent réellement sur les
 * installations existantes. Une règle "global" créée explicitement par un
 * administrateur via l'API (created_by renseigné) est volontairement
 * préservée : elle reste un levier valide de campagne ponctuelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('margin_rules')
            ->where('scope_type', MarginScope::GLOBAL)
            ->whereNull('created_by')
            ->delete();
    }

    public function down(): void
    {
        // Non réversible : on ne peut pas restaurer une valeur de marge
        // plate arbitraire une fois supprimée.
    }
};
