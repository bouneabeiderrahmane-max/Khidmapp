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
        Schema::table('order_items', function (Blueprint $table) {
            // Marge réalisée par unité (MRU), en complément de
            // margin_percent_snapshot — évite de la recalculer à partir du
            // pourcentage pour le reporting (8.9.7 : "marge réalisée").
            $table->decimal('margin_amount_mru_snapshot', 10, 2)->nullable()->after('margin_percent_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('margin_amount_mru_snapshot');
        });
    }
};
