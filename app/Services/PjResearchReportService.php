<?php

namespace App\Services;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use Illuminate\Support\Collection;

class PjResearchReportService
{
    public function build(Order $order, Collection $consultations): array
    {
        $consultations = $consultations
            ->filter(fn ($item) => $item instanceof ApiBrasilConsultation)
            ->values();

        $byKey = $consultations->keyBy('consultation_key');

        $scrPayload = $this->payloadArray($byKey->get('scr_bacen_score_pj'));
        $serasaPayload = $this->payloadArray($byKey->get('serasa_premium_pj'));

        $document = preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: ''));
        if ($document === '') {
            $document = (string) ($consultations->first()?->document_number ?: '-');
        }

        $companyName = $this->firstString([
            $this->findStringByKeys($serasaPayload, ['razao_social', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomeFantasia', 'empresa', 'nome']),
            $this->findStringByKeys($scrPayload, ['razao_social', 'razaoSocial', 'nomeEmpresarial', 'nome_fantasia', 'nomeFantasia', 'empresa', 'nome']),
            (string) ($order->user?->name ?: ''),
        ], '-');

        $scoreValue = $this->firstScalar([
            data_get($scrPayload, 'data.score'),
            data_get($scrPayload, 'score'),
            data_get($serasaPayload, 'data.score'),
            data_get($serasaPayload, 'score'),
        ], '-');

        $riskClass = $this->firstString([
            (string) data_get($scrPayload, 'data.classeRisco'),
            (string) data_get($serasaPayload, 'data.classeRisco'),
        ], '-');

        $creditSituation = $this->firstString([
            (string) data_get($scrPayload, 'data.situacao'),
            (string) data_get($scrPayload, 'data.status'),
            (string) data_get($serasaPayload, 'data.situacao'),
            (string) data_get($serasaPayload, 'data.status'),
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
        ], '-');

        $overdueCredit = $this->firstScalar([
            data_get($scrPayload, 'data.carteiraCredito.valorVencida'),
        ], '-');

        $certidaoPayload = $this->payloadArray($byKey->get('certidao_negativa_pj'));
        $protestoPayload = $this->payloadArray($byKey->get('protesto_nacional_v2'));

        $certidaoStatus = $this->sourceLabel($byKey->get('certidao_negativa_pj'));
        $protestoStatus = $this->sourceLabel($byKey->get('protesto_nacional_v2'));

        $certidaoDetail = $this->firstString([
            (string) data_get($certidaoPayload, 'data.situacao'),
            (string) data_get($certidaoPayload, 'message'),
            (string) ($byKey->get('certidao_negativa_pj')?->error_message ?: ''),
        ], '-');

        $protestoDetail = $this->firstString([
            (string) data_get($protestoPayload, 'data.situacao'),
            (string) data_get($protestoPayload, 'message'),
            (string) ($byKey->get('protesto_nacional_v2')?->error_message ?: ''),
        ], '-');

        $sources = $consultations->map(function (ApiBrasilConsultation $consultation): array {
            $payload = is_array($consultation->response_payload) ? $consultation->response_payload : [];

            return [
                'key' => (string) $consultation->consultation_key,
                'title' => (string) ($consultation->consultation_title ?: $consultation->consultation_key),
                'status' => (string) $consultation->status,
                'status_label' => $consultation->status === 'success' ? 'Sucesso' : 'Falha',
                'http_status' => $consultation->http_status,
                'endpoint' => (string) ($consultation->endpoint ?: '-'),
                'error_message' => (string) ($consultation->error_message ?: ''),
                'message' => $this->firstString([
                    (string) data_get($payload, 'message'),
                    (string) data_get($payload, 'response.message'),
                ], ''),
                'consulted_at' => $consultation->created_at?->format('d/m/Y H:i:s') ?: '-',
            ];
        })->values()->all();

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
}
