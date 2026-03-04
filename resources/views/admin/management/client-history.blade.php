<x-layouts.app>
    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Histórico de Movimentação do Cliente</h1>
            <p class="panel-subtitle mt-1">{{ $client->name }} | {{ $client->email }}</p>
        </section>

        <section class="panel-card p-4">
            <div class="space-y-3">
                @forelse($events as $event)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $event['type'] }} • {{ $event['at']?->format('d/m/Y H:i') }}</p>
                        <p class="text-sm font-semibold text-slate-800">{{ $event['description'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sem movimentações.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
