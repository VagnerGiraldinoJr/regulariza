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
        Schema::create('sac_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo')->unique();
            $table->foreignId('order_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('atendente_id')->nullable()->constrained('users');
            $table->string('assunto');
            $table->enum('status', ['aberto', 'em_atendimento', 'aguardando_cliente', 'resolvido', 'fechado'])->default('aberto');
            $table->enum('prioridade', ['nova', 'baixa', 'media', 'alta', 'critica'])->default('nova');
            $table->timestamp('resolvido_em')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sac_tickets');
    }
};
