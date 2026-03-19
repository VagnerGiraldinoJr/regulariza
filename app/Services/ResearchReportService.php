<?php

namespace App\Services;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\ResearchReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ResearchReportService
{
    public function __construct(
        private readonly ResearchProviderManager $providerManager,
        private readonly PfResearchReportService $pfResearchReportService,
        private readonly PjResearchReportService $pjResearchReportService
    ) {}

    public function createFromBundle(
        ?Order $order,
        User $admin,
        string $reportType,
        string $documentNumber,
        ?string $notes = null
    ): ResearchReport {
        $bundle = $this->bundle($reportType);
        $documentDigits = preg_replace('/\D+/', '', $documentNumber);
        $sources = collect((array) ($bundle['sources'] ?? []))
            ->map(fn ($source) => $this->resolveSourceDefinition($source))
            ->values();

        if ($documentDigits === '') {
            throw new RuntimeException('Documento inválido para gerar o dossiê.');
        }

        if ($sources->isEmpty()) {
            throw new RuntimeException('O pacote selecionado não possui fontes configuradas.');
        }

        return DB::transaction(function () use ($order, $admin, $reportType, $bundle, $documentDigits, $sources, $notes): ResearchReport {
            $order?->loadMissing(['lead', 'user']);

            $report = ResearchReport::query()->create([
                'order_id' => $order?->id,
                'lead_id' => $order?->lead_id,
                'user_id' => $order?->user_id,
                'admin_user_id' => $admin->id,
                'analyst_user_id' => $this->resolveAnalystId($order),
                'report_type' => $reportType,
                'title' => (string) ($bundle['title'] ?? strtoupper($reportType)),
                'document_type' => (string) ($bundle['document_type'] ?? $this->documentTypeFromDigits($documentDigits)),
                'document_number' => $documentDigits,
                'status' => 'processing',
                'source_count' => $sources->count(),
                'notes' => $notes ?: null,
            ]);

            $consultations = collect();

            foreach ($sources as $source) {
                $sourceForExecution = $source;
                if (($source['consultation_key'] ?? '') === 'certidao_negativa_pj') {
                    $resolvedUf = $this->resolveCertidaoUf($consultations);
                    if ($resolvedUf !== null) {
                        $sourceForExecution['body_overrides'] = array_replace(
                            (array) ($sourceForExecution['body_overrides'] ?? []),
                            ['uf' => $resolvedUf]
                        );
                    }
                }

                $result = $this->executeSource($sourceForExecution, $documentDigits);
                $consultation = ApiBrasilConsultation::query()->create([
                    'order_id' => $order?->id,
                    'lead_id' => $order?->lead_id,
                    'user_id' => $order?->user_id,
                    'admin_user_id' => $admin->id,
                    'analyst_user_id' => $report->analyst_user_id,
                    'consultation_key' => $result['consultation_key'] ?? $source['consultation_key'],
                    'consultation_title' => $result['consultation_title'] ?? $source['consultation_key'],
                    'consultation_category' => $result['consultation_category'] ?? null,
                    'document_type' => $result['document_type'] ?? $report->document_type,
                    'document_number' => $result['document'] ?? $documentDigits,
                    'status' => $result['status'] ?? 'error',
                    'provider' => (string) ($result['provider'] ?? $source['provider']),
                    'endpoint' => $result['endpoint'] ?? null,
                    'http_status' => $result['http_status'] ?? null,
                    'request_payload' => $result['request_payload'] ?? null,
                    'response_payload' => $result['response_payload'] ?? null,
                    'error_message' => $result['error_message'] ?? null,
                    'notes' => $notes ?: $report->title,
                ]);

                $report->items()->create([
                    'apibrasil_consultation_id' => $consultation->id,
                    'provider' => (string) ($result['provider'] ?? $source['provider']),
                    'source_key' => $result['consultation_key'] ?? $source['consultation_key'],
                    'source_title' => $result['consultation_title'] ?? $source['consultation_key'],
                    'source_category' => $result['consultation_category'] ?? null,
                    'status' => $result['status'] ?? 'error',
                    'http_status' => $result['http_status'] ?? null,
                    'request_payload' => $result['request_payload'] ?? null,
                    'response_payload' => $result['response_payload'] ?? null,
                    'error_message' => $result['error_message'] ?? null,
                ]);

                $consultations->push($consultation);
            }

            $successCount = (int) $consultations->where('status', 'success')->count();
            $failureCount = (int) $consultations->count() - $successCount;

            $report->update([
                'status' => $successCount === 0
                    ? 'error'
                    : ($failureCount > 0 ? 'partial' : 'success'),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'normalized_payload' => $this->normalizePayload($report, $consultations),
                'generated_at' => now(),
            ]);

            $this->syncResolvedSubjectData($report->fresh(['order.user', 'order.lead', 'user', 'lead']), $consultations);

            return $report->fresh(['order', 'lead', 'user', 'admin', 'analyst', 'items.consultation']);
        });
    }

