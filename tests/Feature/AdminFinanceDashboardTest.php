<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_dashboard_separates_generated_and_paid_amounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Financeira',
            'slug' => 'pesquisa-financeira-dashboard',
            'preco' => 200,
            'ativo' => true,
        ]);

        $paidOrder = Order::query()->create([
            'protocolo' => 'PED-FIN-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 200,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        Order::query()->create([
            'protocolo' => 'PED-FIN-002',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 300,
            'pagamento_status' => 'aguardando',
        ]);

        $contract = Contract::query()->create([
            'order_id' => $paidOrder->id,
            'user_id' => $client->id,
            'debt_amount' => 5000,
            'fee_amount' => 1000,
            'entry_percentage' => 50,
            'entry_amount' => 500,
            'installments_count' => 2,
            'status' => 'ativo',
            'payment_provider' => 'asaas',
            'accepted_at' => now(),
        ]);

        ContractInstallment::query()->create([
            'contract_id' => $contract->id,
            'order_id' => $paidOrder->id,
            'installment_number' => 0,
            'label' => 'Entrada',
            'amount' => 500,
            'due_date' => now()->toDateString(),
            'billing_type' => 'PIX',
            'payment_provider' => 'asaas',
            'status' => 'pago',
            'paid_at' => now(),
        ]);

        ContractInstallment::query()->create([
            'contract_id' => $contract->id,
            'order_id' => $paidOrder->id,
            'installment_number' => 1,
            'label' => 'Parcela 1/2',
            'amount' => 250,
            'due_date' => now()->addMonth()->toDateString(),
            'billing_type' => 'BOLETO',
            'payment_provider' => 'asaas',
            'status' => 'aguardando_pagamento',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Financeiro');
        $response->assertSee('finance-dashboard-root');
        $response->assertSee('Recebido consolidado');
        $response->assertSee('Parcelas de contratos');
        $response->assertSee('Taxa de recebimento');
        $response->assertSee('R$ 500,00', false);
        $response->assertSee('R$ 200,00', false);
        $response->assertSee('R$ 300,00', false);
        $response->assertSee('R$ 750,00', false);
        $response->assertSee('R$ 500,00', false);
        $response->assertSee('R$ 250,00', false);
    }
}
