<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('external_variant_ref');
            $table->string('sku')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->decimal('price_eur', 10, 2);
            $table->string('stock_status')->default('in_stock');
            $table->timestamps();

            $table->unique(['product_id', 'external_variant_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
