<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['cpf_cnpj', 'tipo_documento'], 'idx_leads_documento');
            $table->index('session_id', 'idx_leads_session_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'pagamento_status'], 'idx_orders_status_pagamento');
            $table->index('pago_em', 'idx_orders_pago_em');
        });

        Schema::table('sac_tickets', function (Blueprint $table): void {
            $table->index(['status', 'atendente_id'], 'idx_sac_tickets_status_atendente');
            $table->index('prioridade', 'idx_sac_tickets_prioridade');
        });

        Schema::table('sac_messages', function (Blueprint $table): void {
            $table->index(['sac_ticket_id', 'created_at'], 'idx_sac_messages_ticket_created');
        });
    }

    public function down(): void
    {
        Schema::table('sac_messages', function (Blueprint $table): void {
            $table->dropIndex('idx_sac_messages_ticket_created');
        });

        Schema::table('sac_tickets', function (Blueprint $table): void {
            $table->dropIndex('idx_sac_tickets_prioridade');
            $table->dropIndex('idx_sac_tickets_status_atendente');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('idx_orders_pago_em');
            $table->dropIndex('idx_orders_status_pagamento');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('idx_leads_session_id');
            $table->dropIndex('idx_leads_documento');
        });
    }
};
