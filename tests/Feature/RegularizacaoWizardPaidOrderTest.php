<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class RegularizacaoWizardPaidOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_opens_wizard_on_success_step_even_on_regularizacao_route(): void
    {
        config()->set('services.cpfclean.site_url', 'https://cpfclean.com.br');

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-paid-order',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PAID-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->get(route('regularizacao.index', ['order_id' => $order->id]));

        $response->assertOk();
        $response->assertSee('Pagamento confirmado');
        $response->assertSee('PED-PAID-001');
        $response->assertSee('Ir para o site');
        $response->assertSee('https://cpfclean.com.br', false);
    }

    public function test_success_step_informs_when_payment_was_confirmed_from_existing_charge(): void
    {
        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-existing-paid-charge',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PAID-EXISTING-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->get(route('regularizacao.sucesso', [
            'order_id' => $order->id,
            'existing_charge' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Identificamos uma cobrança já existente para este pedido.');
        $response->assertSee('PED-PAID-EXISTING-001');
    }

    public function test_pending_pix_order_enables_automatic_status_polling(): void
    {
        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-pix-polling',
            'preco' => 10.00,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PIX-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'pendente',
            'valor' => 10.00,
            'pagamento_status' => 'aguardando',
            'asaas_payment_id' => 'pay_pix_test_001',
        ]);

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('getCheckoutSessionForOrder')
            ->once()
            ->andReturn([
                'order_id' => $order->id,
                'payment_id' => 'pay_pix_test_001',
                'billing_type' => 'PIX',
                'payment_url' => 'https://asaas.local/f/pay_pix_test_001',
                'invoice_url' => 'https://asaas.local/f/pay_pix_test_001',
                'bank_slip_url' => null,
                'pix' => [
                    'encoded_image' => 'base64pix',
                    'payload' => '000201pixpayload',
                    'expiration_date' => now()->addDay()->toIso8601String(),
                ],
                'status' => 'PENDING',
                'value' => 10.00,
                'description' => 'Regularização PF',
                'due_date' => now()->toDateString(),
            ]);

        $this->app->instance(CheckoutService::class, $checkoutService);

        $response = $this->get(route('regularizacao.index', ['order_id' => $order->id]));

        $response->assertOk();
        $response->assertSee('wire:poll.5s="sincronizarPagamentoPix"', false);
        $response->assertSee('Aguardando pagamento Pix');
        $response->assertSee('Monitoramento automatico ativo');
        $response->assertDontSee('Atualizar cobrança no Asaas');
    }

    public function test_existing_pending_charge_is_reused_instead_of_creating_a_new_one(): void
    {
        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-existing-session',
            'preco' => 10.00,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PIX-LOCK-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'pendente',
            'valor' => 10.00,
            'pagamento_status' => 'aguardando',
            'asaas_payment_id' => 'pay_pix_lock_001',
        ]);

        $session = [
            'order_id' => $order->id,
            'payment_id' => 'pay_pix_lock_001',
            'billing_type' => 'PIX',
            'payment_url' => 'https://asaas.local/f/pay_pix_lock_001',
            'invoice_url' => 'https://asaas.local/f/pay_pix_lock_001',
            'bank_slip_url' => null,
            'pix' => [
                'encoded_image' => 'base64pixlock',
                'payload' => '000201pixlockpayload',
                'expiration_date' => now()->addDay()->toIso8601String(),
            ],
            'status' => 'PENDING',
            'value' => 10.00,
            'description' => 'Regularização PF',
            'due_date' => now()->toDateString(),
        ];

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('getCheckoutSessionForOrder')
            ->twice()
            ->andReturn($session);
        $checkoutService->shouldReceive('createCheckoutSessionForOrder')->never();

        $this->app->instance(CheckoutService::class, $checkoutService);

        Livewire::withQueryParams(['order_id' => $order->id])
            ->test('regularizacao-wizard')
            ->call('iniciarPagamento')
            ->assertSet('payment_session.order_id', $order->id)
            ->assertSee('Ja existe uma cobranca ativa para este pedido.');
    }

    public function test_existing_pending_pix_charge_redirects_to_success_after_payment_confirmation(): void
    {
        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-existing-session-paid',
            'preco' => 10.00,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PIX-PAID-EXISTING-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'pendente',
            'valor' => 10.00,
            'pagamento_status' => 'aguardando',
            'asaas_payment_id' => 'pay_pix_existing_paid_001',
        ]);

        $session = [
            'order_id' => $order->id,
            'payment_id' => 'pay_pix_existing_paid_001',
            'billing_type' => 'PIX',
            'payment_url' => 'https://asaas.local/f/pay_pix_existing_paid_001',
            'invoice_url' => 'https://asaas.local/f/pay_pix_existing_paid_001',
            'bank_slip_url' => null,
            'pix' => [
                'encoded_image' => 'base64pixexistingpaid',
                'payload' => '000201pixexistingpaidpayload',
                'expiration_date' => now()->addDay()->toIso8601String(),
            ],
            'status' => 'PENDING',
            'value' => 10.00,
            'description' => 'Regularização PF',
            'due_date' => now()->toDateString(),
        ];

        $checkoutService = Mockery::mock(CheckoutService::class);
        $checkoutService->shouldReceive('getCheckoutSessionForOrder')
            ->once()
            ->andReturn($session);
        $checkoutService->shouldReceive('getCheckoutSessionForOrder')
            ->once()
            ->andReturnUsing(function () use ($order, $session) {
                $order->forceFill([
                    'pagamento_status' => 'pago',
                    'status' => 'em_andamento',
                    'pago_em' => now(),
                ])->save();

                return $session;
            });

        $this->app->instance(CheckoutService::class, $checkoutService);

        Livewire::withQueryParams(['order_id' => $order->id])
            ->test('regularizacao-wizard')
            ->call('sincronizarPagamentoPix')
            ->assertSet('etapa', 4)
            ->assertRedirect(route('regularizacao.sucesso', [
                'order_id' => $order->id,
                'existing_charge' => 1,
            ]));
    }
}
