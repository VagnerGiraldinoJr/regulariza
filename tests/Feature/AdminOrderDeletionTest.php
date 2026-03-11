<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_unpaid_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Excluir',
            'slug' => 'pesquisa-excluir-pedido',
            'preco' => 99.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-DEL-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 99.90,
            'pagamento_status' => 'aguardando',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_delete_paid_order(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Excluir Pago',
            'slug' => 'pesquisa-excluir-pedido-pago',
            'preco' => 99.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-DEL-002',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 99.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.orders.index'))->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHasErrors('order_delete');
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
