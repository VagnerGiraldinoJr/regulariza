<?php

namespace Tests\Feature;

use App\Jobs\EnviarAcessoPortalWhatsApp;
use App\Jobs\EnviarLinkAceiteContratoWhatsApp;
use App\Models\Contract;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContractCreationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_contract_and_dispatches_client_notifications_immediately(): void
    {
        Queue::fake([
            EnviarAcessoPortalWhatsApp::class,
            EnviarLinkAceiteContratoWhatsApp::class,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente.contrato@example.com',
            'whatsapp' => '11999999999',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF CLEAN BRASIL',
            'slug' => 'pesquisa-cpf-clean-brasil-contrato',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-CONTRATO-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 200.00,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.contracts.store'), [
            'order_id' => $order->id,
            'debt_amount' => 12000,
            'fee_amount' => 3000,
            'entry_percentage' => 50,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $contract = Contract::query()->latest('id')->first();

        $this->assertNotNull($contract);
        $this->assertSame($client->id, $contract->user_id);
        $this->assertNotNull($contract->acceptance_token);
        $this->assertNotNull($contract->portal_access_sent_at);

        Queue::assertPushed(EnviarAcessoPortalWhatsApp::class, fn (EnviarAcessoPortalWhatsApp $job) => (int) $job->order->id === (int) $order->id);
        Queue::assertPushed(EnviarLinkAceiteContratoWhatsApp::class, fn (EnviarLinkAceiteContratoWhatsApp $job) => (int) $job->contract->id === (int) $contract->id);
    }
}
