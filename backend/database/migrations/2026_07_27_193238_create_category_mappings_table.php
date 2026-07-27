<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained()->cascadeOnDelete();
            $table->string('source_category_ref');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['boutique_id', 'source_category_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_mappings');
    }
};
