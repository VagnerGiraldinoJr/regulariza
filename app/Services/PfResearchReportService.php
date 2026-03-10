<?php

namespace App\Services;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use Illuminate\Support\Collection;

class PfResearchReportService
{
    public function __construct(private readonly CreditRatingService $creditRatingService)
    {
    }

    public function build(Order $order, Collection $consultations): array
    {
        $consultations = $consultations
            ->filter(fn ($item) => $item instanceof ApiBrasilConsultation)
            ->values();

        $byKey = $consultations->keyBy('consultation_key');

        $acertaData = $this->acertaData($byKey->get('acerta_essencial_plus_pf'));
        $scrData = $this->scrData($byKey->get('scr_bacen_score_pf'));

        $primaryScore = $acertaData['score_value'] ?? $acertaData['rating_score_value'] ?? $scrData['score'] ?? null;
        $rating = $this->creditRatingService->resolveFromScore($primaryScore);

        $restrictionCount = (int) ($acertaData['restriction_summary']['count'] ?? 0);
        $protestCount = (int) ($acertaData['protest_summary']['count'] ?? 0);

        return [
            'meta' => [
                'commercial_protocol' => $order->protocolo ?: '-',
                'generated_at' => now(),
                'consultation_count' => $consultations->count(),
                'api_protocol' => $acertaData['protocol'] ?: ($scrData['protocol'] ?: '-'),
                'consultation_date' => $acertaData['consultation_date'] ?: ($scrData['consultation_date'] ?: now()->format('d/m/Y H:i')),
            ],
            'person' => [
                'name' => $acertaData['name'] ?: ($order->user?->name ?? '-'),
                'document' => $acertaData['document'] ?: preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: '')),
                'birth_date' => $acertaData['birth_date'] ?: '-',
                'cpf_status' => $acertaData['cpf_status'] ?: '-',
                'mother_name' => $acertaData['mother_name'] ?: '-',
                'age' => $acertaData['age'] ?: '-',
                'gender' => $acertaData['gender'] ?: '-',
                'rg' => $acertaData['rg'] ?: '-',
                'title' => $acertaData['voter_title'] ?: '-',
                'marital_status' => $acertaData['marital_status'] ?: '-',
                'education' => $acertaData['education'] ?: ($acertaData['education_level'] ?: '-'),
                'dependents' => $acertaData['dependents'] ?: '-',
                'region' => $acertaData['region'] ?: '-',
                'uf' => $acertaData['uf'] ?: '-',
                'rfb_status' => $acertaData['rfb_status'] ?: '-',
                'zodiac' => $acertaData['zodiac'] ?: '-',
                'faixa_idade' => $acertaData['age_band'] ?: '-',
            ],
            'contacts' => [
                'main_email' => $acertaData['main_email'],
                'emails' => $acertaData['emails'],
                'phones' => $acertaData['phones'],
                'employer' => $acertaData['employer'],
                'history' => $acertaData['history'],
            ],
            'addresses' => $acertaData['addresses'],
            'income' => [
                'presumed_income' => $acertaData['presumed_income'] ?: '-',
                'presumed_income_label' => $acertaData['presumed_income_label'] ?: '-',
            ],
            'score' => [
                'value' => $rating['score'],
                'source' => $acertaData['score_source'],
                'band' => $scrData['score_band'] ?: '-',
                'message' => $acertaData['score_message'] ?: '-',
                'probability' => $acertaData['score_probability'] ?: '-',
                'raw_rating_value' => $acertaData['rating_score_value'],
                'rating' => $rating,
            ],
            'restrictions' => [
                'count' => $restrictionCount,
                'total' => $acertaData['restriction_summary']['total'] ?? '',
                'oldest' => $acertaData['restriction_summary']['oldest'] ?? '-',
                'latest' => $acertaData['restriction_summary']['latest'] ?? '-',
                'occurrences' => $acertaData['restriction_occurrences'],
                'alerts' => $acertaData['alerts'],
            ],
            'protests' => [
                'count' => $protestCount,
                'total' => $acertaData['protest_summary']['total'] ?? '',
                'oldest' => $acertaData['protest_summary']['oldest'] ?? '-',
                'latest' => $acertaData['protest_summary']['latest'] ?? '-',
                'records' => $acertaData['protest_occurrences'],
            ],
            'scr' => [
                'database' => $scrData['database'] ?: '-',
                'relationship_since' => $scrData['relationship_since'] ?: '-',
                'institutions' => $scrData['institutions'] ?: '0',
                'operations_count' => $scrData['operations_count'] ?: '0',
                'score' => $scrData['score'],
                'summary' => $scrData['summary'],
                'operations' => $scrData['operations'],
            ],
            'summary' => [
                'risk' => $this->riskLabel($rating['sp'], $restrictionCount, $protestCount),
                'conclusion' => $this->conclusionLabel($rating['sp'], $restrictionCount, $protestCount),
            ],
        ];
    }

    private function acertaData(?ApiBrasilConsultation $consultation): array
    {
        $payload = $this->payload($consultation);
        $response = $this->arrayNode($payload, 'response');
        $data = $this->arrayNode($response, 'data');
        $topLevelData = $this->arrayNode($payload, 'data');

        $plusItems = $this->arrayNode($data, 'dados');
        $plusItem = $this->firstItem($plusItems);
        $acertaPlus = $this->arrayNode($plusItem, 'acertaEssencialPositivo');
        $consultaCredito = $this->arrayNode($acertaPlus, 'consultaCredito');
        $scoreRating = $this->arrayNode($plusItem, 'scoreRating');
        $resumoRetorno = $this->arrayNode($plusItem, 'resumoRetorno');

        $legacyData = $this->arrayNode($response, 'dados');
        $results = $this->firstItem($this->firstNonEmptyArray([
            $this->arrayNode($data, 'results'),
            $this->arrayNode($topLevelData, 'results'),
        ]));
        $socioeconomico = $this->arrayNode($results, 'socioeconomico');
        $vinculo = $this->arrayNode($results, 'vinculo');

        $dadosCadastrais = $this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'dadosCadastrais'),
            $this->arrayNode($results, 'cadastral'),
            $legacyData,
        ]);

        $emails = collect($this->arrayNode($this->arrayNode($consultaCredito, 'contato'), 'email'))
            ->map(fn ($item) => trim((string) ($item['ds_email'] ?? '')))
            ->filter()
            ->values()
            ->all();

        $addresses = collect($this->firstNonEmptyArray([
            $this->arrayNode($this->arrayNode($consultaCredito, 'contato'), 'endereco'),
            [],
        ]))
            ->map(function (array $item): string {
                return implode(', ', array_filter([
                    trim((string) ($item['ds_logradouro'] ?? ($item['endereco'] ?? ''))),
                    trim((string) ($item['nr_numero'] ?? '')),
                    trim((string) ($item['ds_complemento'] ?? '')),
                    trim((string) ($item['ds_bairro'] ?? ($item['bairro'] ?? ''))),
                    trim((string) ($item['ds_cidade'] ?? ($item['cidade'] ?? ''))),
                    trim((string) ($item['sg_uf'] ?? ($item['uf'] ?? ''))),
                    trim((string) ($item['nr_cep'] ?? ($item['cep'] ?? ''))),
                ]));
            })
            ->filter()
            ->values()
            ->all();

        if ($addresses === []) {
            $legacyAddress = implode(', ', array_filter([
                trim((string) ($dadosCadastrais['endereco'] ?? '')),
                trim((string) ($dadosCadastrais['bairro'] ?? '')),
                trim((string) ($dadosCadastrais['cidade'] ?? '')),
                trim((string) ($dadosCadastrais['uf'] ?? '')),
                trim((string) ($dadosCadastrais['cep'] ?? '')),
            ]));

            if ($legacyAddress !== '') {
                $addresses = [$legacyAddress];
            }
        }

        $phones = collect($this->firstNonEmptyArray([
            $this->arrayNode($this->arrayNode($consultaCredito, 'contato'), 'telefone'),
            [],
        ]))
            ->map(function (array $item): string {
                $label = trim((string) ($item['ds_tipo_telefone'] ?? ($item['tipo'] ?? '')));
                $ddd = trim((string) ($item['nr_ddd'] ?? ''));
                $number = trim((string) ($item['nr_telefone'] ?? ($item['telefone'] ?? '')));

                return trim($label.' '.trim($ddd !== '' ? "({$ddd}) {$number}" : $number));
            })
            ->filter()
            ->values()
            ->all();

        if ($phones === [] && filled($dadosCadastrais['telefone'] ?? null)) {
            $phones = [(string) $dadosCadastrais['telefone']];
        }

        $restrictionSummary = $this->normalizeSummary($this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'resumoConsulta')['pendenciasFinanceiras'] ?? [],
            $this->arrayNode($legacyData, 'pend_financeiras'),
        ]));

        $protestSummary = $this->normalizeSummary($this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'resumoConsulta')['protestos'] ?? [],
            $this->arrayNode($legacyData, 'protestos'),
        ]));

        $restrictionOccurrences = collect($this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'pendenciasFinanceiras'),
            $this->arrayNode($legacyData, 'pend_financeiras')['ocorrencias'] ?? [],
        ]))
            ->map(fn ($item) => [
                'creditor' => (string) ($item['credor'] ?? '-'),
                'contract' => (string) ($item['contrato'] ?? '-'),
                'inclusion_date' => (string) ($item['data_inclusao'] ?? '-'),
                'due_date' => (string) ($item['data_vencimento'] ?? '-'),
                'modality' => (string) ($item['modalidade'] ?? '-'),
                'origin' => (string) ($item['origem'] ?? '-'),
                'debtor_type' => (string) ($item['tipo_devedor'] ?? '-'),
                'value' => (string) ($item['valor'] ?? '-'),
            ])
            ->all();

        $protestOccurrences = collect($this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'protestos'),
            [],
        ]))
            ->map(fn ($item) => [
                'date' => (string) ($item['dataOcorrencia'] ?? ($item['data'] ?? '-')),
                'value' => (string) ($item['valor'] ?? '-'),
                'status' => (string) ($item['situacao'] ?? '-'),
                'cartorio' => (string) ($item['cartorio'] ?? '-'),
            ])
            ->all();

        $alerts = collect($this->arrayNode($legacyData, 'informacoes_alertas_restricoes')['ocorrencias'] ?? [])
            ->map(fn ($item) => trim((string) (($item['titulo'] ?? '').': '.($item['observacoes'] ?? ''))))
            ->filter()
            ->values()
            ->all();

        $history = collect($this->firstNonEmptyArray([
            $this->arrayNode($consultaCredito, 'historicoConsultas'),
            [],
        ]))
            ->map(function (array $item): string {
                $date = trim((string) ($item['data'] ?? ''));
                $company = trim((string) ($item['empresa'] ?? ''));

                return trim(implode(' • ', array_filter([$date, $company])));
            })
            ->filter()
            ->values()
            ->all();

        $employers = collect($this->firstNonEmptyArray([
            $this->arrayNode($this->arrayNode($consultaCredito, 'vinculo'), 'empregador'),
            $this->arrayNode($vinculo, 'empregador'),
        ]))
            ->map(fn ($item) => trim((string) (($item['razao_social'] ?? '').($item['dt_admissao'] ? ' • admissão '.$item['dt_admissao'] : ''))))
            ->filter()
            ->values()
            ->all();

        $scoreNode = $this->arrayNode($consultaCredito, 'score');
        $rawScore = $scoreNode['score'] ?? $scoreRating['score'] ?? null;
        $mainEmail = $emails[0] ?? ((string) ($dadosCadastrais['email'] ?? ''));

        return [
            'name' => (string) ($dadosCadastrais['nome'] ?? $dadosCadastrais['nm_completo'] ?? ''),
            'document' => preg_replace('/\D+/', '', (string) ($dadosCadastrais['cpf'] ?? $dadosCadastrais['nr_cpf'] ?? '')),
            'birth_date' => (string) ($dadosCadastrais['dataNascimento'] ?? $dadosCadastrais['dt_nasc'] ?? ''),
            'cpf_status' => (string) ($dadosCadastrais['situacao'] ?? $dadosCadastrais['ds_status_rfb'] ?? ''),
            'mother_name' => (string) ($dadosCadastrais['nomeMae'] ?? $dadosCadastrais['nm_mae'] ?? ''),
            'age' => (string) ($dadosCadastrais['idade'] ?? ''),
            'gender' => (string) ($dadosCadastrais['sexo'] ?? $dadosCadastrais['ds_sexo'] ?? ''),
            'rg' => (string) ($dadosCadastrais['nr_rg'] ?? ''),
            'voter_title' => (string) ($dadosCadastrais['tituloEleitor'] ?? $dadosCadastrais['nr_titulo_eleitoral'] ?? ''),
            'marital_status' => (string) ($dadosCadastrais['estadoCivil'] ?? ''),
            'education' => (string) ($dadosCadastrais['grauInstrucao'] ?? ''),
            'education_level' => (string) ($socioeconomico['ds_grau_escolaridade'] ?? ''),
            'dependents' => (string) ($dadosCadastrais['numeroDependentes'] ?? ''),
            'region' => (string) ($dadosCadastrais['regiaoCPF'] ?? ''),
            'uf' => (string) ($dadosCadastrais['uf'] ?? ''),
            'rfb_status' => (string) ($dadosCadastrais['ds_status_rfb'] ?? ''),
            'zodiac' => (string) ($dadosCadastrais['ds_signo'] ?? ''),
            'age_band' => (string) ($dadosCadastrais['ds_faixa_idade'] ?? ''),
            'presumed_income' => (string) ($dadosCadastrais['rendaPresumida'] ?? ''),
            'presumed_income_label' => (string) ($dadosCadastrais['textoRendaPresumida'] ?? ($socioeconomico['ds_faixa_renda_presumida'] ?? '')),
            'main_email' => $mainEmail,
            'emails' => $emails,
            'phones' => $phones,
            'addresses' => $addresses,
            'restriction_summary' => $restrictionSummary,
            'protest_summary' => $protestSummary,
            'restriction_occurrences' => $restrictionOccurrences,
            'protest_occurrences' => $protestOccurrences,
            'alerts' => $alerts,
            'employer' => $employers,
            'history' => $history,
            'score_value' => $rawScore,
            'rating_score_value' => $scoreRating['score'] ?? null,
            'score_source' => filled($scoreNode['score'] ?? null)
                ? 'Acerta Score'
                : ($scoreRating !== [] ? 'Acerta Rating' : 'SCR Bacen'),
            'score_message' => (string) ($scoreNode['mensagem'] ?? ''),
            'score_probability' => (string) ($scoreNode['probabilidade'] ?? ''),
            'protocol' => (string) ($resumoRetorno['protocolo'] ?? $dadosCadastrais['protocolo'] ?? ''),
            'consultation_date' => (string) ($resumoRetorno['dataConsulta'] ?? $dadosCadastrais['dataConsulta'] ?? ''),
        ];
    }

    private function scrData(?ApiBrasilConsultation $consultation): array
    {
        $payload = $this->payload($consultation);
        $response = $this->arrayNode($payload, 'response');
        $data = $this->arrayNode($response, 'data');
        $dados = $this->arrayNode($data, 'dados');
        $resumo = $this->arrayNode($dados, 'resumoRetorno');

        $scrNode = $this->firstNonEmptyArray([
            $this->arrayNode($this->arrayNode($dados, 'scrBacen'), 'scrBacen'),
            $data,
        ]);

        $consolidado = $this->firstNonEmptyArray([
            $this->arrayNode($scrNode, 'consolidado'),
            $this->arrayNode($data, 'carteiraCredito'),
        ]);

        $score = $this->firstNonEmptyArray([
            $this->arrayNode($scrNode, 'score'),
            ['PONTUACAO' => $data['score'] ?? null, 'FAIXA' => $data['classeRisco'] ?? null],
        ]);

        return [
            'document' => preg_replace('/\D+/', '', (string) ($scrNode['documento'] ?? $resumo['document'] ?? $data['cpf'] ?? '')),
            'consultation_date' => (string) ($resumo['dataConsulta'] ?? $data['dataConsulta'] ?? ''),
            'protocol' => (string) ($resumo['protocolo'] ?? ''),
            'database' => (string) ($scrNode['databaseConsultada'] ?? '-'),
            'relationship_since' => (string) ($scrNode['dataInicioRelacionamento'] ?? '-'),
            'institutions' => (string) ($scrNode['quantidadeInstituicoes'] ?? '0'),
            'operations_count' => (string) ($scrNode['quantidadeOperacoes'] ?? '0'),
            'score' => $score['PONTUACAO'] ?? null,
            'score_band' => (string) ($score['FAIXA'] ?? ''),
            'summary' => [
                'credito_a_vencer' => (string) (($consolidado['creditoAVencer']['valor'] ?? $consolidado['valorVencer'] ?? '0,00')),
                'credito_vencido' => (string) (($consolidado['creditoVencido']['valor'] ?? $consolidado['valorVencida'] ?? '0,00')),
                'limite_credito' => (string) (($consolidado['limiteCredito']['valor'] ?? '0,00')),
                'prejuizo' => (string) (($consolidado['prejuizo']['valor'] ?? '0,00')),
            ],
            'operations' => collect(is_array($scrNode['operacoes'] ?? null) ? $scrNode['operacoes'] : [])
                ->map(fn ($item) => [
                    'modalidade' => (string) ($item['modalidade'] ?? '-'),
                    'sub_modalidade' => (string) ($item['subModalidade'] ?? '-'),
                    'percentual' => (string) ($item['percentual'] ?? '-'),
                    'total' => (string) ($item['total'] ?? '-'),
                ])->all(),
        ];
    }

    private function normalizeSummary(array $summary): array
    {
        return [
            'count' => (int) preg_replace('/\D+/', '', (string) ($summary['quantidadeTotal'] ?? $summary['quantidade_ocorrencia'] ?? 0)),
            'total' => (string) ($summary['valorTotal'] ?? $summary['valor_total'] ?? ''),
            'oldest' => (string) ($summary['data_primeiro'] ?? $summary['ocorrenciaMaisAntiga'] ?? ''),
            'latest' => (string) ($summary['data_ultimo'] ?? $summary['ocorrenciaMaisRecente'] ?? ''),
        ];
    }

    private function payload(?ApiBrasilConsultation $consultation): array
    {
        return $consultation && is_array($consultation->response_payload)
            ? $consultation->response_payload
            : [];
    }

    private function arrayNode(array $source, string $key): array
    {
        $node = $source[$key] ?? null;

        return is_array($node) ? $node : [];
    }

    private function firstItem(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $first = reset($items);

        return is_array($first) ? $first : [];
    }

    private function firstNonEmptyArray(array $candidates): array
    {
        foreach ($candidates as $candidate) {
            if (is_array($candidate) && $candidate !== []) {
                return $candidate;
            }
        }

        return [];
    }

    private function riskLabel(string $rating, int $restrictionCount, int $protestCount): string
    {
        if (in_array($rating, ['AAA', 'AA', 'A', 'BBB'], true) && $restrictionCount === 0 && $protestCount === 0) {
            return 'Baixo';
        }

        if (in_array($rating, ['BB', 'B'], true) || $restrictionCount > 0 || $protestCount > 0) {
            return 'Moderado';
        }

        return 'Elevado';
    }

    private function conclusionLabel(string $rating, int $restrictionCount, int $protestCount): string
    {
        if (in_array($rating, ['AAA', 'AA', 'A', 'BBB'], true) && $restrictionCount === 0 && $protestCount === 0) {
            return 'Perfil com boa capacidade aparente de pagamento e baixo nível de restrições públicas.';
        }

        if (in_array($rating, ['BB', 'B'], true) || $restrictionCount > 0 || $protestCount > 0) {
            return 'Negociação cautelosa. Recomenda-se análise adicional antes da concessão de crédito.';
        }

        return 'Perfil de maior sensibilidade. Recomendado endurecer critérios e revisar exposição de risco.';
    }
}
