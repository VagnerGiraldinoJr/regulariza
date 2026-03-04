<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('payment_provider', 20)->default('asaas')->after('valor');
            $table->string('asaas_customer_id')->nullable()->after('payment_provider');
            $table->string('asaas_payment_id')->nullable()->after('asaas_customer_id');
            $table->text('payment_link_url')->nullable()->after('asaas_payment_id');
            $table->index('asaas_payment_id', 'idx_orders_asaas_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('idx_orders_asaas_payment_id');
            $table->dropColumn([
                'payment_provider',
                'asaas_customer_id',
                'asaas_payment_id',
                'payment_link_url',
            ]);
        });
    }
};
