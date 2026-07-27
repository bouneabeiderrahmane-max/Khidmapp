<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('boutique_id')->nullable()->constrained()->nullOnDelete();

            $table->jsonb('product_name_snapshot');
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('quantity');

            $table->decimal('unit_price_eur', 10, 2);
            $table->decimal('unit_price_mru_snapshot', 10, 2);
            $table->decimal('margin_percent_snapshot', 6, 2);
            $table->string('margin_source_snapshot');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
