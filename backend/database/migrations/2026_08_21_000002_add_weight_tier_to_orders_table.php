<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poids de colis choisi par le client au paiement (PackageWeightTier),
 * historisé comme les autres valeurs de tarification (snapshot). Nullable :
 * les commandes antérieures à cette migration n'en ont pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('weight_tier')->nullable()->after('delivery_zone');
            $table->decimal('extra_weight_kg', 6, 2)->nullable()->after('weight_tier');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['weight_tier', 'extra_weight_kg']);
        });
    }
};
