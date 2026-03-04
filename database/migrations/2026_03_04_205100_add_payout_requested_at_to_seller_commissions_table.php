<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_commissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('seller_commissions', 'payout_requested_at')) {
                $table->timestamp('payout_requested_at')->nullable()->after('available_at');
                $table->index('payout_requested_at', 'idx_seller_commission_payout_requested');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seller_commissions', function (Blueprint $table): void {
            if (Schema::hasColumn('seller_commissions', 'payout_requested_at')) {
                $table->dropIndex('idx_seller_commission_payout_requested');
                $table->dropColumn('payout_requested_at');
            }
        });
    }
};
