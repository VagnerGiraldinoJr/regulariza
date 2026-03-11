<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_orders_index_displays_order_value(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil',
            'slug' => 'pesquisa-cpf-clean-admin-orders',
            'preco' => 123.45,
            'ativo' => true,
        ]);

        Order::query()->create([
            'protocolo' => 'PED-VALOR-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 123.45,
            'pagamento_status' => 'aguardando',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('Valor');
        $response->assertSee('R$ 123,45');
    }
}
