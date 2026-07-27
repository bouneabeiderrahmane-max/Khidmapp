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
        Schema::table('users', function (Blueprint $table) {
            // Blocage temporaire ou définitif d'un compte client en cas
            // d'abus (CDC 8.9.2). blocked_until = null => blocage définitif.
            $table->timestamp('blocked_at')->nullable()->after('locale');
            $table->timestamp('blocked_until')->nullable()->after('blocked_at');
            $table->text('blocked_reason')->nullable()->after('blocked_until');
            $table->foreignId('blocked_by')->nullable()->after('blocked_reason')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocked_by');
            $table->dropColumn(['blocked_at', 'blocked_until', 'blocked_reason']);
        });
    }
};
