<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSacTicketRequest;
use App\Models\SacMessage;
use App\Models\SacTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SacTicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SacTicket::class);

        $tickets = $request->user()
            ->sacTickets()
            ->with('order')
            ->latest()
            ->paginate(10);

        return view('portal.tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function store(StoreSacTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', SacTicket::class);
        $validated = $request->validated();

        $ticket = SacTicket::create([
            'order_id' => $validated['order_id'] ?? null,
            'user_id' => $request->user()->id,
            'assunto' => (string) $validated['assunto'],
            'prioridade' => (string) ($validated['prioridade'] ?? 'nova'),
            'status' => 'aberto',
        ]);

        if (! empty($validated['mensagem'])) {
            SacMessage::create([
                'sac_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'mensagem' => (string) $validated['mensagem'],
                'tipo' => 'texto',
            ]);
        }

        return redirect()
            ->route('portal.tickets.show', $ticket->id)
            ->with('success', 'Chamado aberto com sucesso.');
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('assign', SacTicket::class);

        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $abertos = (int) SacTicket::abertos()->count();
        $semAtendente = (int) SacTicket::semAtendente()->abertos()->count();
        $criticos = (int) SacTicket::query()
            ->semAtendente()
            ->abertos()
            ->where('prioridade', 'critica')
            ->count();
        $resolvidosMes = (int) SacTicket::query()
            ->whereIn('status', ['resolvido', 'fechado'])
            ->whereBetween('updated_at', [$inicioMes, $fimMes])
            ->count();

        $tickets = SacTicket::query()
            ->semAtendente()
            ->abertos()
            ->with(['user', 'order'])
            ->latest()
            ->paginate(20);

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'abertos' => $abertos,
            'semAtendente' => $semAtendente,
            'criticos' => $criticos,
            'resolvidosMes' => $resolvidosMes,
        ]);
    }

    public function assign(Request $request, SacTicket $ticket): RedirectResponse
    {
        $this->authorize('assign', SacTicket::class);

        $ticket->update([
            'atendente_id' => $request->user()->id,
            'status' => 'em_atendimento',
        ]);

        return back();
    }
}
