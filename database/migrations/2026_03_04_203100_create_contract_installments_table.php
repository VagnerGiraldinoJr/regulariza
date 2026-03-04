<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->string('label', 40);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('billing_type', 20)->default('BOLETO');
            $table->string('payment_provider', 20)->default('asaas');
            $table->string('asaas_payment_id')->nullable();
            $table->text('payment_link_url')->nullable();
            $table->string('status', 30)->default('aguardando_pagamento');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'installment_number'], 'uq_contract_installment_number');
            $table->index(['status', 'due_date'], 'idx_contract_installment_status_due');
            $table->index('asaas_payment_id', 'idx_contract_installment_asaas_payment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_installments');
    }
};
