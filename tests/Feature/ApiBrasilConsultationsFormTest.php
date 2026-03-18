<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBrasilConsultationsFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_options_show_document_type_and_report_type_mapping(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $clientPf = User::factory()->create([
            'role' => 'cliente',
            'name' => 'Cliente PF',
        ]);

        $clientPj = User::factory()->create([
            'role' => 'cliente',
            'name' => 'Cliente PJ',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa Documento',
            'slug' => 'pesquisa-documento-form',
            'preco' => 89.90,
            'ativo' => true,
        ]);

        $leadPf = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Lead PF',
            'service_id' => $service->id,
        ]);

        $leadPj = Lead::query()->create([
            'cpf_cnpj' => '10650534000117',
            'tipo_documento' => 'cnpj',
            'nome' => 'Lead PJ',
            'service_id' => $service->id,
        ]);

        Order::query()->create([
            'protocolo' => 'REG-PF-0001',
            'user_id' => $clientPf->id,
            'lead_id' => $leadPf->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 89.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        Order::query()->create([
            'protocolo' => 'REG-PJ-0001',
            'user_id' => $clientPj->id,
            'lead_id' => $leadPj->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 89.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.management.apibrasil-consultations'));

        $response->assertOk();
        $response->assertSee('CPF Cliente PF - REG-PF-0001');
        $response->assertSee('CNPJ Cliente PJ - REG-PJ-0001');
        $response->assertSee('data-document-type="cpf"', false);
        $response->assertSee('data-document-type="cnpj"', false);
        $response->assertSee('data-report-type="pf"', false);
        $response->assertSee('data-report-type="pj"', false);
    }
}
