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
        $businessCreditPayload = $this->payloadArray($byKey->get('analise_credito_business_pj'));
        $basicCreditPayload = $this->payloadArray($byKey->get('analise_credito_basic_pj'));
        $creditPjPayload = $this->firstArray([$businessCreditPayload, $basicCreditPayload]);
        $quodPayload = $this->payloadArray($byKey->get('spc_quod_pj'));
        $serasaPayload = $this->payloadArray($byKey->get('serasa_premium_pj'));
        $bureauPayload = $this->firstArray([$quodPayload, $serasaPayload]);
        $complianceCompletePayload = $this->payloadArray($byKey->get('compliance_complete_pj'));
        $complianceBasicPayload = $this->payloadArray($byKey->get('compliance_basic_pj'));
        $defineRiscoPayload = $this->payloadArray($byKey->get('define_risco_pj'));
        $limitePayload = $this->payloadArray($byKey->get('limite_pj'));

        $document = preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: ''));
        if ($document === '') {
            $document = (string) ($consultations->first()?->document_number ?: '-');
        }

        $completeResult = $this->firstResultPayload([
            $complianceCompletePayload,
            $basicCreditPayload,
            $businessCreditPayload,
            $bureauPayload,
            $defineRiscoPayload,
            $limitePayload,
        ]);
        $basicResult = $this->firstResultPayload([
            $basicCreditPayload,
            $businessCreditPayload,
            $complianceBasicPayload,
            $bureauPayload,
            $defineRiscoPayload,
            $limitePayload,
        ]);
        if ($completeResult === []) {
            $completeResult = $this->firstResultFromConsultations($consultations);
        }
        if ($basicResult === []) {
            $basicResult = $this->firstResultFromConsultations($consultations);
        }

        $companyName = $this->firstString([
            $this->findStringByKeys($completeResult, ['razao_social_nome_empresarial', 'razao_social', 'razaosocial', 'nome_empresa', 'nomeEmpresarial', 'empresa', 'nome']),
            $this->findStringByKeys($basicResult, ['razao_social_nome_empresarial', 'razao_social', 'razaosocial', 'nome_empresa', 'nomeEmpresarial', 'empresa', 'nome']),
            $this->findStringByKeys($bureauPayload, ['razao_social_nome_empresarial', 'razao_social', 'razaosocial', 'nome_empresa', 'nomeEmpresarial', 'empresa', 'nome']),
            (string) ($order->user?->name ?: ''),
        ], '-');

        $tradeName = $this->firstString([
            $this->findStringByKeys($completeResult, ['nome_fantasia', 'nomefantasia', 'trade_name']),
            $this->findStringByKeys($basicResult, ['nome_fantasia', 'nomefantasia', 'trade_name']),
        ], '-');

        $scoreValue = $this->firstScalar([
            data_get($scrPayload, 'data.score'),
            data_get($scrPayload, 'score'),
            data_get($quodPayload, 'response.dados.scores.ocorrencias.0.score'),
            data_get($quodPayload, 'response.data.dados.scores.ocorrencias.0.score'),
            data_get($bureauPayload, 'data.score'),
            data_get($bureauPayload, 'score'),
            data_get($bureauPayload, 'data.cnpj.score'),
            data_get($bureauPayload, 'data.cnpj.scores.ocorrencias.0.score'),
            data_get($bureauPayload, 'response.data.dados.resultado.score.score'),
            data_get($scrPayload, 'response.data.dados.resultado.score.score'),
            data_get($bureauPayload, 'response.data.dados.resultado.score.score'),
            data_get($bureauPayload, 'response.dados.scores.ocorrencias.0.score'),
            data_get($basicCreditPayload, 'data.resultado.score.numero_score'),
            data_get($businessCreditPayload, 'data.resultado.score.0.score'),
            data_get($businessCreditPayload, 'response.data.resultado.score.0.score'),
            data_get($defineRiscoPayload, 'response.data.dados.resultado.score.score'),
            data_get($limitePayload, 'response.data.dados.resultado.score.score'),
            data_get($completeResult, 'score.numero_score'),
            data_get($basicResult, 'score.numero_score'),
        ], '-');

        $rating = $this->creditRatingService->resolveFromScore($scoreValue);

        $riskClass = $this->firstString([
            (string) data_get($scrPayload, 'data.classeRisco'),
            (string) data_get($quodPayload, 'response.dados.scores.ocorrencias.0.descr_score'),
            (string) data_get($quodPayload, 'response.dados.scores.ocorrencias.0.risco'),
            (string) data_get($quodPayload, 'response.dados.scores.ocorrencias.0.texto'),
            (string) data_get($bureauPayload, 'data.classeRisco'),
            (string) data_get($bureauPayload, 'data.cnpj.scores.ocorrencias.0.descr_score'),
            (string) data_get($bureauPayload, 'data.cnpj.scores.ocorrencias.0.risco'),
            (string) data_get($bureauPayload, 'response.dados.scores.ocorrencias.0.descr_score'),
            (string) data_get($basicCreditPayload, 'data.resultado.score.descricao'),
            (string) data_get($businessCreditPayload, 'data.resultado.score.0.classificacao_alfabetica'),
            (string) data_get($businessCreditPayload, 'data.resultado.decisao.descricao'),
            (string) data_get($defineRiscoPayload, 'response.data.dados.resultado.score.mensagem'),
            (string) data_get($limitePayload, 'response.data.dados.resultado.score.mensagem'),
            (string) data_get($completeResult, 'score.descricao'),
            (string) data_get($completeResult, 'score.descricao_complementar_score'),
            (string) data_get($basicResult, 'score.descricao'),
        ], '-');

        $scoreProbability = $this->firstString([
            (string) data_get($completeResult, 'score.probabilidade_pagamento'),
            (string) data_get($completeResult, 'score.descricao_probabilidade_pagamento'),
            (string) data_get($basicResult, 'score.probabilidade_pagamento'),
            (string) data_get($basicCreditPayload, 'data.resultado.score.probabilidade_pagamento'),
            (string) data_get($quodPayload, 'response.dados.scores.ocorrencias.0.probabilidade_inadimplencia'),
            (string) data_get($bureauPayload, 'data.cnpj.scores.ocorrencias.0.probabilidade_inadimplencia'),
            (string) data_get($defineRiscoPayload, 'response.data.dados.resultado.score.probabilidade'),
            (string) data_get($limitePayload, 'response.data.dados.resultado.score.probabilidade'),
            (string) data_get($bureauPayload, 'response.dados.scores.ocorrencias.0.probabilidade_inadimplencia'),
            (string) data_get($businessCreditPayload, 'data.resultado.score.0.probabilidade'),
        ], '-');

        $creditSituation = $this->firstString([
            (string) data_get($scrPayload, 'data.situacao'),
            (string) data_get($scrPayload, 'data.status'),
            (string) data_get($bureauPayload, 'response.dados.dados_receita_federal.situacao_receita'),
            (string) data_get($basicCreditPayload, 'data.resultado.dados_cadastrais.status_empresa'),
            (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.situacao_cnpj'),
            (string) data_get($defineRiscoPayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
            (string) data_get($limitePayload, 'response.data.dados.resultado.dadoscadastrais.situacao'),
            (string) data_get($completeResult, 'situacao_cadastral.ds_situacao_cadastral'),
            (string) data_get($basicResult, 'situacao_cadastral.ds_situacao_cadastral'),
            (string) data_get($completeResult, 'dados_cadastrais.status_empresa'),
            (string) data_get($basicResult, 'dados_cadastrais.status_empresa'),
        ], '-');

        $institutions = $this->firstScalar([
            data_get($scrPayload, 'data.quantidadeInstituicoes'),
            data_get($scrPayload, 'data.instituicoes'),
            data_get($scrPayload, 'response.data.dados.scrBacen.scrBacen.quantidadeInstituicoes'),
            data_get($businessCreditPayload, 'data.resultado.scrBacen.quantidadeInstituicoes'),
        ], '0');

        $operations = $this->firstScalar([
            data_get($scrPayload, 'data.quantidadeOperacoes'),
            data_get($scrPayload, 'data.operacoes'),
            data_get($scrPayload, 'response.data.dados.scrBacen.scrBacen.quantidadeOperacoes'),
            data_get($businessCreditPayload, 'data.resultado.scrBacen.quantidadeOperacoes'),
            data_get($businessCreditPayload, 'data.resultado.consultas.quantidade_total'),
        ], '0');

        $creditToMature = $this->firstScalar([
            data_get($scrPayload, 'data.carteiraCredito.valorVencer'),
            data_get($scrPayload, 'data.indice.total'),
            data_get($scrPayload, 'response.data.dados.scrBacen.scrBacen.consolidado.creditoAVencer.valor'),
            data_get($businessCreditPayload, 'data.resultado.scrBacen.consolidado.creditoAVencer.valor'),
            data_get($completeResult, 'firmografico.valor_faturamento_presumido'),
            data_get($basicResult, 'firmografico.valor_faturamento_presumido'),
            data_get($bureauPayload, 'response.dados.faturamento_presumido.valor_presumido'),
        ], '-');

        $overdueCredit = $this->firstScalar([
            data_get($scrPayload, 'data.carteiraCredito.valorVencida'),
            data_get($scrPayload, 'response.data.dados.scrBacen.scrBacen.consolidado.creditoVencido.valor'),
            data_get($businessCreditPayload, 'data.resultado.scrBacen.consolidado.creditoVencido.valor'),
            data_get($bureauPayload, 'response.dados.protesto_sintetico.valor_total'),
        ], '-');

        $businessMetrics = $this->buildBusinessMetrics($completeResult, $basicResult, $bureauPayload, $creditPjPayload);
        $creditBehavior = $this->buildCreditBehavior($completeResult, $basicResult, $creditPjPayload);
        $contacts = $this->buildContacts($completeResult, $basicResult, $bureauPayload, $creditPjPayload);
        $publicDebts = $this->buildPublicDebts($completeResult, $basicResult);
        $negativeMetrics = $this->buildNegativeMetrics($completeResult, $basicResult, $bureauPayload, $creditPjPayload);
        $compliance = $this->buildComplianceSummary($byKey, $completeResult, $basicResult);
        $complianceEntries = $this->buildComplianceEntries($completeResult, $basicResult);
        $partners = $this->buildPartners($completeResult, $basicResult, $bureauPayload, $creditPjPayload);
        $businessIndicators = $this->buildBusinessIndicators($completeResult);
        $consultationHistory = $this->buildConsultationHistory($completeResult, $basicResult, $creditPjPayload);
        $registration = $this->buildRegistration($completeResult, $basicResult, $bureauPayload, $creditPjPayload);
        $patrimony = $this->buildPatrimony($completeResult, $basicResult);

        if (($businessMetrics['company_name'] ?? '') !== '') {
            $companyName = (string) $businessMetrics['company_name'];
        }
        if (($businessMetrics['trade_name'] ?? '') !== '' && $tradeName === '-') {
            $tradeName = (string) $businessMetrics['trade_name'];
        }

        $sources = $consultations->map(function (ApiBrasilConsultation $consultation): array {
            $httpStatus = $consultation->http_status;
            $status = (string) $consultation->status;
            $isWarning = $status !== 'success' && in_array((int) $httpStatus, [400, 404, 422, 429], true);
            $sanitizedError = $this->sanitizeOperationalMessage((string) ($consultation->error_message ?: ''));

            return [
                'key' => (string) $consultation->consultation_key,
                'title' => (string) ($consultation->consultation_title ?: $consultation->consultation_key),
                'status' => $status,
                'status_label' => $status === 'success'
                    ? 'Sucesso'
                    : ($isWarning ? 'Indisponivel' : 'Falha'),
                'http_status' => $httpStatus,
                'endpoint' => (string) ($consultation->endpoint ?: '-'),
                'error_message' => $sanitizedError,
                'message' => $status === 'success' ? '' : $sanitizedError,
                'consulted_at' => $consultation->created_at?->format('d/m/Y H:i:s') ?: '-',
            ];
        })->values()->all();

        $hasComplianceSource = $consultations->contains(
            fn (ApiBrasilConsultation $consultation) => in_array(
                (string) $consultation->consultation_key,
                ['compliance_complete_pj', 'compliance_basic_pj'],
                true
            )
        );

        $judicialMetrics = $this->judicialMetrics($consultations);

        return [
            'meta' => [
                'commercial_protocol' => (string) ($order->protocolo ?: '-'),
                'generated_at' => now(),
                'consultation_count' => $consultations->count(),
            ],
            'company' => [
                'razao_social' => $companyName,
                'nome_fantasia' => $tradeName,
                'document' => $document,
            ],
            'credit' => [
                'score' => $scoreValue,
                'rating' => $rating,
                'classe_risco' => $riskClass,
                'probabilidade' => $scoreProbability,
                'situacao' => $creditSituation,
                'instituicoes' => $institutions,
                'operacoes' => $operations,
                'credito_a_vencer' => $creditToMature,
                'credito_vencido' => $overdueCredit,
                'has_scr' => array_key_exists('scr_bacen_score_pj', $byKey->all()),
            ],
            'compliance' => $hasComplianceSource ? $compliance : [],
            'compliance_entries' => $hasComplianceSource ? $complianceEntries : [],
            'judicial' => $judicialMetrics,
            'business' => $businessMetrics,
            'credit_behavior' => $creditBehavior,
            'contacts' => $contacts,
            'public_debts' => $publicDebts,
            'negatives' => $negativeMetrics,
            'partners' => $partners,
            'business_indicators' => $businessIndicators,
            'consultation_history' => $consultationHistory,
            'registration' => $registration,
            'patrimony' => $patrimony,
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

    private function firstResultPayload(array $payloads): array
    {
        foreach ($payloads as $payload) {
            if (! is_array($payload) || $payload === []) {
                continue;
            }

            $paths = [
                'data.resultado',
                'response.data.dados.resultado',
                'response.dados',
                'response.data.dados',
            ];

            foreach ($paths as $path) {
                $candidate = data_get($payload, $path);
                if (is_array($candidate) && $candidate !== []) {
                    return $candidate;
                }
            }
        }

        return [];
    }

    private function firstResultFromConsultations(Collection $consultations): array
    {
        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation || ! is_array($consultation->response_payload)) {
                continue;
            }

            $candidate = $this->firstResultPayload([$consultation->response_payload]);
            if ($candidate !== []) {
                return $candidate;
            }
        }

        return [];
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
            if (! is_scalar($value) && ! $value instanceof \Stringable) {
                continue;
            }

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

    private function buildBusinessMetrics(array $completeResult, array $basicResult, array $serasaPayload, array $businessCreditPayload): array
    {
        $serasaCnpj = (array) data_get($serasaPayload, 'data.cnpj', data_get($serasaPayload, 'data.dados', []));
        $serasaEmpresa = (array) data_get($serasaCnpj, 'empresa', []);
        $businessIdentificacao = (array) data_get($businessCreditPayload, 'data.resultado.identificacao_completo', []);

        $dadosCadastrais = $this->mergeAssoc(
            (array) data_get($basicResult, 'dados_cadastrais', []),
            (array) data_get($completeResult, 'dados_cadastrais', [])
        );

        if ($dadosCadastrais === []) {
            $dadosCadastrais = (array) data_get($basicResult, 'dadoscadastrais', []);
        }

        return [
            'company_name' => $this->firstString([
                (string) ($dadosCadastrais['razao_social_nome_empresarial'] ?? ''),
                (string) ($dadosCadastrais['nome_empresa'] ?? ''),
                (string) ($dadosCadastrais['razaosocial'] ?? ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.razao_social', ''),
                (string) ($serasaEmpresa['razao_social'] ?? ''),
                (string) ($businessIdentificacao['razao_social'] ?? ''),
                (string) data_get($serasaPayload, 'data.nome', ''),
            ], ''),
            'trade_name' => $this->firstString([
                (string) ($dadosCadastrais['nome_fantasia'] ?? ''),
                (string) ($dadosCadastrais['nomefantasia'] ?? ''),
                (string) ($serasaCnpj['nome_fantasia'] ?? ''),
            ], ''),
            'status' => $this->firstString([
                (string) data_get($completeResult, 'situacao_cadastral.ds_situacao_cadastral', ''),
                (string) ($dadosCadastrais['status_empresa'] ?? ''),
                (string) ($dadosCadastrais['situacao'] ?? ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.situacao_receita', ''),
                (string) data_get($serasaPayload, 'data.situacao', ''),
                (string) ($serasaCnpj['situacao_cadastral'] ?? ''),
                (string) ($businessIdentificacao['situacao_cnpj'] ?? ''),
            ], '-'),
            'main_activity' => $this->firstString([
                (string) ($dadosCadastrais['ds_cnae_fiscal_principal'] ?? ''),
                (string) ($dadosCadastrais['descricao_atividade_principal'] ?? ''),
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.ramo_atividade_primario.atividade', ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.atividade_economica_principal', ''),
                (string) ($serasaCnpj['cnae_fiscal'] ?? ''),
                (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.ramo_atividade_primario.atividade', ''),
            ], '-'),
            'secondary_activity' => $this->firstString([
                (string) ($dadosCadastrais['descricao_atividade_secundaria'] ?? ''),
                (string) ($dadosCadastrais['ds_cnae_fiscal_secundarios.0'] ?? ''),
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.ramo_atividade_secundario.atividade', ''),
                (string) ($dadosCadastrais['descricao_atividade_secundaria'] ?? ''),
                (string) ($serasaCnpj['cnae_fiscal_secundaria'] ?? ''),
                (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.ramo_atividade_secundario.atividade', ''),
            ], '-'),
            'natureza_juridica' => $this->firstString([
                (string) ($dadosCadastrais['ds_natureza_juridica'] ?? ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.natureza_juridica', ''),
                (string) ($serasaEmpresa['natureza_juridica'] ?? ''),
                (string) ($businessIdentificacao['natureza_juridica'] ?? ''),
            ], '-'),
            'capital_social' => $this->firstString([
                (string) data_get($completeResult, 'firmografico.capital_social', ''),
                (string) data_get($basicResult, 'quadro_societario.capital_social', ''),
                (string) ($serasaEmpresa['capital_social'] ?? ''),
                (string) ($businessIdentificacao['capital_atual'] ?? ''),
                (string) ($businessIdentificacao['capital_inicial'] ?? ''),
            ], '-'),
            'porte' => $this->firstString([
                (string) data_get($completeResult, 'firmografico.ds_porte_empresa', ''),
                (string) data_get($completeResult, 'firmografico.porte_td', ''),
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.porte_empresa', ''),
                (string) data_get($serasaPayload, 'data.cnpj.empresa.porte_empresa', ''),
            ], '-'),
            'faixa_faturamento' => $this->firstString([
                (string) data_get($completeResult, 'firmografico.ds_faixa_faturamento', ''),
                (string) data_get($serasaPayload, 'response.dados.faturamento_presumido.faturamento_anual', ''),
                (string) data_get($serasaPayload, 'data.cnpj.empresa.faturamento_presumido', ''),
            ], '-'),
            'faturamento_presumido' => $this->firstString([
                (string) data_get($completeResult, 'firmografico.valor_faturamento_presumido', ''),
                (string) data_get($serasaPayload, 'response.dados.faturamento_presumido.valor_presumido', ''),
                (string) data_get($serasaPayload, 'data.cnpj.empresa.faturamento_presumido', ''),
            ], '-'),
            'foundation_date' => $this->firstString([
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.data_fundacao', ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.data_nascimento_fundacao', ''),
                (string) ($serasaCnpj['data_inicio_atividades'] ?? ''),
                (string) ($businessIdentificacao['data_fundacao'] ?? ''),
            ], '-'),
        ];
    }

    private function buildCreditBehavior(array $completeResult, array $basicResult, array $businessCreditPayload): array
    {
        $consultas = $this->mergeAssoc(
            (array) data_get($basicResult, 'consultas', []),
            (array) data_get($completeResult, 'consultas', [])
        );
        $businessConsultas = (array) data_get($businessCreditPayload, 'data.resultado.consultas', []);

        return [
            'ultimos_30_dias' => (int) ($consultas['contagem_consultas_ultimos_30_dias'] ?? data_get($businessConsultas, 'consultas_mes.0.quantidade', 0)),
            'de_31_a_60_dias' => (int) ($consultas['contagem_consultas_31_a_60_dias'] ?? 0),
            'de_61_a_90_dias' => (int) ($consultas['contagem_consultas_61_a_90_dias'] ?? 0),
            'mais_90_dias' => (int) ($consultas['contagem_consultas_mais_90_dias'] ?? 0),
            'status_cadastro_positivo' => $this->firstString([
                (string) ($completeResult['status_cadastro_positivo'] ?? ''),
                (string) ($basicResult['status_cadastro_positivo'] ?? ''),
                (string) data_get($businessCreditPayload, 'data.resultado.status_consumidor.mensagem', ''),
            ], ''),
        ];
    }

    private function buildContacts(array $completeResult, array $basicResult, array $serasaPayload, array $businessCreditPayload): array
    {
        $emails = collect();
        $phones = collect();
        $addresses = collect();
        $serasaCnpj = (array) data_get($serasaPayload, 'data.cnpj', data_get($serasaPayload, 'data.dados', []));
        $businessMatriz = (array) data_get($businessCreditPayload, 'data.resultado.localizacao_completo.matriz', []);

        foreach ([
            data_get($completeResult, 'dados_contato.emails', []),
            data_get($basicResult, 'dados_contato.emails', []),
        ] as $sourceEmails) {
            if (is_array($sourceEmails)) {
                foreach ($sourceEmails as $emailRow) {
                    $value = trim((string) data_get($emailRow, 'emails', ''));
                    if ($value !== '') {
                        $emails->push($value);
                    }
                }
            }
        }

        $legacyEmail = (string) data_get($basicResult, 'dados_cadastrais.emails.emails', '');
        if ($legacyEmail !== '') {
            $emails->push($legacyEmail);
        }
        $serasaCnpjEmail = trim((string) ($serasaCnpj['correio_eletronico'] ?? ''));
        if ($serasaCnpjEmail !== '') {
            $emails->push($serasaCnpjEmail);
        }
        $serasaEmail = (string) data_get($basicResult, 'dados_receita_federal.email', '');
        if ($serasaEmail !== '') {
            $emails->push($serasaEmail);
        }

        foreach ([
            data_get($completeResult, 'dados_contato.telefones', []),
            data_get($basicResult, 'dados_contato.telefones', []),
        ] as $sourcePhones) {
            if (is_array($sourcePhones)) {
                foreach ($sourcePhones as $phoneRow) {
                    $ddd = trim((string) data_get($phoneRow, 'ddd', ''));
                    $number = trim((string) data_get($phoneRow, 'numero', ''));
                    $type = trim((string) data_get($phoneRow, 'tipo', ''));
                    $value = trim($ddd.' '.$number);
                    if ($value !== '') {
                        $phones->push(trim($value.($type !== '' ? ' ('.$type.')' : '')));
                    }
                }
            }
        }

        $legacyPhone = (string) data_get($basicResult, 'dados_cadastrais.numero_telefone', '');
        if ($legacyPhone !== '') {
            $phones->push($legacyPhone);
        }
        $serasaCnpjPhone = trim((string) (($serasaCnpj['ddd1'] ?? '').' '.($serasaCnpj['telefone1'] ?? '')));
        if ($serasaCnpjPhone !== '') {
            $phones->push($serasaCnpjPhone);
        }
        $serasaPhone = (string) data_get($basicResult, 'identificacao_completo.fgts.telefone', '');
        if ($serasaPhone !== '') {
            $phones->push($serasaPhone);
        }

        foreach ([
            data_get($completeResult, 'dados_contato.logradouros', []),
            data_get($basicResult, 'dados_contato.logradouros', []),
        ] as $sourceAddresses) {
            if (is_array($sourceAddresses)) {
                foreach ($sourceAddresses as $row) {
                    $parts = array_filter([
                        trim((string) data_get($row, 'logradouro_tipo', '')),
                        trim((string) data_get($row, 'logradouro_endereco', '')),
                        trim((string) data_get($row, 'logradouro_numero', '')),
                        trim((string) data_get($row, 'logradouro_complemento', '')),
                        trim((string) data_get($row, 'logradouro_bairro', '')),
                        trim((string) data_get($row, 'logradouro_municipio', '')),
                        trim((string) data_get($row, 'logradouro_uf', '')),
                        trim((string) data_get($row, 'logradouro_cep', '')),
                    ]);
                    if ($parts !== []) {
                        $addresses->push(implode(', ', $parts));
                    }
                }
            }
        }

        $legacyAddress = (array) data_get($basicResult, 'dados_cadastrais.enderecos', []);
        if ($legacyAddress !== []) {
            $parts = array_filter([
                trim((string) ($legacyAddress['rua'] ?? '')),
                trim((string) ($legacyAddress['numero'] ?? '')),
                trim((string) ($legacyAddress['complemento'] ?? '')),
                trim((string) ($legacyAddress['bairro'] ?? '')),
                trim((string) ($legacyAddress['cidade'] ?? '')),
                trim((string) ($legacyAddress['estado'] ?? '')),
                trim((string) ($legacyAddress['codigo_postal'] ?? '')),
            ]);
            if ($parts !== []) {
                $addresses->push(implode(', ', $parts));
            }
        }
        $serasaCnpjAddress = array_filter([
            trim((string) ($serasaCnpj['tipo_logradouro'] ?? '')),
            trim((string) ($serasaCnpj['logradouro'] ?? '')),
            trim((string) ($serasaCnpj['numero'] ?? '')),
            trim((string) ($serasaCnpj['complemento'] ?? '')),
            trim((string) ($serasaCnpj['bairro'] ?? '')),
            trim((string) data_get($serasaCnpj, 'municipio.descricao', '')),
            trim((string) ($serasaCnpj['uf'] ?? '')),
            trim((string) ($serasaCnpj['cep'] ?? '')),
        ]);
        if ($serasaCnpjAddress !== []) {
            $addresses->push(implode(', ', $serasaCnpjAddress));
        }
        $serasaMatrizAddress = trim((string) data_get($basicResult, 'localizacao_completo.matriz.endereco_matriz', ''));
        if ($serasaMatrizAddress !== '') {
            $parts = array_filter([
                $serasaMatrizAddress,
                trim((string) data_get($basicResult, 'localizacao_completo.matriz.bairro_matriz', '')),
                trim((string) data_get($basicResult, 'localizacao_completo.matriz.cidade_matriz', '')),
                trim((string) data_get($basicResult, 'localizacao_completo.matriz.uf_matriz', '')),
                trim((string) data_get($basicResult, 'localizacao_completo.matriz.cep_matriz', '')),
            ]);
            if ($parts !== []) {
                $addresses->push(implode(', ', $parts));
            }
        }
        if ($businessMatriz !== []) {
            $parts = array_filter([
                trim((string) ($businessMatriz['endereco_matriz'] ?? '')),
                trim((string) ($businessMatriz['bairro_matriz'] ?? '')),
                trim((string) ($businessMatriz['cidade_matriz'] ?? '')),
                trim((string) ($businessMatriz['uf_matriz'] ?? '')),
                trim((string) ($businessMatriz['cep_matriz'] ?? '')),
            ]);
            if ($parts !== []) {
                $addresses->push(implode(', ', $parts));
            }
        }

        return [
            'emails' => $emails->map(fn ($item) => mb_strtoupper((string) $item))->unique()->values()->all(),
            'phones' => $phones->unique()->values()->all(),
            'addresses' => $addresses->unique()->values()->all(),
        ];
    }

    private function buildPublicDebts(array $completeResult, array $basicResult): array
    {
        $debts = $this->mergeAssoc(
            (array) data_get($basicResult, 'dividas_publicas', data_get($basicResult, 'divida_publica', [])),
            (array) data_get($completeResult, 'dividas_publicas', data_get($completeResult, 'divida_publica', []))
        );

        $rows = [];
        foreach ([
            'pgfn_fgts' => 'PGFN FGTS',
            'pgfn_nao_previdenciario' => 'PGFN Não Previdenciário',
            'pgfn_previdenciario' => 'PGFN Previdenciário',
        ] as $key => $label) {
            $node = (array) ($debts[$key] ?? []);
            $rows[] = [
                'title' => $label,
                'quantity' => (string) ($node['quantidade'] ?? '0'),
                'value' => (string) ($node['valor'] ?? '0,00'),
            ];
        }

        return $rows;
    }

    private function buildNegativeMetrics(array $completeResult, array $basicResult, array $serasaPayload, array $businessCreditPayload): array
    {
        $negativacoes = $this->mergeAssoc(
            (array) data_get($basicResult, 'negativacoes', []),
            (array) data_get($completeResult, 'negativacoes', [])
        );

        $protestos = $this->firstArray([
            data_get($completeResult, 'protestos', []),
            data_get($basicResult, 'protestos', []),
        ]);

        return [
            'controle_pendencias_credito' => (string) ($negativacoes['controle_pendencias_credito'] ?? '0'),
            'apontamentos' => is_array($negativacoes['apontamentos'] ?? null) ? count($negativacoes['apontamentos']) : 0,
            'ccf' => is_array($negativacoes['ccf_apontamentos'] ?? null) ? count($negativacoes['ccf_apontamentos']) : 0,
            'acoes_judiciais' => is_array($negativacoes['acoes_judiciais_apontamentos'] ?? null) ? count($negativacoes['acoes_judiciais_apontamentos']) : 0,
            'protestos_total' => $this->firstString([
                (string) data_get($protestos, '0.total_protestos'),
                (string) data_get($serasaPayload, 'response.dados.protesto_sintetico.quantidade_ocorrencia', ''),
                (string) data_get($businessCreditPayload, 'data.resultado.protestos.total_protestos', ''),
                '0',
            ], '0'),
            'protestos_valor' => $this->firstString([
                (string) data_get($protestos, '0.valor_protestados_total'),
                (string) data_get($serasaPayload, 'response.dados.protesto_sintetico.valor_total', ''),
                (string) data_get($businessCreditPayload, 'data.resultado.protestos.valor_total', ''),
                '0,00',
            ], '0,00'),
        ];
    }

    private function buildComplianceSummary(Collection $byKey, array $completeResult, array $basicResult): array
    {
        $completeSource = $byKey->get('compliance_complete_pj');
        $basicSource = $byKey->get('compliance_basic_pj');

        $rows = $this->buildComplianceEntries($completeResult, $basicResult);
        $withHit = collect($rows)->first(fn (array $row) => (int) $row['quantity'] > 0);

        return [
            'certidao' => $this->sourceLabel($completeSource ?: $basicSource),
            'certidao_detail' => $withHit
                ? 'Ocorrências em '.$withHit['title'].': '.$withHit['quantity']
                : 'Sem ocorrências em listas de compliance consultadas.',
            'protesto' => $this->sourceLabel($basicSource ?: $completeSource),
            'protesto_detail' => $withHit
                ? 'Verifique seção de Compliance e Órgãos para detalhes.'
                : 'Sem ocorrências de compliance retornadas.',
        ];
    }

    private function buildComplianceEntries(array $completeResult, array $basicResult): array
    {
        $complete = (array) data_get($completeResult, 'compliance', []);
        $basic = (array) data_get($basicResult, 'compliance', []);

        $map = [
            'ceis' => 'CEIS',
            'cepim' => 'CEPIM',
            'cnep' => 'CNEP',
            'ibama' => 'IBAMA',
            'mpf_leniencia' => 'MPF Leniencia',
            'mpt_lista_suja' => 'MPT Lista Suja',
            'pep' => 'PEP',
            'tcu' => 'TCU',
        ];

        $rows = [];
        foreach ($map as $key => $label) {
            $completeItems = is_array($complete[$key] ?? null) ? $complete[$key] : [];
            $basicItems = is_array($basic[$key] ?? null) ? $basic[$key] : [];
            $quantity = max(count($completeItems), count($basicItems));

            $rows[] = [
                'key' => $key,
                'title' => $label,
                'quantity' => $quantity,
                'status' => $quantity > 0 ? 'Alerta' : 'Regular',
            ];
        }

        return $rows;
    }

    private function buildPartners(array $completeResult, array $basicResult, array $serasaPayload, array $businessCreditPayload): array
    {
        $completePartners = is_array(data_get($completeResult, 'quadro_societario.socios'))
            ? data_get($completeResult, 'quadro_societario.socios')
            : [];

        $basicPartners = is_array(data_get($basicResult, 'quadro_societario.socios'))
            ? data_get($basicResult, 'quadro_societario.socios')
            : [];
        $serasaPartners = is_array(data_get($serasaPayload, 'data.cnpj.socios'))
            ? data_get($serasaPayload, 'data.cnpj.socios')
            : [];
        $businessPartners = is_array(data_get($businessCreditPayload, 'data.resultado.socios'))
            ? data_get($businessCreditPayload, 'data.resultado.socios')
            : [];

        $partners = collect(array_merge($completePartners, $basicPartners, $serasaPartners, $businessPartners))
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $partner): array {
                $document = $this->firstString([
                    (string) ($partner['cpf_cnpj_socio_tratado'] ?? ''),
                    (string) ($partner['cpf_cnpj'] ?? ''),
                    (string) ($partner['cpf_cnpj_socio'] ?? ''),
                    (string) ($partner['cnpj_cpf_socio'] ?? ''),
                ], '-');

                return [
                    'name' => $this->firstString([
                        (string) ($partner['nome_socio_razao_social'] ?? ''),
                        (string) ($partner['nomes'] ?? ''),
                    ], '-'),
                    'document' => $document,
                    'document_digits' => preg_replace('/\D+/', '', $document),
                    'type' => $this->firstString([
                        (string) ($partner['ds_identificador_socio'] ?? ''),
                        (string) ($partner['tipo_entidade'] ?? ''),
                        (string) ($partner['tipo_socio'] ?? ''),
                        (string) ($partner['tipo_documento'] ?? ''),
                    ], '-'),
                    'relationship' => $this->firstString([
                        (string) ($partner['ds_qualificacao_socio'] ?? ''),
                        (string) ($partner['descricao_relacionamento'] ?? ''),
                        (string) data_get($partner, 'qualificacao.descricao', ''),
                        (string) ($partner['qualificacao_socio'] ?? ''),
                        (string) ($partner['assina_empresa'] ?? ''),
                    ], '-'),
                    'share' => $this->firstString([
                        (string) ($partner['percentual_participacao'] ?? ''),
                        (string) ($partner['valor_participacao'] ?? ''),
                    ], '-'),
                    'status' => $this->firstString([
                        (string) ($partner['status'] ?? ''),
                        (string) ($partner['nivel_confianca'] ?? ''),
                    ], '-'),
                ];
            })
            ->reject(function (array $partner): bool {
                $name = trim((string) ($partner['name'] ?? ''));
                $digits = trim((string) ($partner['document_digits'] ?? ''));

                return ($name === '' || $name === '-')
                    && ($digits === '' || strlen($digits) < 11);
            })
            ->unique(fn ($partner) => ($partner['document_digits'] ?: ($partner['document'] ?? '')).'|'.($partner['name'] ?? ''))
            ->map(function (array $partner): array {
                unset($partner['document_digits']);

                return $partner;
            })
            ->values()
            ->all();

        return $partners;
    }

    private function buildBusinessIndicators(array $completeResult): array
    {
        $indicators = (array) data_get($completeResult, 'indicadores_de_negocios', []);
        if ($indicators === []) {
            return [];
        }

        $groups = [];
        foreach ($indicators as $groupKey => $groupValue) {
            if (! is_array($groupValue)) {
                continue;
            }

            $items = [];
            foreach ($groupValue as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $items[] = [
                    'name' => (string) ($item['nomes'] ?? '-'),
                    'risk' => (string) ($item['risco'] ?? '-'),
                    'description' => (string) ($item['descricao'] ?? '-'),
                ];
            }

            if ($items !== []) {
                $groups[] = [
                    'title' => $this->normalizeIndicatorTitle((string) $groupKey),
                    'items' => $items,
                ];
            }
        }

        return $groups;
    }

    private function normalizeIndicatorTitle(string $key): string
    {
        $clean = str_replace('_', ' ', trim($key));

        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    private function buildConsultationHistory(array $completeResult, array $basicResult, array $businessCreditPayload): array
    {
        $consultas = $this->mergeAssoc(
            (array) data_get($basicResult, 'consultas', []),
            (array) data_get($completeResult, 'consultas', [])
        );

        $details = is_array($consultas['detalhes_consultas'] ?? null) ? $consultas['detalhes_consultas'] : [];
        if ($details === []) {
            $details = is_array(data_get($businessCreditPayload, 'data.resultado.consultas.ultimas_consultas'))
                ? data_get($businessCreditPayload, 'data.resultado.consultas.ultimas_consultas')
                : [];
        }

        $normalized = collect($details)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row): array {
                $date = (array) ($row['data_consulta'] ?? []);
                $formattedDate = trim((string) ($date['dia'] ?? '')).'/'.trim((string) ($date['mes'] ?? '')).'/'.trim((string) ($date['ano'] ?? ''));
                $formattedDate = trim($formattedDate, '/');
                if ($formattedDate === '' && is_string($row['data'] ?? null)) {
                    $formattedDate = (string) $row['data'];
                }

                return [
                    'date' => $formattedDate !== '' ? $formattedDate : '-',
                    'segment' => (string) ($row['segmento'] ?? ($row['razao_social'] ?? '-')),
                    'count' => (int) ($row['contagem_consultas'] ?? ($row['quantidade'] ?? 0)),
                ];
            })
            ->values()
            ->all();

        return [
            'total_30' => (int) ($consultas['contagem_consultas_ultimos_30_dias'] ?? 0),
            'total_31_60' => (int) ($consultas['contagem_consultas_31_a_60_dias'] ?? 0),
            'total_61_90' => (int) ($consultas['contagem_consultas_61_a_90_dias'] ?? 0),
            'total_90_plus' => (int) ($consultas['contagem_consultas_mais_90_dias'] ?? 0),
            'details' => $normalized,
        ];
    }

    private function buildRegistration(array $completeResult, array $basicResult, array $serasaPayload, array $businessCreditPayload): array
    {
        $serasaCnpj = (array) data_get($serasaPayload, 'data.cnpj', data_get($serasaPayload, 'data.dados', []));

        return [
            'situacao_cadastral' => $this->firstString([
                (string) data_get($completeResult, 'situacao_cadastral.ds_situacao_cadastral', ''),
                (string) data_get($basicResult, 'dados_cadastrais.status_empresa', ''),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.situacao_receita', ''),
                (string) ($serasaCnpj['situacao_cadastral'] ?? ''),
                (string) data_get($serasaPayload, 'data.situacao', ''),
                (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.situacao_cnpj', ''),
            ], '-'),
            'data_inicio_atividade' => $this->firstString([
                (string) data_get($completeResult, 'dados_cadastrais.data_inicio_atividade', ''),
                $this->dateValue(data_get($basicResult, 'dados_cadastrais.data_fundacao')),
                (string) data_get($serasaPayload, 'response.dados.dados_receita_federal.data_nascimento_fundacao', ''),
                (string) ($serasaCnpj['data_inicio_atividades'] ?? ''),
                (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.data_fundacao', ''),
            ], '-'),
            'nire' => $this->firstString([
                (string) data_get($basicResult, 'dados_cadastrais.nire', ''),
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.numero_nire', ''),
                (string) data_get($businessCreditPayload, 'data.resultado.identificacao_completo.numero_nire', ''),
            ], '-'),
            'regime' => $this->firstString([
                data_get($completeResult, 'regime_tributario.opcao_simples') === true ? 'Simples Nacional' : '',
                data_get($completeResult, 'regime_tributario.opcao_mei') === true ? 'MEI' : '',
                data_get($basicResult, 'regime_tributario.opcao_simples') === true ? 'Simples Nacional' : '',
                data_get($basicResult, 'regime_tributario.opcao_mei') === true ? 'MEI' : '',
                (string) data_get($basicResult, 'dados_cadastrais.tipo_legal', ''),
                (string) data_get($serasaPayload, 'response.dados.identificacao_completo.natureza_juridica', ''),
                (string) data_get($serasaPayload, 'data.cnpj.empresa.natureza_juridica', ''),
            ], '-'),
        ];
    }

    private function buildPatrimony(array $completeResult, array $basicResult): array
    {
        $multi = $this->firstArray([
            data_get($completeResult, 'patrimonio.multiempresarial', []),
            data_get($basicResult, 'patrimonio.multiempresarial', []),
        ]);

        return [
            'multiempresarial_count' => is_array($multi) ? count($multi) : 0,
            'filiais_count' => is_array(data_get($completeResult, 'filiais')) ? count(data_get($completeResult, 'filiais')) : 0,
        ];
    }

    private function mergeAssoc(array $base, array $extra): array
    {
        if ($base === []) {
            return $extra;
        }

        if ($extra === []) {
            return $base;
        }

        return array_replace_recursive($base, $extra);
    }

    private function firstArray(array $values): array
    {
        foreach ($values as $value) {
            if (is_array($value) && $value !== []) {
                return $value;
            }
        }

        return [];
    }

    private function dateValue(mixed $value): string
    {
        if (is_array($value)) {
            $year = trim((string) ($value['ano'] ?? ''));
            $month = trim((string) ($value['mes'] ?? ''));
            $day = trim((string) ($value['dia'] ?? ''));

            if ($year !== '' && $month !== '' && $day !== '') {
                return str_pad($day, 2, '0', STR_PAD_LEFT).'/'.str_pad($month, 2, '0', STR_PAD_LEFT).'/'.$year;
            }

            return '';
        }

        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return '';
        }

        return trim((string) $value);
    }

    private function sanitizeOperationalMessage(string $message): string
    {
        $clean = trim($message);
        if ($clean === '') {
            return '';
        }

        // Never expose charging information in customer-facing reports.
        $clean = preg_replace('/valor\s+da\s+consulta\s*:\s*r\$\s*[\d\.,]+!?/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/voc[eê]\s+foi\s+tarifado\s+em\s+r\$\s*[\d\.,]+!?/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/r\$\s*[\d\.,]+/iu', 'R$ ***', $clean) ?? $clean;
        $clean = preg_replace('/\s{2,}/', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\n{2,}/', "\n", $clean) ?? $clean;

        return trim($clean);
    }
}
