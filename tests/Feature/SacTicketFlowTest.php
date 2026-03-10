<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Order;
use App\Models\SacMessage;
use App\Models\SacTicket;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SacTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_open_ticket_and_admin_can_assign_it(): void
    {
        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-sac',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-SAC-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $client->id,
            'debt_amount' => 12000,
            'fee_amount' => 3000,
            'entry_percentage' => 50,
            'entry_amount' => 1500,
            'installments_count' => 3,
            'status' => 'ativo',
            'payment_provider' => 'asaas',
            'acceptance_token' => 'token-sac-001',
            'accepted_at' => now(),
            'activated_at' => now(),
        ]);

        $storeResponse = $this->actingAs($client)->post(route('portal.tickets.store'), [
            'order_id' => $order->id,
            'assunto' => 'Preciso revisar meu contrato',
            'prioridade' => 'alta',
            'mensagem' => 'Mensagem inicial do chamado',
        ]);

        $ticket = SacTicket::query()->latest('id')->first();

        $storeResponse->assertRedirect(route('portal.tickets.show', $ticket->id));
        $this->assertSame('aberto', $ticket->status);
        $this->assertSame($client->id, $ticket->user_id);

        $message = SacMessage::query()->where('sac_ticket_id', $ticket->id)->first();
        $this->assertNotNull($message);
        $this->assertSame('Mensagem inicial do chamado', $message->mensagem);

        $assignResponse = $this->actingAs($admin)->patch(route('admin.tickets.assign', $ticket), []);

        $assignResponse->assertRedirect();

        $ticket->refresh();
        $this->assertSame($admin->id, $ticket->atendente_id);
        $this->assertSame('em_atendimento', $ticket->status);
    }
}
