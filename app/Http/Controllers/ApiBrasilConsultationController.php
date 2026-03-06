<?php

namespace App\Http\Controllers;

use App\Models\ApiBrasilConsultation;
use App\Models\Order;
use App\Models\User;
use App\Services\ApiBrasilService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ApiBrasilConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');

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
        ]);
    }

    public function store(Request $request, ApiBrasilService $apiBrasilService): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
            'document_number' => ['required', 'string', 'max:18'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $order = null;

        if (! empty($data['order_id'])) {
            $order = Order::query()->with(['lead', 'user'])->findOrFail((int) $data['order_id']);
        }

        try {
            $result = $apiBrasilService->consultarDocumento((string) $data['document_number']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['apibrasil' => $exception->getMessage()])->withInput();
        }

        $consultation = ApiBrasilConsultation::query()->create([
            'order_id' => $order?->id,
            'lead_id' => $order?->lead_id,
            'user_id' => $order?->user_id,
            'admin_user_id' => (int) $request->user()->id,
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

    private function isConfigured(): bool
    {
        return filled(config('services.apibrasil.base_url'))
            && filled(config('services.apibrasil.token'))
            && filled(config('services.apibrasil.cpf_path'))
            && filled(config('services.apibrasil.cnpj_path'));
    }
}
