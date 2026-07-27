<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            // Renseigné pour une commande manuelle (8.4.2) créée par le
            // service client / un administrateur pour le compte du client.
            $table->foreignId('created_by_agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_label')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_area')->nullable();
            $table->string('shipping_phone')->nullable();

            $table->string('payment_method')->nullable();

            $table->decimal('subtotal_eur', 10, 2);
            $table->decimal('exchange_rate_snapshot', 12, 6);
            $table->decimal('subtotal_mru', 10, 2);
            $table->decimal('delivery_fee_snapshot_mru', 10, 2);
            $table->decimal('total_mru', 10, 2);

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('cancellation_fee_applicable')->default(false);
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
