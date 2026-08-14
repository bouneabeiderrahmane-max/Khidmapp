<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_request_id')->constrained()->cascadeOnDelete();

            // Optionnelle : la demande peut partir d'une boutique parcourue
            // (résolution de marge boutique > global) ou d'une URL externe
            // libre (repli sur la marge globale).
            $table->foreignId('boutique_id')->nullable()->constrained()->nullOnDelete();

            $table->text('product_url');
            $table->unsignedInteger('quantity');

            // Prix indiqué par le client (page produit de la boutique) —
            // engageant (clarifié avec l'utilisateur) : utilisé tel quel
            // par le moteur de marge pour calculer le prix final, sans
            // re-saisie par l'administration.
            $table->decimal('estimated_price_eur', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_items');
    }
};
