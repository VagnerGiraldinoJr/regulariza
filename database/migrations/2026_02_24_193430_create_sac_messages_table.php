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
        Schema::create('sac_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sac_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('mensagem');
            $table->enum('tipo', ['texto', 'arquivo', 'sistema'])->default('texto');
            $table->string('arquivo_url')->nullable();
            $table->boolean('lida')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sac_messages');
    }
};
