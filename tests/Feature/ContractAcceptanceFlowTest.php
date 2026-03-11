<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContractAcceptanceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_acceptance_registers_evidence_and_releases_final_pdf(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11999999999',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-ACEITE-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $contract = Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'debt_amount' => 12000,
            'fee_amount' => 3000,
            'entry_percentage' => 50,
            'entry_amount' => 1500,
            'installments_count' => 3,
            'status' => 'aguardando_aceite',
            'payment_provider' => 'asaas',
            'acceptance_token' => 'token-publico-aceite-1234567890',
        ]);

        ContractInstallment::query()->create([
            'contract_id' => $contract->id,
            'order_id' => $order->id,
            'installment_number' => 0,
            'label' => 'Entrada (50%)',
            'amount' => 1500,
            'due_date' => now()->toDateString(),
            'billing_type' => 'PIX',
            'payment_provider' => 'asaas',
            'status' => 'aguardando_pagamento',
        ]);

        $showResponse = $this->get(route('contracts.accept.show', $contract->acceptance_token));
        $showResponse->assertOk();
        $showResponse->assertSee('Confissão de Dívida');
        $showResponse->assertSee('Cláusula 1', false);

        $response = $this->post(route('contracts.accept.store', $contract->acceptance_token), [
            'accepted_name' => 'Cliente de Teste',
            'accept_terms' => '1',
        ]);

        $response->assertRedirect(route('contracts.accept.show', $contract->acceptance_token));

        $contract->refresh();

        $this->assertNotNull($contract->accepted_at);
        $this->assertSame('Cliente de Teste', $contract->accepted_name);
        $this->assertNotNull($contract->accepted_hash);
        $this->assertSame('aguardando_entrada', $contract->status);

        $pdfResponse = $this->get(route('contracts.accept.pdf', $contract->acceptance_token));
        $pdfResponse->assertOk();
    }

    public function test_contract_charges_are_only_issued_after_acceptance(): void
    {
        config()->set('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        config()->set('services.asaas.api_key', 'asaas_test_token');

        $paymentSequence = 0;

        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$paymentSequence) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/customers')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/customers')) {
                return Http::response(['id' => 'cus_123'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/payments')) {
                $paymentSequence++;

                return Http::response([
                    'id' => 'pay_'.$paymentSequence,
                    'invoiceUrl' => 'https://asaas.local/pay/'.$paymentSequence,
                ], 200);
            }

            return Http::response([], 404);
        });

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'email' => 'cliente@example.com',
            'whatsapp' => '11999999999',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-ACEITE-002',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $contract = app(\App\Services\ContractService::class)->createForOrder(
            order: $order,
            debtAmount: 12000,
            feeAmount: 3000,
            entryPercentage: 50
        );

        Http::assertNothingSent();

        $this->assertSame('aguardando_aceite', $contract->status);
        $this->assertCount(4, $contract->installments);
        $this->assertTrue($contract->installments->every(fn (ContractInstallment $item) => $item->status === 'aguardando_aceite'));
        $this->assertTrue($contract->installments->every(fn (ContractInstallment $item) => blank($item->payment_link_url)));

        $response = $this->post(route('contracts.accept.store', $contract->acceptance_token), [
            'accepted_name' => 'Cliente de Teste',
            'accept_terms' => '1',
        ]);

        $response->assertRedirect(route('contracts.accept.show', $contract->acceptance_token));

        $contract->refresh();
        $installments = $contract->installments()->orderBy('installment_number')->get();

        $this->assertSame('aguardando_entrada', $contract->status);
        $this->assertTrue($installments->every(fn (ContractInstallment $item) => $item->status === 'aguardando_pagamento'));
        $this->assertTrue($installments->every(fn (ContractInstallment $item) => filled($item->asaas_payment_id)));
        $this->assertTrue($installments->every(fn (ContractInstallment $item) => filled($item->payment_link_url)));

        Http::assertSentCount(7);
    }

    public function test_expired_acceptance_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-expirado',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-ACEITE-003',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $contract = Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'debt_amount' => 12000,
            'fee_amount' => 3000,
            'entry_percentage' => 50,
            'entry_amount' => 1500,
            'installments_count' => 3,
            'status' => 'aguardando_aceite',
            'payment_provider' => 'asaas',
            'acceptance_token' => 'token-expirado-aceite-123',
            'acceptance_expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('contracts.accept.show', $contract->acceptance_token));
        $response->assertStatus(410);

        $postResponse = $this->post(route('contracts.accept.store', $contract->acceptance_token), [
            'accepted_name' => 'Cliente Expirado',
            'accept_terms' => '1',
        ]);

        $postResponse->assertSessionHasErrors('contract');
    }
}
