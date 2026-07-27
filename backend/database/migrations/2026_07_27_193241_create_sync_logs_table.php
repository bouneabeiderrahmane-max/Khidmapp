<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('running');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('products_created')->default(0);
            $table->unsignedInteger('products_updated')->default(0);
            $table->unsignedInteger('products_disabled')->default(0);
            $table->jsonb('errors')->nullable();
            $table->timestamps();

            $table->index(['boutique_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
