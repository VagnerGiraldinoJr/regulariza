<?php

namespace Tests\Feature;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\User;
use App\Services\PjResearchReportService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PjResearchReportServiceTest extends TestCase
{
    public function test_build_extracts_judicial_metrics_from_acoes_processos_payload(): void
    {
        $client = User::factory()->make([
            'name' => 'Empresa Teste',
            'cpf_cnpj' => '10650534000117',
        ]);

        $order = new Order([
            'protocolo' => 'REG-PJ-JUD-001',
        ]);
        $order->id = 10;
        $order->setRelation('user', $client);

        $consultation = new ApiBrasilConsultation([
            'consultation_key' => 'acoes_processos',
            'consultation_title' => 'Ações e Processos',
            'status' => 'success',
            'http_status' => 200,
            'response_payload' => $this->acoesProcessosPayload(),
        ]);
        $consultation->created_at = now();

        $service = app(PjResearchReportService::class);
        $report = $service->build($order, new Collection([$consultation]));

        $this->assertSame(3, data_get($report, 'judicial.count'));
        $this->assertSame(1, data_get($report, 'judicial.active_count'));
        $this->assertSame(2, data_get($report, 'judicial.archived_count'));
        $this->assertNotEmpty(data_get($report, 'judicial.tribunals'));
        $this->assertNotEmpty(data_get($report, 'judicial.top_cases'));
    }

    public function test_build_extracts_basic_pj_business_and_partners_metrics(): void
    {
        $client = User::factory()->make([
            'name' => 'Empresa Teste',
            'cpf_cnpj' => '44959669000180',
        ]);

        $order = new Order([
            'protocolo' => 'REG-PJ-BASIC-001',
        ]);
        $order->id = 11;
        $order->setRelation('user', $client);

        $consultation = new ApiBrasilConsultation([
            'consultation_key' => 'analise_credito_basic_pj',
            'consultation_title' => 'Análise de Crédito Basic PJ',
            'status' => 'success',
            'http_status' => 200,
            'response_payload' => $this->basicPjPayload(),
        ]);
        $consultation->created_at = now();

        $service = app(PjResearchReportService::class);
        $report = $service->build($order, new Collection([$consultation]));

        $this->assertSame('EMPRESA FICTICIA LTDA', data_get($report, 'business.company_name'));
        $this->assertSame('FAKE COMERCIO', data_get($report, 'business.trade_name'));
        $this->assertSame('ATIVO', data_get($report, 'business.status'));
        $this->assertSame(1, data_get($report, 'credit_behavior.ultimos_30_dias'));
        $this->assertSame(2, data_get($report, 'credit_behavior.de_31_a_60_dias'));
        $this->assertSame('1', data_get($report, 'credit_behavior.status_cadastro_positivo'));
        $this->assertCount(2, data_get($report, 'partners'));
    }

    private function acoesProcessosPayload(): array
    {
        return [
            'response' => [
                'data' => [
                    'dados' => [
                        'acoesProcessos' => [
                            'acoes' => [
                                'processos' => [
                                    [
                                        'numeroProcessoUnico' => '10000000020234000000',
                                        'tribunal' => 'JF-FICT',
                                        'classeProcessual' => ['nome' => 'TERMO CIRCUNSTANCIADO'],
                                        'statusPj' => ['statusProcesso' => 'EM TRAMITACAO'],
                                    ],
                                    [
                                        'numeroProcessoUnico' => '20000000020226000000',
                                        'tribunal' => 'TRE-FICT',
                                        'classeProcessual' => ['nome' => 'COMPOSICAO DE MESA'],
                                        'statusPj' => ['statusProcesso' => 'ARQUIVAMENTO DEFINITIVO'],
                                    ],
                                    [
                                        'numeroProcessoUnico' => '30000000020198000000',
                                        'tribunal' => 'TJ-FICT',
                                        'classeProcessual' => ['nome' => 'PROCEDIMENTO DO JUIZADO ESPECIAL CIVEL'],
                                        'statusPj' => ['statusProcesso' => 'ARQUIVAMENTO DEFINITIVO'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function basicPjPayload(): array
    {
        return [
            'data' => [
                'resultado' => [
                    'consultas' => [
                        'contagem_consultas_ultimos_30_dias' => 1,
                        'contagem_consultas_31_a_60_dias' => 2,
                        'contagem_consultas_61_a_90_dias' => 1,
                        'contagem_consultas_mais_90_dias' => 5,
                    ],
                    'dados_cadastrais' => [
                        'nome_empresa' => 'EMPRESA FICTICIA LTDA',
                        'nome_fantasia' => 'FAKE COMERCIO',
                        'status_empresa' => 'ATIVO',
                        'descricao_atividade_principal' => 'COMERCIO VAREJISTA DE MERCADORIAS',
                        'descricao_atividade_secundaria' => 'DESENVOLVIMENTO DE PROGRAMAS DE COMPUTADOR',
                        'numero_telefone' => '1133334444',
                        'emails' => [
                            'emails' => 'CONTATO@EMPRESA-FAKE.COM.BR',
                        ],
                    ],
                    'quadro_societario' => [
                        'capital_social' => '50000',
                        'socios' => [
                            [
                                'nomes' => 'JOAO DA SILVA FICTICIO',
                                'cpf_cnpj' => '12345678901',
                                'tipo_entidade' => 'PF',
                                'descricao_relacionamento' => 'SOCIO',
                                'percentual_participacao' => '60.0',
                                'status' => 'Regular',
                            ],
                            [
                                'nomes' => 'MARIA SANTOS FICTICIA',
                                'cpf_cnpj' => '98765432100',
                                'tipo_entidade' => 'PF',
                                'descricao_relacionamento' => 'SOCIO',
                                'percentual_participacao' => '40.0',
                                'status' => 'Regular',
                            ],
                        ],
                    ],
                    'status_cadastro_positivo' => '1',
                ],
            ],
        ];
    }
}
