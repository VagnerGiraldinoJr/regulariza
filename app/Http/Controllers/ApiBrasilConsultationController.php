<?php

namespace App\Http\Controllers;

use App\Models\AdminActionLog;
use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\ResearchReport;
use App\Models\ResearchReportItem;
use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\ApiBrasilService;
use App\Services\PfResearchReportService;
use App\Services\PjResearchReportService;
use App\Services\ResearchReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ApiBrasilConsultationController extends Controller
{
    public function index(Request $request, ApiBrasilService $apiBrasilService): View
    {
        $status = (string) $request->query('status', '');
        $bundles = $this->bundles();

        $consultations = ApiBrasilConsultation::query()
            ->with(['order', 'user', 'analyst', 'admin'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $orderIds = $consultations->getCollection()
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->values();

        $consultationCountByOrder = ApiBrasilConsultation::query()
            ->selectRaw('order_id, COUNT(*) as total')
            ->whereIn('order_id', $orderIds)
            ->groupBy('order_id')
            ->pluck('total', 'order_id')
            ->map(fn ($total) => (int) $total)
            ->all();

        $paidOrders = Order::query()
            ->with(['lead', 'user'])
            ->where('pagamento_status', 'pago')
            ->latest('id')
            ->limit(80)
            ->get();

        $reports = ResearchReport::query()
            ->with(['order', 'user', 'admin'])
            ->latest('id')
            ->limit(12)
            ->get();

        $analysts = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $balance = [
            'status' => 'error',
            'balance' => null,
            'error_message' => null,
        ];

        $overview = [
            'total' => ApiBrasilConsultation::query()->count(),
            'success' => ApiBrasilConsultation::query()->where('status', 'success')->count(),
            'error' => ApiBrasilConsultation::query()->where('status', 'error')->count(),
            'forwarded' => ApiBrasilConsultation::query()->whereNotNull('analyst_user_id')->count(),
        ];

        if ($this->isConfigured()) {
            try {
                $balance = Cache::remember('apibrasil.balance.snapshot', now()->addSeconds(45), function () use ($apiBrasilService) {
                    return $apiBrasilService->consultarSaldo();
                });
            } catch (\Throwable $exception) {
                $balance['error_message'] = $exception->getMessage();
            }
        }

        if (! is_numeric($balance['balance'] ?? null)) {
            $fallbackBalance = $this->balanceFromConsultationHistory();
            if (is_numeric($fallbackBalance)) {
                $balance['balance'] = (float) $fallbackBalance;
            }
        }

        $recentAuditLogs = AdminActionLog::query()
            ->with('admin')
            ->where('action', 'consultation_deleted')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('admin.management.apibrasil-consultations', [
            'consultations' => $consultations,
            'paidOrders' => $paidOrders,
            'reports' => $reports,
            'analysts' => $analysts,
            'status' => $status,
            'apibrasilConfigured' => $this->isConfigured(),
            'bundles' => $bundles,
            'balance' => $balance,
            'overview' => $overview,
            'consultationCountByOrder' => $consultationCountByOrder,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    public function store(
        Request $request,
        ResearchReportService $researchReportService,
        ApiBrasilService $apiBrasilService
    ): RedirectResponse {
        $bundles = $this->bundles();
        $data = $request->validate([
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'report_type' => ['required', Rule::in(array_keys($bundles))],
            'document_number' => ['required', 'string', 'max:18'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $order = null;

        if (! empty($data['order_id'])) {
            $order = Order::query()->with(['lead', 'user'])->findOrFail((int) $data['order_id']);
        }

        $reportType = (string) $data['report_type'];
        $documentDigits = preg_replace('/\D+/', '', (string) $data['document_number']);
        $expectedDocumentType = (string) ($bundles[$reportType]['document_type'] ?? 'both');

        if (
            ($expectedDocumentType === 'cpf' && strlen($documentDigits) !== 11)
            || ($expectedDocumentType === 'cnpj' && strlen($documentDigits) !== 14)
        ) {
            return back()->withErrors([
                'document_number' => $expectedDocumentType === 'cpf'
                    ? 'Informe um CPF válido para o dossiê PF.'
                    : 'Informe um CNPJ válido para o dossiê PJ.',
            ])->withInput();
        }

        if ($this->isConfigured()) {
            try {
                $balance = $apiBrasilService->consultarSaldo();
                $balanceValue = $this->parseMoney($balance['balance'] ?? null);
                if ($balanceValue !== null && $balanceValue <= 0) {
                    return back()->withErrors([
                        'apibrasil' => 'Saldo insuficiente na API Brasil para executar a análise. Recarregue os créditos e tente novamente.',
                    ])->withInput();
                }
            } catch (\Throwable $exception) {
                Log::warning('Falha ao validar saldo da API Brasil antes da análise.', [
                    'error' => $exception->getMessage(),
                    'admin_user_id' => $request->user()?->id,
                    'report_type' => $reportType,
                    'document' => $documentDigits,
                ]);
            }
        }

        try {
            $report = $researchReportService->createFromBundle(
                order: $order,
                admin: $request->user(),
                reportType: $reportType,
                documentNumber: $documentDigits,
                notes: $data['notes'] ?? null
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'apibrasil' => $exception->getMessage(),
            ])->withInput();
        }

        $bundleTitle = (string) ($bundles[$reportType]['title'] ?? strtoupper($reportType));
        $successCount = (int) $report->success_count;
        $failureCount = (int) $report->failure_count;

        $message = "{$bundleTitle} executado com {$successCount} consulta(s) concluída(s)";
        if ($failureCount > 0) {
            $message .= " e {$failureCount} com falha";
        }
        $message .= ". Relatório #{$report->id} gerado.";

        return back()->with('success', $message);
    }

    public function forward(Request $request, ApiBrasilConsultation $consultation): RedirectResponse
    {
        $data = $request->validate([
            'analyst_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $analyst = User::query()->findOrFail((int) $data['analyst_user_id']);

        if (! in_array($analyst->role, ['analista', 'vendedor'], true)) {
            return back()->withErrors(['analyst_user_id' => 'Selecione um analista/vendedor válido.']);
        }

        try {
            $consultation->update([
                'analyst_user_id' => $analyst->id,
                'forwarded_at' => now(),
                'notes' => ($data['notes'] ?? null) ?: $consultation->notes,
            ]);

            ResearchReport::query()
                ->whereHas('items', fn ($query) => $query->where('apibrasil_consultation_id', $consultation->id))
                ->update(['analyst_user_id' => $analyst->id]);

            if ($consultation->lead) {
                $consultation->lead->update(['referred_by_user_id' => $analyst->id]);
            }

            if ($consultation->user) {
                $consultation->user->update(['referred_by_user_id' => $analyst->id]);
            }
        } catch (\Throwable $exception) {
            Log::error('Falha ao encaminhar consulta API Brasil para analista.', [
                'consultation_id' => $consultation->id,
                'analyst_user_id' => $analyst->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'apibrasil_forward' => 'Não foi possível encaminhar para o analista agora. Tente novamente em instantes.',
            ]);
        }

        return back()->with('success', 'Consulta encaminhada ao analista com sucesso.');
    }

    public function destroy(Request $request, ApiBrasilConsultation $consultation, AdminAuditService $adminAuditService): RedirectResponse
    {
        $linkedReports = ResearchReport::query()
            ->whereHas('items', fn ($query) => $query->where('apibrasil_consultation_id', $consultation->id))
            ->pluck('id')
            ->values()
            ->all();

        $context = [
            'consultation_key' => $consultation->consultation_key,
            'consultation_title' => $consultation->consultation_title,
            'document_type' => $consultation->document_type,
            'document_number' => $consultation->document_number,
            'status' => $consultation->status,
            'http_status' => $consultation->http_status,
            'order_id' => $consultation->order_id,
            'analyst_user_id' => $consultation->analyst_user_id,
            'linked_reports' => $linkedReports,
        ];

        $adminAuditService->record(
            $request->user(),
            'consultation_deleted',
            $consultation,
            'Consulta operacional da API Brasil removida manualmente pelo admin.',
            $context
        );

        $consultation->delete();

        return back()->with('success', 'Consulta removida com auditoria.');
    }

    public function cleanupPartial(Request $request, AdminAuditService $adminAuditService): RedirectResponse
    {
        $reportIds = collect();
        $consultationIds = collect();

        DB::transaction(function () use (&$reportIds, &$consultationIds) {
            $reportIds = ResearchReport::query()
                ->where('status', 'partial')
                ->pluck('id')
                ->values();

            $consultationIds = ResearchReportItem::query()
                ->whereIn('research_report_id', $reportIds)
                ->whereNotNull('apibrasil_consultation_id')
                ->pluck('apibrasil_consultation_id')
                ->unique()
                ->values();

            ApiBrasilConsultation::query()->whereIn('id', $consultationIds)->delete();
            ResearchReport::query()->whereIn('id', $reportIds)->delete();
        });

        $context = [
            'deleted_reports_partial' => $reportIds->count(),
            'deleted_consultations_linked' => $consultationIds->count(),
            'report_ids' => $reportIds->all(),
            'consultation_ids' => $consultationIds->all(),
        ];

        $adminAuditService->record(
            $request->user(),
            'consultation_cleanup_partial',
            null,
            'Limpeza em lote executada: relatórios com status partial e consultas vinculadas removidos.',
            $context
        );

        return back()->with(
            'success',
            "Limpeza concluída. Dossiês parciais removidos: {$context['deleted_reports_partial']}; consultas vinculadas removidas: {$context['deleted_consultations_linked']}."
        );
    }

    public function downloadPdf(ApiBrasilConsultation $consultation): Response
    {
        $filename = sprintf(
            'consulta-%s-%s.pdf',
            $consultation->consultation_key ?: 'apibrasil',
            Str::slug((string) $consultation->document_number)
        );

        $pdf = Pdf::loadView('admin.management.apibrasil-consultation-pdf', [
            'consultation' => $consultation->loadMissing(['order', 'user', 'analyst', 'admin']),
        ])->setPaper('a4');

        return $pdf->download($filename);
    }

    public function downloadOrderPdf(
        Order $order,
        PfResearchReportService $pfResearchReportService,
        PjResearchReportService $pjResearchReportService
    ): Response|RedirectResponse {
        $existingReport = ResearchReport::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        if ($existingReport) {
            return $this->downloadResearchReportPdf($existingReport, $pfResearchReportService, $pjResearchReportService);
        }

        $consultations = ApiBrasilConsultation::query()
            ->where('order_id', $order->id)
            ->where('status', 'success')
            ->with(['order', 'user', 'analyst', 'admin'])
            ->latest('id')
            ->get();

        if ($consultations->isEmpty()) {
            return back()->withErrors([
                'apibrasil_pdf' => 'Este pedido ainda não possui consultas de sucesso para gerar o dossiê.',
            ]);
        }

        $filename = sprintf('dossie-%s-apibrasil.pdf', Str::slug((string) ($order->protocolo ?: $order->id)));

        $order->loadMissing(['lead', 'user']);
        $documentDigits = preg_replace('/\D+/', '', (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: ''));

        if (strlen($documentDigits) === 11) {
            $report = $pfResearchReportService->build($order, $consultations);

            $pdf = Pdf::loadView('admin.management.apibrasil-order-dossier-pf-pdf', [
                'order' => $order,
                'consultations' => $consultations,
                'report' => $report,
            ])->setPaper('a4');
        } else {
            $report = $pjResearchReportService->build($order, $consultations);

            $pdf = Pdf::loadView('admin.management.apibrasil-order-dossier-pj-pdf', [
                'order' => $order,
                'consultations' => $consultations,
                'report' => $report,
            ])->setPaper('a4');
        }

        return $pdf->download($filename);
    }

    public function downloadResearchReportPdf(
        ResearchReport $report,
        PfResearchReportService $pfResearchReportService,
        PjResearchReportService $pjResearchReportService
    ): Response|RedirectResponse {
        $report->loadMissing([
            'order.lead',
            'order.user',
            'lead',
            'user',
            'admin',
            'analyst',
            'items.consultation.order',
            'items.consultation.user',
            'items.consultation.analyst',
            'items.consultation.admin',
        ]);
        $consultations = $report->items
            ->pluck('consultation')
            ->filter()
            ->values();

        if ($consultations->isEmpty()) {
            return back()->withErrors([
                'apibrasil_pdf' => 'Este relatório ainda não possui consultas suficientes para gerar o PDF.',
            ]);
        }

        $filename = sprintf(
            'dossie-%s-%s.pdf',
            $report->report_type,
            Str::slug((string) ($report->order?->protocolo ?: $report->document_number))
        );

        $documentDigits = preg_replace('/\D+/', '', (string) $report->document_number);
        $contextOrder = $report->order ?: $this->virtualOrderFromReport($report);

        if (strlen($documentDigits) === 11) {
            $payload = $this->hydratePfPayload(
                $report->normalized_payload,
                $pfResearchReportService->build($contextOrder, $consultations)
            );

            $pdf = Pdf::loadView('admin.management.apibrasil-order-dossier-pf-pdf', [
                'order' => $contextOrder,
                'consultations' => $consultations,
                'report' => $payload,
            ])->setPaper('a4');
        } else {
            $payload = $this->hydratePjPayload(
                $report->normalized_payload,
                $pjResearchReportService->build($contextOrder, $consultations)
            );

            $pdf = Pdf::loadView('admin.management.apibrasil-order-dossier-pj-pdf', [
                'order' => $contextOrder,
                'consultations' => $consultations,
                'report' => $payload,
                'researchReport' => $report,
            ])->setPaper('a4');
        }

        return $pdf->download($filename);
    }

    private function virtualOrderFromReport(ResearchReport $report): Order
    {
        $order = new Order([
            'protocolo' => 'REL-'.$report->id,
        ]);
        $order->id = $report->id;

        $order->setRelation('lead', $report->lead);
        $order->setRelation('user', $report->user);

        return $order;
    }

    private function hydratePfPayload(?array $storedPayload, array $fallbackPayload): array
    {
        $payload = is_array($storedPayload) && $storedPayload !== [] ? $storedPayload : $fallbackPayload;
        $generatedAt = data_get($payload, 'meta.generated_at');

        if (is_string($generatedAt) && $generatedAt !== '') {
            data_set($payload, 'meta.generated_at', Carbon::parse($generatedAt));
        } elseif (! $generatedAt instanceof Carbon) {
            data_set($payload, 'meta.generated_at', now());
        }

        return $payload;
    }

    private function hydratePjPayload(?array $storedPayload, array $fallbackPayload): array
    {
        $payload = is_array($storedPayload) && $storedPayload !== [] ? $storedPayload : $fallbackPayload;
        $generatedAt = data_get($payload, 'meta.generated_at');

        if (is_string($generatedAt) && $generatedAt !== '') {
            data_set($payload, 'meta.generated_at', Carbon::parse($generatedAt));
        } elseif (! $generatedAt instanceof Carbon) {
            data_set($payload, 'meta.generated_at', now());
        }

        return $payload;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.apibrasil.base_url'))
            && filled(config('services.apibrasil.token'))
            && ! empty($this->catalog());
    }

    private function catalog(): array
    {
        return (array) config('apibrasil_catalog.consultations', []);
    }

    private function bundles(): array
    {
        return (array) config('apibrasil_catalog.bundles', []);
    }

    private function balanceFromConsultationHistory(): ?float
    {
        $recentPayloads = ApiBrasilConsultation::query()
            ->latest('id')
            ->limit(30)
            ->pluck('response_payload');

        foreach ($recentPayloads as $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $value = $this->findBalanceValue($payload);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function findBalanceValue(array $payload): ?float
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $nested = $this->findBalanceValue($value);
                if ($nested !== null) {
                    return $nested;
                }
            }

            if (! is_string($key)) {
                continue;
            }

            $keyLower = mb_strtolower($key);
            if (! str_contains($keyLower, 'saldo') && ! str_contains($keyLower, 'balance')) {
                continue;
            }

            $parsed = $this->parseMoney($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseMoney(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