    public function consultationsFor(ResearchReport $report): Collection
    {
        $consultationIds = $report->items()
            ->orderBy('id')
            ->pluck('apibrasil_consultation_id')
            ->filter()
            ->values();

        if ($consultationIds->isEmpty()) {
            return collect();
        }

        $consultations = ApiBrasilConsultation::query()
            ->with(['order', 'user', 'analyst', 'admin'])
            ->whereIn('id', $consultationIds)
            ->get()
            ->keyBy('id');

        return $consultationIds
            ->map(fn ($id) => $consultations->get($id))
            ->filter()
            ->values();
    }

    private function executeSource(array $source, string $documentDigits): array
    {
        $definition = $this->consultationDefinition((string) $source['consultation_key']);

        try {
            return $this->providerManager->consult($source, $documentDigits);
        } catch (\Throwable $exception) {
            return [
                'document' => $documentDigits,
                'document_type' => $this->documentTypeFromDigits($documentDigits),
                'status' => 'error',
                'provider' => (string) ($source['provider'] ?? 'apibrasil'),
                'provider_label' => (string) ($source['provider_label'] ?? 'Fonte de pesquisa'),
                'provider_driver' => (string) ($source['provider_driver'] ?? 'apibrasil'),
                'endpoint' => null,
                'http_status' => null,
                'request_payload' => [
                    'document' => $documentDigits,
                    'provider' => $source['provider'] ?? null,
                    'source_key' => $source['consultation_key'] ?? null,
                ],
                'response_payload' => null,
                'error_message' => $exception->getMessage(),
                'consultation_key' => $source['consultation_key'] ?? null,
                'consultation_title' => (string) ($definition['title'] ?? ($source['consultation_key'] ?? 'fonte')),
                'consultation_category' => (string) ($definition['category'] ?? 'geral'),
            ];
        }
    }

    private function resolveSourceDefinition(array|string $source): array
    {
        if (is_string($source)) {
            return [
                'provider' => 'apibrasil',
                'provider_label' => 'API Brasil',
                'provider_driver' => 'apibrasil',
                'consultation_key' => $source,
            ];
        }

        $providerKey = (string) ($source['provider'] ?? 'apibrasil');
        $providerConfig = config("apibrasil_catalog.providers.{$providerKey}");

        if (! is_array($providerConfig)) {
            throw new RuntimeException("Provedor de pesquisa não encontrado: {$providerKey}");
        }

        return [
            'provider' => $providerKey,
            'provider_label' => (string) ($providerConfig['label'] ?? $providerKey),
            'provider_driver' => (string) ($providerConfig['driver'] ?? 'apibrasil'),
            'consultation_key' => (string) ($source['consultation_key'] ?? ''),
        ];
    }

