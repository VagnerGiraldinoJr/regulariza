<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_report_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('research_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apibrasil_consultation_id')->nullable()->constrained('apibrasil_consultations')->nullOnDelete();
            $table->string('provider', 50)->default('apibrasil');
            $table->string('source_key', 80);
            $table->string('source_title', 180)->nullable();
            $table->string('source_category', 80)->nullable();
            $table->string('status', 30)->default('error');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['research_report_id', 'status'], 'idx_research_report_items_report_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_report_items');
    }
};
