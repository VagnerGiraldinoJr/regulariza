<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcilePaidOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reconciles_paid_orders_and_creates_missing_commission(): void
    {
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
            'slug' => 'regularizacao-pf-reconcile-command',
            'preco' => 350.00,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-RECONCILE-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 350.00,
            'pagamento_status' => 'pago',
            'pago_em' => now()->subHour(),
        ]);

        $this->artisan('orders:reconcile-paid', ['order_id' => [$order->id]])
            ->expectsOutput("Pedido #{$order->id} reconciliado.")
            ->expectsOutput('Pedidos reconciliados: 1')
            ->assertExitCode(0);

        $order->refresh();

        $this->assertSame('em_andamento', $order->status);
        $this->assertNotNull($client->fresh()->portal_token);
        $this->assertDatabaseHas('seller_commissions', [
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'source_type' => 'research',
        ]);
    }
}
