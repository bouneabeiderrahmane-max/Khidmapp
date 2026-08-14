<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lien traçable vers la ligne de demande d'origine (URL produit,
     * notes taille/couleur) quand un OrderItem provient d'une demande de
     * "pedido personalizado" approuvée — nullable et sans effet sur le
     * flux panier normal.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('custom_order_item_id')->nullable()->after('boutique_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_order_item_id');
        });
    }
};
