<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SellerCommission;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_release_and_mark_commission_as_paid_manually(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $seller = User::factory()->create([
            'role' => 'vendedor',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Comissão',
            'slug' => 'pesquisa-comissao-manual',
            'preco' => 150,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-COM-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 150,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $commission = SellerCommission::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'source_type' => 'research',
            'base_amount' => 150,
            'rate' => 0.30,
            'commission_amount' => 45,
            'status' => 'pending',
            'available_at' => now()->addDay(),
        ]);

        $releaseResponse = $this->actingAs($admin)->post(route('admin.management.commissions.release', $commission));
        $releaseResponse->assertRedirect();
        $releaseResponse->assertSessionHas('success');

        $commission->refresh();
        $this->assertSame('available', $commission->status);

        $payResponse = $this->actingAs($admin)->post(route('admin.management.commissions.mark-paid', $commission));
        $payResponse->assertRedirect();
        $payResponse->assertSessionHas('success');

        $commission->refresh();
        $this->assertSame('paid', $commission->status);
        $this->assertNotNull($commission->paid_at);
    }

    public function test_admin_can_cancel_unpaid_commission(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $seller = User::factory()->create([
            'role' => 'vendedor',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Cancelamento',
            'slug' => 'pesquisa-cancelamento-comissao',
            'preco' => 150,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-COM-002',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 150,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $commission = SellerCommission::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'source_type' => 'research',
            'base_amount' => 150,
            'rate' => 0.30,
            'commission_amount' => 45,
            'status' => 'available',
            'available_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.management.commissions.cancel', $commission));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('seller_commissions', [
            'id' => $commission->id,
            'status' => 'canceled',
        ]);
    }
}