    private function normalizePayload(ResearchReport $report, Collection $consultations): array
    {
        $successfulConsultations = $consultations->where('status', 'success')->values();

        if ($report->report_type === 'pf' && $successfulConsultations->isNotEmpty()) {
            $payload = $this->pfResearchReportService->build($this->contextOrder($report), $successfulConsultations);
            $payload['meta']['generated_at'] = $report->freshTimestamp()->toIso8601String();

            return $payload;
        }

        if ($report->report_type === 'pj' && $consultations->isNotEmpty()) {
            $payload = $this->pjResearchReportService->build($this->contextOrder($report), $consultations);
            $payload['meta']['generated_at'] = $report->freshTimestamp()->toIso8601String();

            return $payload;
        }

        return [
            'meta' => [
                'title' => $report->title,
                'generated_at' => $report->freshTimestamp()->toIso8601String(),
                'document' => $report->document_number,
                'document_type' => strtoupper($report->document_type),
                'order_protocol' => $report->order?->protocolo ?: null,
            ],
            'summary' => [
                'source_count' => (int) $report->source_count,
                'success_count' => (int) $consultations->where('status', 'success')->count(),
                'failure_count' => (int) $consultations->where('status', '!=', 'success')->count(),
            ],
            'sources' => $consultations->map(fn (ApiBrasilConsultation $consultation) => [
                'key' => $consultation->consultation_key,
                'title' => $consultation->consultation_title,
                'status' => $consultation->status,
                'http_status' => $consultation->http_status,
            ])->all(),
        ];
    }

    private function contextOrder(ResearchReport $report): Order
    {
        if ($report->order) {
            return $report->order->loadMissing(['lead', 'user']);
        }

        $order = new Order([
            'protocolo' => 'REL-'.$report->id,
        ]);
        $order->id = $report->id;

        $order->setRelation('lead', $report->lead);
        $order->setRelation('user', $report->user);

        return $order;
    }

    private function resolveAnalystId(?Order $order): ?int
    {
        if (! $order) {
            return null;
        }

        $order->loadMissing(['lead', 'user']);

        $candidateId = (int) ($order->lead?->referred_by_user_id ?: $order->user?->referred_by_user_id ?: 0);

        return $candidateId > 0 ? $candidateId : null;
    }

    private function bundle(string $reportType): array
    {
        $bundle = config("apibrasil_catalog.bundles.{$reportType}");

        if (! is_array($bundle)) {
            throw new RuntimeException('Pacote de pesquisa não encontrado.');
        }

        return $bundle;
    }

    private function consultationDefinition(string $sourceKey): array
    {
        $definition = config("apibrasil_catalog.consultations.{$sourceKey}");

        return is_array($definition) ? $definition : [];
    }

    private function documentTypeFromDigits(string $documentDigits): string
    {
        return strlen($documentDigits) === 14 ? 'cnpj' : 'cpf';
    }

    private function syncResolvedSubjectData(ResearchReport $report, Collection $consultations): void
    {
        $resolvedName = $this->resolveSubjectName($report, $consultations);
        $resolvedEmail = $this->resolveSubjectEmail($report);

        if ($resolvedName === null && $resolvedEmail === null) {
            return;
        }

        $lead = $report->lead ?: $report->order?->lead;
        $user = $report->user ?: $report->order?->user;

        if ($lead) {
            $leadUpdates = [];

            if ($resolvedName !== null && $this->shouldOverwriteName($lead->nome)) {
                $leadUpdates['nome'] = $resolvedName;
            }

            if ($resolvedEmail !== null && blank($lead->email)) {
                $leadUpdates['email'] = $resolvedEmail;
            }

            if ($leadUpdates !== []) {
                $lead->update($leadUpdates);
            }
        }

        if ($user) {
            $userUpdates = [];

            if ($resolvedName !== null && $this->shouldOverwriteName($user->name)) {
                $userUpdates['name'] = $resolvedName;
            }

            if ($resolvedEmail !== null && method_exists($user, 'hasProvisionalEmail') && $user->hasProvisionalEmail()) {
                $userUpdates['email'] = $resolvedEmail;
            }

            if ($userUpdates !== []) {
                $user->update($userUpdates);
            }
        }
    }

