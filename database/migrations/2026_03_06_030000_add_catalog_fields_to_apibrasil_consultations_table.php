<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apibrasil_consultations', function (Blueprint $table) {
            $table->string('consultation_key', 80)->nullable()->after('analyst_user_id');
            $table->string('consultation_title', 180)->nullable()->after('consultation_key');
            $table->string('consultation_category', 80)->nullable()->after('consultation_title');
        });
    }

    public function down(): void
    {
        Schema::table('apibrasil_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_key',
                'consultation_title',
                'consultation_category',
            ]);
        });
    }
};
