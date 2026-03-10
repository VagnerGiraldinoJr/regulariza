<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->string('acceptance_token', 64)->nullable()->unique()->after('document_path');
            $table->string('accepted_name')->nullable()->after('accepted_at');
            $table->string('accepted_ip', 45)->nullable()->after('accepted_name');
            $table->text('accepted_user_agent')->nullable()->after('accepted_ip');
        });

        DB::table('contracts')
            ->select(['id', 'acceptance_token'])
            ->orderBy('id')
            ->get()
            ->each(function (object $contract): void {
                if (filled($contract->acceptance_token)) {
                    return;
                }

                DB::table('contracts')
                    ->where('id', $contract->id)
                    ->update(['acceptance_token' => Str::random(64)]);
            });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropUnique(['acceptance_token']);
            $table->dropColumn([
                'acceptance_token',
                'accepted_name',
                'accepted_ip',
                'accepted_user_agent',
            ]);
        });
    }
};
