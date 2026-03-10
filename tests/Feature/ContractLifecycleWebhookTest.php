<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContractLifecycleWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_marks_entry_payment_activation_and_completion_separately(): void
    {
        config()->set('services.asaas.webhook_token', 'webhook-test-token');
        Queue::fake();

        $client = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-ciclo',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-CONTRATO-CICLO-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $contract = Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $client->id,
            'debt_amount' => 12000,
            'fee_amount' => 3000,
            'entry_percentage' => 50,
            'entry_amount' => 1500,
            'installments_count' => 3,
            'status' => 'aguardando_entrada',
            'payment_provider' => 'asaas',
            'acceptance_token' => 'token-ciclo-contrato-001',
            'accepted_at' => now(),
            'accepted_name' => 'Cliente PF',
        ]);

        $entry = ContractInstallment::query()->create([
            'contract_id' => $contract->id,
            'order_id' => $order->id,
            'installment_number' => 0,
            'label' => 'Entrada (50%)',
            'amount' => 1500,
            'due_date' => now()->toDateString(),
            'billing_type' => 'PIX',
            'payment_provider' => 'asaas',
            'status' => 'aguardando_pagamento',
            'asaas_payment_id' => 'pay_entry',
        ]);

        $installments = collect(range(1, 3))->map(function (int $number) use ($contract, $order) {
            return ContractInstallment::query()->create([
                'contract_id' => $contract->id,
                'order_id' => $order->id,
                'installment_number' => $number,
                'label' => "Parcela {$number}/3",
                'amount' => 500,
                'due_date' => now()->addDays($number * 30)->toDateString(),
                'billing_type' => 'BOLETO',
                'payment_provider' => 'asaas',
                'status' => 'aguardando_pagamento',
                'asaas_payment_id' => "pay_{$number}",
            ]);
        });

        $this->postJson(route('api.asaas.webhook'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => $entry->asaas_payment_id,
                'invoiceUrl' => 'https://asaas.test/entry',
            ],
        ], [
            'Asaas-Access-Token' => 'webhook-test-token',
        ])->assertOk();

        $contract->refresh();
        $this->assertSame('ativo', $contract->status);
        $this->assertNotNull($contract->entry_paid_at);
        $this->assertNotNull($contract->activated_at);
        $this->assertNull($contract->completed_at);

        foreach ($installments as $installment) {
            $this->postJson(route('api.asaas.webhook'), [
                'event' => 'PAYMENT_RECEIVED',
                'payment' => [
                    'id' => $installment->asaas_payment_id,
                    'invoiceUrl' => "https://asaas.test/{$installment->id}",
                ],
            ], [
                'Asaas-Access-Token' => 'webhook-test-token',
            ])->assertOk();
        }

        $contract->refresh();
        $this->assertSame('concluido', $contract->status);
        $this->assertNotNull($contract->entry_paid_at);
        $this->assertNotNull($contract->activated_at);
        $this->assertNotNull($contract->completed_at);
    }
}
