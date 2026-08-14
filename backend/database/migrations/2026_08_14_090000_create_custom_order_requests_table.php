<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Pedido personalizado" (demande hors catalogue, produit trouvé par le
     * client sur le site de la boutique) : entité distincte des commandes,
     * en attente de revue manuelle (faisabilité + prix estimé par le
     * client) avant toute conversion en Commande réelle. Voir
     * CustomOrderRequestService::approve() — c'est lui qui crée la ligne
     * `orders` correspondante, jamais ce formulaire directement.
     */
    public function up(): void
    {
        Schema::create('custom_order_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('en_attente');

            // Renseignés par le client à la soumission (comme un checkout
            // classique) pour éviter une étape supplémentaire après
            // validation par l'administration.
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method')->nullable();

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_order_requests');
    }
};
