<?php

namespace Tests\Feature;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\ResearchReport;
use App\Models\ResearchReportItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBrasilForwardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_forward_consultation_and_sync_report_analyst(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $analyst = User::factory()->create(['role' => 'analista']);
        $client = User::factory()->create(['role' => 'cliente']);

        $service = Service::query()->create([
            'nome' => 'Regularização PJ',
            'slug' => 'regularizacao-pj-forward',
            'preco' => 399.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-FWD-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 399.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $report = ResearchReport::query()->create([
            'order_id' => $order->id,
            'user_id' => $client->id,
            'admin_user_id' => $admin->id,
            'report_type' => 'pj',
            'title' => 'Pesquisa PJ Consolidada',
            'document_type' => 'cnpj',
            'document_number' => '12345678000199',
            'status' => 'success',
            'source_count' => 4,
            'success_count' => 4,
            'failure_count' => 0,
            'generated_at' => now(),
        ]);

        $consultation = ApiBrasilConsultation::query()->create([
            'order_id' => $order->id,
            'user_id' => $client->id,
            'admin_user_id' => $admin->id,
            'consultation_key' => 'serasa_premium_pj',
            'consultation_title' => 'Serasa Premium PJ',
            'consultation_category' => 'consulta_cnpj',
            'document_type' => 'cnpj',
            'document_number' => '12345678000199',
            'status' => 'success',
            'provider' => 'bureau',
            'http_status' => 200,
            'response_payload' => ['ok' => true],
        ]);

        ResearchReportItem::query()->create([
            'research_report_id' => $report->id,
            'apibrasil_consultation_id' => $consultation->id,
            'provider' => 'bureau',
            'source_key' => 'serasa_premium_pj',
            'source_title' => 'Serasa Premium PJ',
            'status' => 'success',
            'http_status' => 200,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.management.apibrasil-consultations.forward', $consultation),
            ['analyst_user_id' => $analyst->id]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $consultation->refresh();
        $report->refresh();
        $client->refresh();

        $this->assertSame($analyst->id, $consultation->analyst_user_id);
        $this->assertNotNull($consultation->forwarded_at);
        $this->assertSame($analyst->id, $report->analyst_user_id);
        $this->assertSame($analyst->id, $client->referred_by_user_id);
    }
}
