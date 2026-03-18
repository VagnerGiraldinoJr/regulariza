<?php

namespace App\Services;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use Illuminate\Support\Collection;

class PjResearchReportService
{
    public function __construct(private readonly CreditRatingService $creditRatingService) {}

    public function build(Order $order, Collection $consultations): array
    {
        $consultations = $consultations
            ->filter(fn ($item) => $item instanceof ApiBrasilConsultation)
            ->values();

        $byKey = $consultations->keyBy('consultation_key');

        $scrPayload = $this->payloadArray($byKey->get('scr_bacen_score_pj'));
        $serasaPayload = $this->payloadArray($byKey->get('serasa_premium_pj'));
        $defineRiscoPayload = $this->payloadArray($byKey->get('define_risco_pj'));
        $limitePayload = $this->payloadArray($byKey->get('limite_pj'));

        $document = preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: ''));
        if ($document === '') {
            $document = (string) ($consultations->first()?->document_number ?: '-');
        }

        $companyName = $this->firstString([
            $this->findStringByKeys($serasaPayload, ['razao_social', 'razaosocial', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomefantasia', 'nomeFantasia', 'empresa', 'nome']),
            $this->findStringByKeys($scrPayload, ['razao_social', 'razaosocial', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomefantasia', 'nomeFantasia', 'empresa', 'nome']),
            $this->findStringByKeys($defineRiscoPayload, ['razao_social', 'razaosocial', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomefantasia', 'nomeFantasia', 'empresa', 'nome']),
            $this->findStringByKeys($limitePayload, ['razao_social', 'razaosocial', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomefantasia', 'nomeFantasia', 'empresa', 'nome']),
            (string) ($order->user?->name ?: ''),
        ], '-');

        $scoreValue = $this->firstScalar([
            data_get($scrPayload, 'data.score'),
            data_get($scrPayload, 'score'),
            data_get($serasaPayload, 'data.score'),
            data_get($serasaPayload, 'score'),
            data_get($scrPayload, 'response.data.dados.resultado.score.score'),
            data_get($serasaPayload, 'response.data.dados.resultado.score.score'),
            data_get($scrPayload, 'response.dados.scores.ocorrencias.0.score'),
            data_get($serasaPayload, 'response.dados.scores.ocorrencias.0.score'),
            data_get($defineRiscoPayload, 'response.data.dados.resultado.score.score'),
            data_get($limitePayload, 'response.data.dados.resultado.score.score'),
            data_get($defineRiscoPayload, 'response.dados.scores.ocorrencias.0.score'),
            data_get($limitePayload, 'response.dados.scores.ocorrencias.0.score'),
        ], '-');
        $rating = $this->creditRatingService->resolveFromScore($scoreValue);

        $riskClass = $this->firstString([
            (string) data_get($scrPayload, 'data.classeRisco'),
            (string) data_get($serasaPayload, 'data.classeRisco'),
            (string) data_get($scrPayload, 'response.data.dados.resultado.score.mensagem'),
            (string) data_get($serasaPayload, 'response.data.dados.resultado.score.mensagem'),
            (string) data_get($defineRiscoPayload, 'response.data.dados.resultado.score.mensagem'),
            (string) data_get($limitePayload, 'response.data.dados.resultado.score.mensagem'),
        ], '-');

        $creditSituation = $this->firstString([
            (string) data_get($scrPayload, 'data.situacao'),
            (string) data_get($scrPayload, 'data.status'),
            (string) data_get($serasaPayload, 'data.situacao'),
            (string) data_get($serasaPayload, 'data.status'),
            (string) data_get($scrPayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
            (string) data_get($serasaPayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
            (string) data_get($scrPayload, 'response.dados.dados_receita_federal.situacao_receita'),
            (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.situacao_receita'),
            (string) data_get($defineRiscoPayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
            (string) data_get($limitePayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
        ], '-');

        $institutions = $this->firstScalar([
            data_get($scrPayload, 'data.quantidadeInstituicoes'),
            data_get($scrPayload, 'data.instituicoes'),
        ], '0');

        $operations = $this->firstScalar([
            data_get($scrPayload, 'data.quantidadeOperacoes'),
            data_get($scrPayload, 'data.operacoes'),
        ], '0');

        $creditToMature = $this->firstScalar([
            data_get($scrPayload, 'data.carteiraCredito.valorVencer'),
            data_get($scrPayload, 'data.indice.total'),
            data_get($scrPayload, 'response.dados.faturamento_presumido.valor_presumido'),
            data_get($serasaPayload, 'response.dados.faturamento_presumido.valor_presumido'),
            data_get($defineRiscoPayload, 'response.dados.faturamento_presumido.valor_presumido'),
            data_get($limitePayload, 'response.dados.faturamento_presumido.valor_presumido'),
        ], '-');

        $overdueCredit = $this->firstScalar([
            data_get($scrPayload, 'data.carteiraCredito.valorVencida'),
        ], '-');

        $certidaoPayload = $this->payloadArray($byKey->get('certidao_negativa_pj'));
        $protestoPayload = $this->payloadArray($byKey->get('protesto_nacional_v2'));

        $certidaoStatus = $this->sourceLabel($byKey->get('certidao_negativa_pj'));
        $protestoStatus = $this->sourceLabel($byKey->get('protesto_nacional_v2'));
        if ($byKey->has('define_risco_pj')) {
            $certidaoStatus = $this->sourceLabel($byKey->get('define_risco_pj'));
        }
        if ($byKey->has('limite_pj')) {
            $protestoStatus = $this->sourceLabel($byKey->get('limite_pj'));
        }

        $certidaoDetail = $this->firstString([
            (string) data_get($certidaoPayload, 'data.situacao'),
            (string) data_get($certidaoPayload, 'message'),
            (string) ($byKey->get('certidao_negativa_pj')?->error_message ?: ''),
            (string) data_get($defineRiscoPayload, 'response.message'),
            (string) data_get($defineRiscoPayload, 'response.data.msg'),
        ], '-');

        $protestoDetail = $this->firstString([
            (string) data_get($protestoPayload, 'data.situacao'),
            (string) data_get($protestoPayload, 'message'),
            (string) ($byKey->get('protesto_nacional_v2')?->error_message ?: ''),
            (string) data_get($limitePayload, 'response.message'),
            (string) data_get($limitePayload, 'response.data.msg'),
        ], '-');

        $sources = $consultations->map(function (ApiBrasilConsultation $consultation): array {
            $payload = is_array($consultation->response_payload) ? $consultation->response_payload : [];
            $httpStatus = $consultation->http_status;
            $status = (string) $consultation->status;
            $isWarning = $status !== 'success' && in_array((int) $httpStatus, [404, 422, 429], true);

            return [
                'key' => (string) $consultation->consultation_key,
                'title' => (string) ($consultation->consultation_title ?: $consultation->consultation_key),
                'status' => $status,
                'status_label' => $status === 'success'
                    ? 'Sucesso'
                    : ($isWarning ? 'Indisponivel' : 'Falha'),
                'http_status' => $httpStatus,
                'endpoint' => (string) ($consultation->endpoint ?: '-'),
                'error_message' => (string) ($consultation->error_message ?: ''),
                'message' => $status === 'success' ? '' : (string) ($consultation->error_message ?: ''),
                'consulted_at' => $consultation->created_at?->format('d/m/Y H:i:s') ?: '-',
            ];
        })->values()->all();

        $judicialMetrics = $this->judicialMetrics($consultations);
        $basicPjMetrics = $this->basicPjMetrics($consultations);
        if (($basicPjMetrics['business']['company_name'] ?? '') !== '') {
            $companyName = (string) $basicPjMetrics['business']['company_name'];
        }
        if (($basicPjMetrics['business']['trade_name'] ?? '') !== '') {
            $companyName = (string) $basicPjMetrics['business']['trade_name'];
        }

        return [
            'meta' => [
                'commercial_protocol' => (string) ($order->protocolo ?: '-'),
                'generated_at' => now(),
                'consultation_count' => $consultations->count(),
            ],
            'company' => [
                'razao_social' => $companyName,
                'document' => $document,
            ],
            'credit' => [
                'score' => $scoreValue,
                'rating' => $rating,
                'classe_risco' => $riskClass,
                'situacao' => $creditSituation,
                'instituicoes' => $institutions,
                'operacoes' => $operations,
                'credito_a_vencer' => $creditToMature,
                'credito_vencido' => $overdueCredit,
            ],
            'compliance' => [
                'certidao' => $certidaoStatus,
                'certidao_detail' => $certidaoDetail,
                'protesto' => $protestoStatus,
                'protesto_detail' => $protestoDetail,
            ],
            'judicial' => $judicialMetrics,
            'business' => $basicPjMetrics['business'],
            'credit_behavior' => $basicPjMetrics['credit_behavior'],
            'partners' => $basicPjMetrics['partners'],
            'sources' => $sources,
        ];
    }

    private function payloadArray(?ApiBrasilConsultation $consultation): array
    {
        return is_array($consultation?->response_payload) ? $consultation->response_payload : [];
    }

    private function sourceLabel(?ApiBrasilConsultation $consultation): string
    {
        if (! $consultation) {
            return '-';
        }

        return $consultation->status === 'success' ? 'Regular' : 'Com pendencia';
    }

    private function findStringByKeys(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array($key, $keys, true) && is_scalar($value)) {
                $candidate = trim((string) $value);
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            if (is_array($value)) {
                $nested = $this->findStringByKeys($value, $keys);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function firstString(array $values, string $fallback): string
    {
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $fallback;
    }

    private function firstScalar(array $values, string $fallback): string
    {
        foreach ($values as $value) {
            if (is_scalar($value)) {
                $candidate = trim((string) $value);
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return $fallback;
    }

    private function judicialMetrics(Collection $consultations): array
    {
        $allProcesses = collect();

        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation) {
                continue;
            }

            $payload = is_array($consultation->response_payload) ? $consultation->response_payload : [];
            if ($payload === []) {
                continue;
            }

            $processes = $this->extractProcesses($payload);
            if ($processes->isNotEmpty()) {
                $allProcesses = $allProcesses->merge($processes);
            }
        }

        $allProcesses = $allProcesses
            ->filter(fn ($item) => is_array($item))
            ->values();

        if ($allProcesses->isEmpty()) {
            return [
                'count' => 0,
                'active_count' => 0,
                'archived_count' => 0,
                'tribunals' => [],
                'top_cases' => [],
            ];
        }

        $activeCount = 0;
        $archivedCount = 0;

        foreach ($allProcesses as $process) {
            $status = mb_strtolower(trim((string) data_get($process, 'statusPj.statusProcesso', '')));
            if (str_contains($status, 'arquiv')) {
                $archivedCount++;
            } else {
                $activeCount++;
            }
        }

        $tribunals = $allProcesses
            ->map(fn ($process) => trim((string) ($process['tribunal'] ?? '')))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values()
            ->all();

        $topCases = $allProcesses
            ->take(8)
            ->map(function (array $process): array {
                return [
                    'number' => (string) ($process['numeroProcessoUnico'] ?? '-'),
                    'tribunal' => (string) ($process['tribunal'] ?? '-'),
                    'subject' => (string) data_get($process, 'classeProcessual.nome', '-'),
                    'status' => (string) data_get($process, 'statusPj.statusProcesso', ($process['statusObservacao'] ?? '-')),
                ];
            })
            ->all();

        return [
            'count' => $allProcesses->count(),
            'active_count' => $activeCount,
            'archived_count' => $archivedCount,
            'tribunals' => $tribunals,
            'top_cases' => $topCases,
        ];
    }

    private function extractProcesses(array $payload): Collection
    {
        $candidates = [
            data_get($payload, 'response.data.dados.acoesProcessos.acoes.processos', []),
            data_get($payload, 'data.dados.acoesProcessos.acoes.processos', []),
            data_get($payload, 'acoesProcessos.acoes.processos', []),
            data_get($payload, 'acoes.processos', []),
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && $candidate !== []) {
                return collect($candidate);
            }
        }

        return collect();
    }

    private function basicPjMetrics(Collection $consultations): array
    {
        $resultado = null;

        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation || ! is_array($consultation->response_payload)) {
                continue;
            }

            $candidate = data_get($consultation->response_payload, 'data.resultado');
            if (is_array($candidate) && $candidate !== []) {
                $resultado = $candidate;
                break;
            }

            $candidate = data_get($consultation->response_payload, 'response.data.dados.resultado');
            if (is_array($candidate) && $candidate !== []) {
                $resultado = $candidate;
                break;
            }
        }

        if (! is_array($resultado) || $resultado === []) {
            return [
                'business' => [],
                'credit_behavior' => [],
                'partners' => [],
            ];
        }

        $dadosCadastrais = (array) data_get($resultado, 'dados_cadastrais', []);
        if ($dadosCadastrais === []) {
            $dadosCadastrais = (array) data_get($resultado, 'dadoscadastrais', []);
        }
        $consultas = (array) data_get($resultado, 'consultas', []);
        $quadroSocietario = (array) data_get($resultado, 'quadro_societario', []);
        $socios = is_array($quadroSocietario['socios'] ?? null) ? $quadroSocietario['socios'] : [];

        $metrics = [
            'business' => [
                'company_name' => (string) ($dadosCadastrais['nome_empresa'] ?? ($dadosCadastrais['razaosocial'] ?? '')),
                'trade_name' => (string) ($dadosCadastrais['nome_fantasia'] ?? ($dadosCadastrais['nomefantasia'] ?? '')),
                'status' => (string) ($dadosCadastrais['status_empresa'] ?? ($dadosCadastrais['situacao'] ?? '')),
                'main_activity' => (string) ($dadosCadastrais['descricao_atividade_principal'] ?? ''),
                'secondary_activity' => (string) ($dadosCadastrais['descricao_atividade_secundaria'] ?? ''),
                'telefone' => (string) ($dadosCadastrais['numero_telefone'] ?? ''),
                'email' => (string) data_get($dadosCadastrais, 'emails.emails', ''),
                'capital_social' => (string) ($quadroSocietario['capital_social'] ?? ''),
            ],
            'credit_behavior' => [
                'ultimos_30_dias' => (int) ($consultas['contagem_consultas_ultimos_30_dias'] ?? 0),
                'de_31_a_60_dias' => (int) ($consultas['contagem_consultas_31_a_60_dias'] ?? 0),
                'de_61_a_90_dias' => (int) ($consultas['contagem_consultas_61_a_90_dias'] ?? 0),
                'mais_90_dias' => (int) ($consultas['contagem_consultas_mais_90_dias'] ?? 0),
                'status_cadastro_positivo' => (string) ($resultado['status_cadastro_positivo'] ?? ''),
            ],
            'partners' => collect($socios)->map(function (array $partner): array {
                return [
                    'name' => (string) ($partner['nomes'] ?? '-'),
                    'document' => (string) ($partner['cpf_cnpj'] ?? '-'),
                    'type' => (string) ($partner['tipo_entidade'] ?? '-'),
                    'relationship' => (string) ($partner['descricao_relacionamento'] ?? '-'),
                    'share' => (string) ($partner['percentual_participacao'] ?? '-'),
                    'status' => (string) ($partner['status'] ?? '-'),
                ];
            })->values()->all(),
        ];

        if ($payloadFallback = $this->fallbackBasicPjMetrics($consultations)) {
            $metrics = array_replace_recursive($metrics, $payloadFallback);
        }

        return $metrics;
    }

    private function fallbackBasicPjMetrics(Collection $consultations): array
    {
        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation || ! is_array($consultation->response_payload)) {
                continue;
            }

            $dados = (array) data_get($consultation->response_payload, 'response.dados', []);
            if ($dados === []) {
                continue;
            }

            return [
                'business' => [
                    'company_name' => (string) data_get($dados, 'dados_receita_federal.razao_social', ''),
                    'status' => (string) data_get($dados, 'dados_receita_federal.situacao_receita', ''),
                    'main_activity' => (string) data_get($dados, 'dados_receita_federal.atividade_economica_principal', ''),
                    'email' => (string) data_get($dados, 'dados_receita_federal.email', ''),
                ],
                'credit_behavior' => [
                    'status_cadastro_positivo' => (string) data_get($dados, 'faturamento_presumido.dados_cadastro_positivo', ''),
                ],
            ];
        }

        return [];
    }
}
