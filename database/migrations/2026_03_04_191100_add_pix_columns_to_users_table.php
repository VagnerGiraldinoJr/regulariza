<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pix_key', 120)->nullable()->after('whatsapp');
            $table->string('pix_key_type', 20)->nullable()->after('pix_key');
            $table->string('pix_holder_name', 120)->nullable()->after('pix_key_type');
            $table->string('pix_holder_document', 20)->nullable()->after('pix_holder_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'pix_key',
                'pix_key_type',
                'pix_holder_name',
                'pix_holder_document',
            ]);
        });
    }
};
