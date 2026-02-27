<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 16)->nullable()->unique()->after('whatsapp');
            $table->foreignId('referred_by_user_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->decimal('referral_credits', 10, 2)->default(0)->after('referred_by_user_id');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('referred_by_user_id')->nullable()->after('session_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('referral_credit_amount', 10, 2)->default(0)->after('valor');
            $table->timestamp('referral_credited_at')->nullable()->after('pago_em');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['referral_credit_amount', 'referral_credited_at']);
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by_user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('referred_by_user_id');
            $table->dropUnique('users_referral_code_unique');
            $table->dropColumn(['referral_code', 'referral_credits']);
        });
    }
};

