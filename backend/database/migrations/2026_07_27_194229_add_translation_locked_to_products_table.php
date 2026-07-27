<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un produit dont la traduction a été corrigée manuellement par un
     * administrateur (8.2.1) ne doit plus être écrasé par la traduction
     * automatique lors des synchronisations suivantes.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('translation_locked')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('translation_locked');
        });
    }
};
