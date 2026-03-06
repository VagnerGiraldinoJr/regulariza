<?php

namespace App\Http\Controllers;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\User;
use App\Services\ApiBrasilService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $catalog = $this->catalog();
        $categories = (array) config('apibrasil_catalog.categories', []);

        $consultations = ApiBrasilConsultation::query()
            ->with(['order', 'user', 'analyst', 'admin'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $paidOrders = Order::query()
            ->with(['lead', 'user'])
            ->where('pagamento_status', 'pago')
            ->latest('id')
            ->limit(80)
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

        return view('admin.management.apibrasil-consultations', [
            'consultations' => $consultations,
            'paidOrders' => $paidOrders,
            'analysts' => $analysts,
            'status' => $status,
            'apibrasilConfigured' => $this->isConfigured(),
            'catalog' => $catalog,
            'categories' => $categories,
            'balance' => $balance,
        ]);
    }

    public function store(Request $request, ApiBrasilService $apiBrasilService): RedirectResponse
    {
        $catalog = $this->catalog();
        $data = $request->validate([
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'consultation_key' => ['required', Rule::in(array_keys($catalog))],
            'document_number' => ['required', 'string', 'max:18'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $order = null;

        if (! empty($data['order_id'])) {
            $order = Order::query()->with(['lead', 'user'])->findOrFail((int) $data['order_id']);
        }

        try {
            $result = $apiBrasilService->consultarCatalogo(
                (string) $data['consultation_key'],
                (string) $data['document_number']
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['apibrasil' => $exception->getMessage()])->withInput();
        }

        $consultation = ApiBrasilConsultation::query()->create([
            'order_id' => $order?->id,
            'lead_id' => $order?->lead_id,
            'user_id' => $order?->user_id,
            'admin_user_id' => (int) $request->user()->id,
            'consultation_key' => $result['consultation_key'] ?? null,
            'consultation_title' => $result['consultation_title'] ?? null,
            'consultation_category' => $result['consultation_category'] ?? null,
            'document_type' => $result['document_type'],
            'document_number' => $result['document'],
            'status' => $result['status'],
            'provider' => 'apibrasil',
            'endpoint' => $result['endpoint'],
            'http_status' => $result['http_status'],
            'request_payload' => $result['request_payload'],
            'response_payload' => $result['response_payload'],
            'error_message' => $result['error_message'],
            'notes' => $data['notes'] ?: null,
        ]);

        if ($consultation->status === 'success') {
            return back()->with('success', 'Consulta realizada e salva com sucesso.');
        }

        return back()->withErrors([
            'apibrasil' => 'Consulta salva com falha na API Brasil: '.($consultation->error_message ?: 'erro não identificado.'),
        ]);
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
                'notes' => $data['notes'] ?: $consultation->notes,
            ]);

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
