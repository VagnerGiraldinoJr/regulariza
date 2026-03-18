<?php

namespace Tests\Feature;

use App\Models\Lead;
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

    public function test_admin_orders_index_shows_delete_action_only_for_unpaid_orders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil 2',
            'slug' => 'pesquisa-cpf-clean-admin-orders-2',
            'preco' => 90,
            'ativo' => true,
        ]);

        Order::query()->create([
            'protocolo' => 'PED-UNPAID-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 90,
            'pagamento_status' => 'aguardando',
        ]);

        Order::query()->create([
            'protocolo' => 'PED-PAID-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('Excluir');
        $response->assertSee('Pedido pago');
    }

    public function test_admin_orders_index_displays_origin_as_company_or_analyst(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $analyst = User::factory()->create([
            'role' => 'analista',
            'name' => 'Analista Origem',
        ]);

        $client = User::factory()->create([
            'role' => 'cliente',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Origem',
            'slug' => 'pesquisa-origem-admin-orders',
            'preco' => 120,
            'ativo' => true,
        ]);

        $leadByCompany = Lead::query()->create([
            'cpf_cnpj' => '12345678901',
            'tipo_documento' => 'cpf',
            'nome' => 'Lead Empresa',
            'service_id' => $service->id,
        ]);

        $leadByAnalyst = Lead::query()->create([
            'cpf_cnpj' => '12345678000199',
            'tipo_documento' => 'cnpj',
            'nome' => 'Lead Analista',
            'service_id' => $service->id,
            'referred_by_user_id' => $analyst->id,
        ]);

        Order::query()->create([
            'protocolo' => 'PED-ORIGEM-EMPRESA',
            'user_id' => $client->id,
            'lead_id' => $leadByCompany->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 120,
            'pagamento_status' => 'aguardando',
        ]);

        Order::query()->create([
            'protocolo' => 'PED-ORIGEM-ANALISTA',
            'user_id' => $client->id,
            'lead_id' => $leadByAnalyst->id,
            'service_id' => $service->id,
            'status' => 'pendente',
            'valor' => 120,
            'pagamento_status' => 'aguardando',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee('Origem');
        $response->assertSee('Empresa');
        $response->assertSee('Analista (Analista Origem)');
    }
}
