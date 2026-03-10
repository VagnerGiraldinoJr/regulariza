<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('analyst_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type', 20);
            $table->string('title', 180);
            $table->string('document_type', 10);
            $table->string('document_number', 18);
            $table->string('status', 30)->default('processing');
            $table->unsignedTinyInteger('source_count')->default(0);
            $table->unsignedTinyInteger('success_count')->default(0);
            $table->unsignedTinyInteger('failure_count')->default(0);
            $table->json('normalized_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['report_type', 'created_at'], 'idx_research_reports_type_created');
            $table->index(['document_number', 'created_at'], 'idx_research_reports_document_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_reports');
    }
};
