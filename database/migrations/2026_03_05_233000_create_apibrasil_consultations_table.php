<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apibrasil_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('analyst_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 10);
            $table->string('document_number', 18);
            $table->string('status', 30)->default('error');
            $table->string('provider', 50)->default('apibrasil');
            $table->string('endpoint', 255)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['document_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apibrasil_consultations');
    }
};
