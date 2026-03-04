<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('base_amount', 10, 2);
            $table->decimal('rate', 6, 4);
            $table->decimal('commission_amount', 10, 2);
            $table->enum('status', ['pending', 'available', 'paid', 'canceled'])->default('pending');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('asaas_transfer_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'available_at'], 'idx_seller_commission_status_available');
            $table->index(['seller_id', 'status'], 'idx_seller_commission_seller_status');
            $table->unique(['order_id', 'source_type', 'source_id'], 'uq_seller_commission_order_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_commissions');
    }
};
