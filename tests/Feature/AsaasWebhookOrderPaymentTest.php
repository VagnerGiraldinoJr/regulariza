<?php

namespace Tests\Feature;

use App\Jobs\CriarUsuarioPortal;
use App\Jobs\EnviarBoasVindasWhatsApp;
use App\Jobs\NotificarEquipeInterna;
use App\Models\Order;
use App\Models\SellerCommission;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AsaasWebhookOrderPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_payment_webhook_marks_order_paid_and_registers_commission(): void
    {
        config()->set('services.asaas.webhook_token', 'webhook-order-token');

        Queue::fake([
            CriarUsuarioPortal::class,
            EnviarAcessoPortalWhatsApp::class,
            EnviarBoasVindasWhatsApp::class,
            NotificarEquipeInterna::class,
        ]);

        $seller = User::factory()->create([
            'role' => 'vendedor',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
            'referred_by_user_id' => $seller->id,
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-webhook',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-WEBHOOK-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 299.90,
            'pagamento_status' => 'aguardando',
        ]);

        $response = $this->postJson(route('api.asaas.webhook'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_order_001',
                'invoiceUrl' => 'https://asaas.test/order/1',
                'externalReference' => 'order:'.$order->id,
            ],
        ], [
            'Asaas-Access-Token' => 'webhook-order-token',
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('pago', $order->pagamento_status);
        $this->assertSame('em_andamento', $order->status);
        $this->assertSame('pay_order_001', $order->asaas_payment_id);
        $this->assertNotNull($client->fresh()->portal_token);

        $commission = SellerCommission::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($commission);
        $this->assertSame('research', $commission->source_type);
        $this->assertSame($seller->id, $commission->seller_id);

        Queue::assertPushed(CriarUsuarioPortal::class, 0);
        Queue::assertPushed(EnviarBoasVindasWhatsApp::class);
        Queue::assertPushed(NotificarEquipeInterna::class);
    }

    public function test_webhook_reconciles_paid_order_even_when_status_was_already_paid(): void
    {
        config()->set('services.asaas.webhook_token', 'webhook-order-token');

        Queue::fake([
            CriarUsuarioPortal::class,
            EnviarBoasVindasWhatsApp::class,
            NotificarEquipeInterna::class,
        ]);

        $seller = User::factory()->create([
            'role' => 'vendedor',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
            'referred_by_user_id' => $seller->id,
            'portal_token' => null,
            'portal_token_expires_at' => null,
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-webhook-reconcile',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-WEBHOOK-002',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now()->subMinute(),
        ]);

        $response = $this->postJson(route('api.asaas.webhook'), [
            'event' => 'PAYMENT_RECEIVED',
            'payment' => [
                'id' => 'pay_order_002',
                'invoiceUrl' => 'https://asaas.test/order/2',
                'externalReference' => 'order:'.$order->id,
            ],
        ], [
            'Asaas-Access-Token' => 'webhook-order-token',
        ]);

        $response->assertOk();

        $order->refresh();

        $this->assertSame('pago', $order->pagamento_status);
        $this->assertSame('em_andamento', $order->status);
        $this->assertSame('pay_order_002', $order->asaas_payment_id);
        $this->assertNotNull($client->fresh()->portal_token);

        $commission = SellerCommission::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($commission);
        $this->assertSame('research', $commission->source_type);
        $this->assertSame($seller->id, $commission->seller_id);

        Queue::assertNothingPushed();
    }
}
