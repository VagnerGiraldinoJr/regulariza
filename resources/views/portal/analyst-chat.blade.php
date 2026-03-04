<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Mensageria com Analista</h1>
            <p class="panel-subtitle mt-1">{{ $analyst ? 'Seu analista: '.$analyst->name : 'Você ainda não possui analista vinculado.' }}</p>
        </section>

        @if ($analyst)
            <section class="panel-card p-4">
                <form method="POST" action="{{ route('portal.analyst-chat.open') }}" class="grid gap-3 sm:grid-cols-2">
                    @csrf
                    <select name="order_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Sem vínculo com pedido</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}">{{ $order->protocolo }}</option>
                        @endforeach
                    </select>
                    <input name="mensagem" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Mensagem para o analista" required>
                    <button class="btn-primary sm:col-span-2">Iniciar conversa</button>
                </form>
            </section>
        @endif

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Conversas abertas</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($tickets as $ticket)
                    <a href="{{ route('portal.tickets.show', $ticket->id) }}" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-800">{{ $ticket->protocolo }} - {{ $ticket->assunto }}</p>
                        <p class="text-xs text-slate-500">Status: {{ $ticket->status }}</p>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-slate-500">Sem conversas com analista.</div>
                @endforelse
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $tickets->links() }}</div>
        </section>
    </div>
</x-layouts.app>
