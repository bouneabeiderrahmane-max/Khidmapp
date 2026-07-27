<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Zone tarifaire (grille de frais de livraison, 8.9.4) appliquée
            // à cette commande — utilisée pour la ventilation des ventes par
            // zone du tableau de bord (8.9.7).
            $table->string('delivery_zone')->nullable()->after('delivery_fee_snapshot_mru');
            $table->index('delivery_zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivery_zone']);
            $table->dropColumn('delivery_zone');
        });
    }
};
