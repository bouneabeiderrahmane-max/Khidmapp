<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('margin_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type');
            // Pointe vers boutiques.id ou categories.id selon scope_type ;
            // null pour scope_type=global. Pas de contrainte FK unique
            // possible puisque la table cible varie.
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->decimal('percent', 6, 2);
            $table->timestamp('effective_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope_type', 'scope_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margin_rules');
    }
};
