<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Timeline do Contrato</h1>
            <p class="panel-subtitle mt-1">Tempo médio de resposta: {{ $responseMinutes !== null ? $responseMinutes.' min' : 'sem amostra' }}</p>
        </section>

        <section class="panel-card p-4">
            <div class="space-y-3">
                @forelse($events as $event)
                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                        <p class="text-xs text-slate-500">{{ $event['at']?->format('d/m/Y H:i') }}</p>
                        <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                        <p class="text-sm text-slate-600">{{ $event['description'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Sem eventos.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
