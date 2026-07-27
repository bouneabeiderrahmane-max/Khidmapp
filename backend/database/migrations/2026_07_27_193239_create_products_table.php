<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained()->cascadeOnDelete();
            $table->string('external_ref');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->jsonb('images')->nullable();
            $table->decimal('base_price_eur', 10, 2);
            $table->string('status')->default('active');
            $table->string('source_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('unavailable_since')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['boutique_id', 'external_ref']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
