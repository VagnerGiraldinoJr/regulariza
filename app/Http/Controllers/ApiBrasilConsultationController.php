<?php

namespace App\Http\Controllers;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\User;
use App\Services\ApiBrasilService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ApiBrasilConsultationController extends Controller
{
    public function index(Request $request): View
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

        return view('admin.management.apibrasil-consultations', [
            'consultations' => $consultations,
            'paidOrders' => $paidOrders,
            'analysts' => $analysts,
            'status' => $status,
            'apibrasilConfigured' => $this->isConfigured(),
            'catalog' => $catalog,
            'categories' => $categories,
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
}
