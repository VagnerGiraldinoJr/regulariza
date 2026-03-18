<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ResearchReport;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResearchReportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_pf_research_report_with_three_sources(): void
    {
        config()->set('services.apibrasil.base_url', 'https://apibrasil.test');
        config()->set('services.apibrasil.token', 'token-apibrasil');
        config()->set('services.apibrasil.homolog', false);

        Http::fake([
            'https://apibrasil.test/api/v2/consulta/cpf/credits' => Http::sequence()
                ->push($this->pfAcertaPayload(), 200)
                ->push($this->pfScrPayload(), 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create([
            'role' => 'cliente',
            'name' => 'Cliente PF',
            'cpf_cnpj' => '36745465825',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-relatorio',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PF-REL-001',
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.management.apibrasil-consultations.store'), [
            'order_id' => $order->id,
            'report_type' => 'pf',
            'document_number' => '367.454.658-25',
            'notes' => 'Relatório PF de teste',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report = ResearchReport::query()->latest('id')->first();

        $this->assertNotNull($report);
        $this->assertSame('pf', $report->report_type);
        $this->assertSame(2, $report->source_count);
        $this->assertSame(2, $report->success_count);
        $this->assertSame(0, $report->failure_count);
        $this->assertSame('success', $report->status);
        $this->assertNotEmpty($report->normalized_payload);
        $this->assertCount(2, $report->items);
        $this->assertSame(
            2,
            $report->items()->distinct('provider')->count('provider')
        );

        $this->assertSame('Cliente PF', data_get($report->normalized_payload, 'person.name'));
        $this->assertSame('36745465825', data_get($report->normalized_payload, 'person.document'));
        $this->assertSame(720, data_get($report->normalized_payload, 'score.value'));

        $pdfResponse = $this->actingAs($admin)->get(
            route('admin.management.apibrasil-consultations.reports.pdf', $report)
        );

        $pdfResponse->assertOk();
        Http::assertSentCount(2);
    }

    public function test_admin_can_generate_pf_research_report_with_alternative_acerta_payload_shape(): void
    {
        config()->set('services.apibrasil.base_url', 'https://apibrasil.test');
        config()->set('services.apibrasil.token', 'token-apibrasil');
        config()->set('services.apibrasil.homolog', false);

        Http::fake([
            'https://apibrasil.test/api/v2/consulta/cpf/credits' => Http::sequence()
                ->push($this->pfAcertaAlternativePayload(), 200)
                ->push($this->pfScrPayload(), 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.management.apibrasil-consultations.store'), [
            'report_type' => 'pf',
            'document_number' => '367.454.658-25',
            'notes' => 'Relatório PF com payload alternativo',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report = ResearchReport::query()->latest('id')->first();

        $this->assertNotNull($report);
        $this->assertSame('success', $report->status);
        $this->assertSame('Cliente Alternativo PF', data_get($report->normalized_payload, 'person.name'));
        $this->assertSame('36745465825', data_get($report->normalized_payload, 'person.document'));
        $this->assertSame('REGULAR', data_get($report->normalized_payload, 'person.cpf_status'));
        $this->assertSame(655, data_get($report->normalized_payload, 'score.value'));
        $this->assertSame('alt@cpfclean.test', data_get($report->normalized_payload, 'contacts.main_email'));
        Http::assertSentCount(2);
    }

    public function test_admin_can_generate_pj_research_report_without_order_and_track_failures(): void
    {
        config()->set('services.apibrasil.base_url', 'https://apibrasil.test');
        config()->set('services.apibrasil.token', 'token-apibrasil');
        config()->set('services.apibrasil.homolog', false);

        Http::fake([
            'https://apibrasil.test/api/v2/consulta/cnpj/credits' => Http::sequence()
                ->push(['data' => ['empresa' => 'Empresa XPTO']], 200)
                ->push(['error' => 'falha'], 500)
                ->push(['response' => ['data' => ['dados' => ['resultado' => ['dadoscadastrais' => ['razaosocial' => 'Empresa XPTO LTDA']]]]]], 200)
                ->push(['response' => ['data' => ['dados' => ['resultado' => ['score' => ['score' => 702]]]]]], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.management.apibrasil-consultations.store'), [
            'report_type' => 'pj',
            'document_number' => '12.345.678/0001-99',
            'notes' => 'Relatório PJ manual',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report = ResearchReport::query()->latest('id')->first();

        $this->assertNotNull($report);
        $this->assertNull($report->order_id);
        $this->assertSame('pj', $report->report_type);
        $this->assertSame(4, $report->source_count);
        $this->assertSame(3, $report->success_count);
        $this->assertSame(1, $report->failure_count);
        $this->assertSame('partial', $report->status);
        $this->assertCount(4, $report->items);
        $this->assertSame(
            4,
            $report->items()->distinct('provider')->count('provider')
        );

        $pdfResponse = $this->actingAs($admin)->get(
            route('admin.management.apibrasil-consultations.reports.pdf', $report)
        );

        $pdfResponse->assertOk();
        Http::assertSentCount(4);
    }

    private function pfAcertaPayload(): array
    {
        return [
            'response' => [
                'data' => [
                    'dados' => [
                        [
                            'acertaEssencialPositivo' => [
                                'consultaCredito' => [
                                    'dadosCadastrais' => [
                                        'nome' => 'Cliente PF',
                                        'cpf' => '36745465825',
                                        'dataNascimento' => '1990-01-01',
                                        'situacao' => 'REGULAR',
                                        'nomeMae' => 'Maria Teste',
                                    ],
                                    'score' => [
                                        'score' => 720,
                                        'mensagem' => 'Perfil estável',
                                        'probabilidade' => '10%',
                                    ],
                                    'resumoConsulta' => [
                                        'pendenciasFinanceiras' => [
                                            'quantidadeTotal' => 0,
                                        ],
                                        'protestos' => [
                                            'quantidadeTotal' => 0,
                                        ],
                                    ],
                                ],
                            ],
                            'scoreRating' => [
                                'score' => 720,
                            ],
                            'resumoRetorno' => [
                                'protocolo' => 'API123',
                                'dataConsulta' => '10/03/2026 12:00',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function pfScrPayload(): array
    {
        return [
            'response' => [
                'data' => [
                    'dados' => [
                        'resumoRetorno' => [
                            'protocolo' => 'SCR123',
                            'dataConsulta' => '10/03/2026 12:05',
                        ],
                        'scrBacen' => [
                            'scrBacen' => [
                                'databaseConsultada' => '02/2026',
                                'dataInicioRelacionamento' => '01/2020',
                                'quantidadeInstituicoes' => '2',
                                'quantidadeOperacoes' => '3',
                                'score' => [
                                    'PONTUACAO' => 680,
                                    'FAIXA' => 'B',
                                ],
                                'consolidado' => [
                                    'creditoAVencer' => ['valor' => '1500,00'],
                                    'creditoVencido' => ['valor' => '0,00'],
                                    'limiteCredito' => ['valor' => '5000,00'],
                                    'prejuizo' => ['valor' => '0,00'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function pfAcertaAlternativePayload(): array
    {
        return [
            'response' => [
                'data' => [
                    'acertaEssencialPositivo' => [
                        'consultaCredito' => [
                            'dadosCadastrais' => [
                                'nome' => 'Cliente Alternativo PF',
                                'cpf' => '36745465825',
                                'dataNascimento' => '1988-05-03',
                                'situacao' => 'REGULAR',
                                'nomeMae' => 'Maria Alternativa',
                                'email' => 'alt@cpfclean.test',
                                'telefone' => '(42) 99999-0000',
                            ],
                            'contato' => [
                                'email' => [
                                    ['ds_email' => 'alt@cpfclean.test'],
                                ],
                            ],
                            'score' => [
                                'score' => 655,
                                'mensagem' => 'Perfil em observação',
                                'probabilidade' => '18%',
                            ],
                            'resumoConsulta' => [
                                'pendenciasFinanceiras' => [
                                    'quantidadeTotal' => 0,
                                ],
                                'protestos' => [
                                    'quantidadeTotal' => 0,
                                ],
                            ],
                        ],
                        'resumoRetorno' => [
                            'protocolo' => 'API-ALT-123',
                            'dataConsulta' => '10/03/2026 17:10',
                        ],
                    ],
                ],
            ],
        ];
    }
}
