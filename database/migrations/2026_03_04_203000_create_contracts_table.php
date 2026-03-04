<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('debt_amount', 12, 2)->default(0);
            $table->decimal('fee_amount', 12, 2);
            $table->decimal('entry_percentage', 5, 2)->default(50);
            $table->decimal('entry_amount', 12, 2);
            $table->unsignedTinyInteger('installments_count')->default(3);
            $table->string('status', 30)->default('aguardando_entrada');
            $table->string('payment_provider', 20)->default('asaas');
            $table->string('asaas_customer_id')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('order_id', 'uq_contracts_order_id');
            $table->index(['analyst_id', 'status'], 'idx_contracts_analyst_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
