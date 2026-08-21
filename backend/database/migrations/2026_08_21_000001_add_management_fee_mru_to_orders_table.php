<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Coût de gestion" (5 % par défaut, config('pricing.management_fee_percent'))
 * : contrairement à la marge, ce montant est explicitement montré au client
 * sur son panier/commande, en plus du sous-total et de la livraison — voir
 * CartPricingCalculator/CustomOrderPricingCalculator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('management_fee_mru', 10, 2)->default(0)->after('delivery_fee_snapshot_mru');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('management_fee_mru');
        });
    }
};
