<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Poids de colis choisi par le client à la soumission de la demande (comme
 * un checkout classique, voir la migration create_custom_order_requests_table),
 * reporté sur la Commande réelle à l'approbation (CustomOrderRequestService::approve()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_order_requests', function (Blueprint $table) {
            $table->string('weight_tier')->nullable()->after('payment_method');
            $table->decimal('extra_weight_kg', 6, 2)->nullable()->after('weight_tier');
        });
    }

    public function down(): void
    {
        Schema::table('custom_order_requests', function (Blueprint $table) {
            $table->dropColumn(['weight_tier', 'extra_weight_kg']);
        });
    }
};
