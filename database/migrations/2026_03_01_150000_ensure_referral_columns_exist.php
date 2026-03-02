<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) use ($isSqlite): void {
                if (! Schema::hasColumn('users', 'referral_code')) {
                    $table->string('referral_code', 16)->nullable()->after('whatsapp');
                }

                if (! Schema::hasColumn('users', 'referred_by_user_id')) {
                    $column = $table->unsignedBigInteger('referred_by_user_id')->nullable()->after('referral_code');

                    if (! $isSqlite) {
                        $column->index();
                    }
                }

                if (! Schema::hasColumn('users', 'referral_credits')) {
                    $table->decimal('referral_credits', 10, 2)->default(0)->after('referred_by_user_id');
                }
            });

        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) use ($isSqlite): void {
                if (! Schema::hasColumn('leads', 'referred_by_user_id')) {
                    $column = $table->unsignedBigInteger('referred_by_user_id')->nullable()->after('session_id');

                    if (! $isSqlite) {
                        $column->index();
                    }
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'referral_credit_amount')) {
                    $table->decimal('referral_credit_amount', 10, 2)->default(0)->after('valor');
                }

                if (! Schema::hasColumn('orders', 'referral_credited_at')) {
                    $table->timestamp('referral_credited_at')->nullable()->after('pago_em');
                }
            });
        }
    }

    public function down(): void
    {
        // Defensive migration only; no destructive rollback.
    }
};
