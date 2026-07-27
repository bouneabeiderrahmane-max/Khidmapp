<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('zone')->default('nouakchott');
            $table->decimal('min_price_mru', 10, 2)->default(0);
            $table->decimal('max_price_mru', 10, 2)->nullable();
            $table->decimal('fee_mru', 10, 2);
            $table->timestamps();

            $table->index(['zone', 'min_price_mru']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_fee_tiers');
    }
};
