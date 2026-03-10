<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->timestamp('sent_for_acceptance_at')->nullable()->after('acceptance_token');
            $table->timestamp('acceptance_expires_at')->nullable()->after('sent_for_acceptance_at');
            $table->string('accepted_hash', 64)->nullable()->after('accepted_user_agent');
            $table->timestamp('entry_paid_at')->nullable()->after('accepted_hash');
            $table->timestamp('activated_at')->nullable()->after('entry_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropColumn([
                'sent_for_acceptance_at',
                'acceptance_expires_at',
                'accepted_hash',
                'entry_paid_at',
                'activated_at',
            ]);
        });
    }
};