    private function resolveSubjectName(ResearchReport $report, Collection $consultations): ?string
    {
        $normalizedPayload = (array) $report->normalized_payload;
        $documentType = $report->document_type;

        $candidates = [];

        if ($documentType === 'cpf') {
            $candidates[] = trim((string) data_get($normalizedPayload, 'person.name'));
        } else {
            $candidates[] = trim((string) data_get($normalizedPayload, 'company.razao_social'));
            $candidates[] = trim((string) data_get($normalizedPayload, 'company.nome_fantasia'));
            $candidates[] = trim((string) data_get($normalizedPayload, 'person.name'));
        }

        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation || ! is_array($consultation->response_payload)) {
                continue;
            }

            $responsePayload = $consultation->response_payload;
            $candidates[] = $this->firstStringFromPayload($responsePayload, [
                'nome',
                'nome_completo',
                'nm_completo',
                'razao_social',
                'nome_fantasia',
                'empresa',
            ]);
        }

        foreach ($candidates as $candidate) {
            $name = trim((string) $candidate);

            if ($this->isValidResolvedName($name)) {
                return Str::title(mb_strtolower($name));
            }
        }

        return null;
    }

    private function resolveSubjectEmail(ResearchReport $report): ?string
    {
        $email = mb_strtolower(trim((string) data_get((array) $report->normalized_payload, 'contacts.main_email')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function shouldOverwriteName(?string $currentName): bool
    {
        $currentName = trim((string) $currentName);

        return $currentName === '' || $currentName === 'Cliente Regulariza';
    }

    private function isValidResolvedName(string $name): bool
    {
        if ($name === '' || $name === 'Cliente Regulariza') {
            return false;
        }

        return mb_strlen($name) >= 4;
    }

    private function firstStringFromPayload(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            if (is_string($key) && in_array($key, $keys, true) && is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_array($value)) {
                $nested = $this->firstStringFromPayload($value, $keys);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function resolveCertidaoUf(Collection $consultations): ?string
    {
        $paths = [
            'data.resultado.dados_cadastrais.enderecos.estado',
            'data.resultado.dados_cadastrais.historico_endereco.enderecos.0.estado',
            'data.resultado.dados_contato.logradouros.0.logradouro_uf',
            'data.resultado.dados_contato.logradouros.0.logradouro_uf_iso',
            'data.dados_cadastrais.enderecos.estado',
            'data.dados.enderecos.0.estado',
            'response.dados.dados_receita_federal.uf',
            'data.dados_receita_federal.uf',
            'response.response.dados.dados_receita_federal.uf',
            'response.data.retorno.uf',
            'data.retorno.uf',
        ];

        foreach ($consultations as $consultation) {
            if (! $consultation instanceof ApiBrasilConsultation || ! is_array($consultation->response_payload)) {
                continue;
            }

            foreach ($paths as $path) {
                $candidate = data_get($consultation->response_payload, $path);
                $uf = $this->normalizeUf($candidate);
                if ($uf !== null) {
                    return $uf;
                }
            }

            $uf = $this->findUfRecursively($consultation->response_payload);
            if ($uf !== null) {
                return $uf;
            }
        }

        return null;
    }

    private function normalizeUf(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $candidate = mb_strtoupper(trim((string) $value));
        if (preg_match('/^[A-Z]{2}$/', $candidate) === 1) {
            return $candidate;
        }

        if (preg_match('/^BR\-([A-Z]{2})$/', $candidate, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function findUfRecursively(mixed $node): ?string
    {
        if (! is_array($node)) {
            return null;
        }

        foreach ($node as $key => $value) {
            if (is_string($key)) {
                $keyLower = mb_strtolower($key);
                if (in_array($keyLower, ['uf', 'estado', 'logradouro_uf', 'logradouro_uf_iso'], true)) {
                    $uf = $this->normalizeUf($value);
                    if ($uf !== null) {
                        return $uf;
                    }
                }
            }

            if (is_array($value)) {
                $nested = $this->findUfRecursively($value);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
