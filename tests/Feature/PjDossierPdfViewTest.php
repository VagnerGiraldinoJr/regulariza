<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PjDossierPdfViewTest extends TestCase
{
    public function test_pj_dossier_view_does_not_expose_tariff_or_consultation_price_terms(): void
    {
        $order = new Order([
            'protocolo' => 'REG-PJ-TESTE-001',
        ]);
        $order->id = 99;

        $report = [
            'meta' => [
                'commercial_protocol' => 'REG-PJ-TESTE-001',
                'generated_at' => Carbon::parse('2026-03-18 14:00:00'),
                'consultation_count' => 4,
            ],
            'company' => [
                'razao_social' => 'EMPRESA TESTE LTDA',
                'document' => '44959669000180',
            ],
            'credit' => [
                'score' => '514',
                'rating' => [
                    'classification' => 'RISCO MÉDIO',
                    'moodys' => 'Ba2',
                    'sp' => 'BB',
                    'fitch' => 'BB',
                ],
                'classe_risco' => 'Risco Médio',
                'situacao' => 'Atenção',
                'instituicoes' => '3',
                'operacoes' => '8',
                'credito_a_vencer' => 'De R$ 200.000,00 a R$ 300.000,00',
                'credito_vencido' => 'R$ 0,00 ou Não Informado',
            ],
            'compliance' => [
                'certidao' => 'Com pendencia',
                'certidao_detail' => 'Indisponível no provedor',
                'protesto' => 'Com pendencia',
                'protesto_detail' => 'Indisponível no provedor',
            ],
            'sources' => [
                [
                    'title' => 'SCR Bacen + Score PJ',
                    'status' => 'success',
                    'status_label' => 'Sucesso',
                    'http_status' => 200,
                    'consulted_at' => '18/03/2026 14:00:00',
                    'endpoint' => 'https://gateway.apibrasil.io/api/v2/consulta/cnpj/credits',
                    'message' => '',
                    'error_message' => '',
                ],
            ],
        ];

        $html = view('admin.management.apibrasil-order-dossier-pj-pdf', [
            'order' => $order,
            'consultations' => new Collection,
            'report' => $report,
        ])->render();

        $this->assertStringNotContainsStringIgnoringCase('tarifado', $html);
        $this->assertStringNotContainsStringIgnoringCase('valor_consulta', $html);
        $this->assertStringNotContainsStringIgnoringCase('api_limit_for', $html);
        $this->assertStringNotContainsStringIgnoringCase('tax', $html);
    }
}
