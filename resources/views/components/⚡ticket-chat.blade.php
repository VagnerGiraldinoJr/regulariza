<?php

use App\Models\SacMessage;
use App\Models\SacTicket;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public ?int $ticketId = null;
    public string $mensagem = '';

    public function mount(int $ticketId): void
    {
        $ticket = SacTicket::findOrFail($ticketId);
        Gate::authorize('view', $ticket);

        $this->ticketId = $ticketId;
    }

    public function enviarMensagem(): void
    {
        $data = validator(
            ['mensagem' => $this->mensagem],
            ['mensagem' => ['required', 'string', 'max:5000']]
        )->validate();

        $ticket = SacTicket::findOrFail($this->ticketId);
        Gate::authorize('view', $ticket);

        if (! auth()->check()) {
            abort(403);
        }

        SacMessage::create([
            'sac_ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'mensagem' => trim($data['mensagem']),
            'tipo' => 'texto',
        ]);

        $this->mensagem = '';
    }

    public function getTicketProperty(): SacTicket
    {
        $ticket = SacTicket::with(['messages.user', 'user', 'atendente', 'order'])
            ->findOrFail($this->ticketId);

        Gate::authorize('view', $ticket);

        return $ticket;
    }
};
?>

<div wire:poll.3s class="space-y-3">
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
        <p class="text-sm font-semibold text-slate-800">{{ $this->ticket->protocolo }} - {{ $this->ticket->assunto }}</p>
        <p class="text-xs text-slate-500">Status: {{ $this->ticket->status }}</p>
    </div>

    <div class="max-h-96 space-y-2 overflow-y-auto rounded-lg border border-slate-200 bg-white p-3">
        @foreach($this->ticket->messages as $message)
            <div class="max-w-[85%] rounded-lg border px-3 py-2 text-sm {{ $message->user_id === auth()->id() ? 'ml-auto border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }}">
                <p class="font-semibold text-slate-700">{{ $message->user->name }}</p>
                <p class="text-slate-700">{{ $message->mensagem }}</p>
                <p class="mt-1 text-[11px] text-slate-500">{{ $message->created_at?->format('d/m/Y H:i') }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex gap-2">
        <input wire:model="mensagem" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="Digite sua mensagem..." />
        <button wire:click="enviarMensagem" class="btn-primary">Enviar</button>
    </div>
</div>
