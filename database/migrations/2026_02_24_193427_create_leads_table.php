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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('cpf_cnpj', 18);
            $table->enum('tipo_documento', ['cpf', 'cnpj']);
            $table->string('nome')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->foreignId('service_id')->nullable()->constrained();
            $table->enum('etapa', ['identificacao', 'servico', 'pagamento', 'concluido'])->default('identificacao');
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
