<?php

namespace App\Http\Controllers;

use App\Models\SacMessage;
use App\Models\SacTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientExperienceController extends Controller
{
    public function timeline(Request $request): View
    {
        $user = $request->user();

        $events = collect();

        foreach ($user->orders()->with('service')->latest()->get() as $order) {
            $events->push([
                'at' => $order->updated_at,
                'title' => "Pedido {$order->protocolo}",
                'description' => "{$order->service?->nome} | status {$order->status} | pagamento {$order->pagamento_status}",
            ]);
        }

        foreach ($user->sacTickets()->latest()->get() as $ticket) {
            $events->push([
                'at' => $ticket->updated_at,
                'title' => "Ticket {$ticket->protocolo}",
                'description' => "{$ticket->assunto} | status {$ticket->status}",
            ]);
        }

        foreach ($user->contracts()->with('installments')->latest()->get() as $contract) {
            $events->push([
                'at' => $contract->updated_at,
                'title' => "Contrato #{$contract->id}",
                'description' => "Status {$contract->status} | honorários R$ ".number_format((float) $contract->fee_amount, 2, ',', '.'),
            ]);

            foreach ($contract->installments as $installment) {
                $events->push([
                    'at' => $installment->paid_at ?: $installment->updated_at,
                    'title' => "Contrato #{$contract->id} - {$installment->label}",
                    'description' => "Parcela R$ ".number_format((float) $installment->amount, 2, ',', '.')." | {$installment->status}",
                ]);
            }
        }

        $events = $events->sortByDesc('at')->values();

        $responseMinutes = $this->averageResponseMinutes($user->id);

        return view('portal/timeline', [
            'events' => $events,
            'responseMinutes' => $responseMinutes,
        ]);
    }

    public function profile(Request $request): View
    {
        return view('portal/profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'whatsapp' => ['required', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = mb_strtolower(trim((string) $data['email']));
        $user->whatsapp = preg_replace('/\D+/', '', (string) $data['whatsapp']);
        $user->cpf_cnpj = $data['cpf_cnpj'] ? preg_replace('/\D+/', '', (string) $data['cpf_cnpj']) : null;

        if (! empty($data['password'])) {
            $user->password = Hash::make((string) $data['password']);
        }

        $user->save();

        return back()->with('success', 'Dados atualizados com sucesso.');
    }

    public function analystChat(Request $request): View
    {
        $user = $request->user();
        $analyst = $user->referredBy;

        $tickets = $user->sacTickets()
            ->when($analyst, fn ($q) => $q->where('atendente_id', $analyst->id))
            ->latest()
            ->paginate(10);

        return view('portal/analyst-chat', [
            'analyst' => $analyst,
            'tickets' => $tickets,
            'orders' => $user->orders()->latest()->limit(20)->get(['id', 'protocolo']),
        ]);
    }

    public function openAnalystChat(Request $request): RedirectResponse
    {
        $user = $request->user();
        $analyst = $user->referredBy;

        if (! $analyst) {
            return back()->withErrors(['analyst' => 'Você ainda não possui analista vinculado.']);
        }

        $data = $request->validate([
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where(fn ($q) => $q->where('user_id', $user->id))],
            'mensagem' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SacTicket::create([
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $user->id,
            'atendente_id' => $analyst->id,
            'assunto' => 'Conversa com Analista',
            'prioridade' => 'baixa',
            'status' => 'em_atendimento',
        ]);

        SacMessage::create([
            'sac_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'mensagem' => (string) $data['mensagem'],
            'tipo' => 'texto',
        ]);

        return redirect()->route('portal.tickets.show', $ticket->id);
    }

    private function averageResponseMinutes(int $userId): ?float
    {
        $tickets = SacTicket::query()->where('user_id', $userId)->get(['id', 'created_at']);

        if ($tickets->isEmpty()) {
            return null;
        }

        $samples = [];

        foreach ($tickets as $ticket) {
            $firstSupportMessage = SacMessage::query()
                ->where('sac_ticket_id', $ticket->id)
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['admin', 'atendente', 'analista', 'vendedor']))
                ->oldest('id')
                ->first();

            if (! $firstSupportMessage) {
                continue;
            }

            $samples[] = $ticket->created_at->diffInMinutes($firstSupportMessage->created_at);
        }

        if ($samples === []) {
            return null;
        }

        return round(array_sum($samples) / count($samples), 1);
    }
}
