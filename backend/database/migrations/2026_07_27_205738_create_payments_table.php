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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            // Conçu extensible (CDC 8.5.3) : "bankily"/"manual" aujourd'hui,
            // d'autres moyens de paiement locaux/internationaux demain sans
            // changer le schéma.
            $table->string('method');
            $table->string('status')->default('pending');
            $table->decimal('amount_mru', 10, 2);

            // Paiement automatique (Bankily, 8.5.1)
            $table->string('external_reference')->nullable()->unique();
            $table->jsonb('raw_payload')->nullable();
            $table->string('failure_reason')->nullable();

            // Paiement manuel (8.5.2)
            $table->string('proof_file_path')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('info_requested_note')->nullable();
            $table->timestamp('info_requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('initiated_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
