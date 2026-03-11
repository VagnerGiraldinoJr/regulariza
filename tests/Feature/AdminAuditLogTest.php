<?php

namespace Tests\Feature;

use App\Models\ApiBrasilConsultation;
use App\Models\ResearchReport;
use App\Models\ResearchReportItem;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_whatsapp_log_with_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'cliente']);

        $log = WhatsappLog::query()->create([
            'user_id' => $client->id,
            'telefone' => '5511999999999',
            'evento' => 'lembrete',
            'mensagem' => 'Mensagem de teste para exclusao.',
            'status' => 'falhou',
            'zapi_response' => ['error' => 'invalid phone'],
            'enviado_em' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.management.messages.destroy', $log));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('whatsapp_logs', ['id' => $log->id]);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'whatsapp_log_deleted',
            'target_type' => 'WhatsappLog',
            'target_id' => $log->id,
        ]);
    }

    public function test_admin_can_delete_apibrasil_consultation_with_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'cliente']);

        $consultation = ApiBrasilConsultation::query()->create([
            'user_id' => $client->id,
            'admin_user_id' => $admin->id,
            'consultation_key' => 'cpf_v1',
            'consultation_title' => 'Consulta CPF',
            'document_type' => 'cpf',
            'document_number' => '36745465825',
            'status' => 'success',
            'provider' => 'apibrasil',
            'endpoint' => '/consulta/cpf',
            'http_status' => 200,
            'response_payload' => ['ok' => true],
        ]);

        $report = ResearchReport::query()->create([
            'user_id' => $client->id,
            'admin_user_id' => $admin->id,
            'report_type' => 'pf',
            'title' => 'Dossie PF',
            'document_type' => 'cpf',
            'document_number' => '36745465825',
            'status' => 'completed',
            'source_count' => 1,
            'success_count' => 1,
            'failure_count' => 0,
            'generated_at' => now(),
        ]);

        $item = ResearchReportItem::query()->create([
            'research_report_id' => $report->id,
            'apibrasil_consultation_id' => $consultation->id,
            'provider' => 'apibrasil',
            'source_key' => 'cpf_v1',
            'source_title' => 'Consulta CPF',
            'status' => 'success',
            'http_status' => 200,
            'response_payload' => ['ok' => true],
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.management.apibrasil-consultations.destroy', $consultation));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('apibrasil_consultations', ['id' => $consultation->id]);
        $this->assertDatabaseHas('admin_action_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'consultation_deleted',
            'target_type' => 'ApiBrasilConsultation',
            'target_id' => $consultation->id,
        ]);
        $this->assertDatabaseHas('research_report_items', [
            'id' => $item->id,
            'apibrasil_consultation_id' => null,
        ]);
    }
}
