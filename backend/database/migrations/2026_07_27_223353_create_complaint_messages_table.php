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
        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type');
            // Nullable : les réclamations ouvertes automatiquement (8.6.1,
            // anomalie de contrôle qualité) portent un message système sans
            // utilisateur associé (sender_type = "system").
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message')->nullable();
            $table->jsonb('attachments')->nullable();
            $table->timestamps();

            $table->index(['complaint_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_messages');
    }
};
