<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Dashboard Financeiro</h1>
            <p class="panel-subtitle mt-1">Indicadores de receita e performance de pagamentos.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card metric-soft-blue">
                <h3>R$ {{ number_format($receitaTotal, 2, ',', '.') }}</h3>
                <p>Receita total</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>R$ {{ number_format($receitaMes, 2, ',', '.') }}</h3>
                <p>Receita do mês</p>
            </div>
            <div class="metric-card metric-soft-amber">
                <h3>R$ {{ number_format($ticketMedio, 2, ',', '.') }}</h3>
                <p>Ticket médio</p>
            </div>
            <div class="metric-card metric-soft-red">
                <h3>{{ number_format($taxaPendencia, 1, ',', '.') }}%</h3>
                <p>Taxa de pendência</p>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Receita por serviço</h2>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($receitaPorServico as $item)
                        <div class="px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $item->service?->nome ?? 'Serviço removido' }}</p>
                                <p class="text-sm font-bold text-slate-700">R$ {{ number_format((float) $item->total, 2, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">Ainda não há pagamentos confirmados.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Receita últimos 6 meses</h2>
                </div>

                <div class="space-y-3 px-4 py-4">
                    @php
                        $max = max(1, $seriesMensal->max('total'));
                    @endphp

                    @foreach($seriesMensal as $mes)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ $mes['label'] }}</span>
                                <span>R$ {{ number_format((float) $mes['total'], 2, ',', '.') }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-500" style="width: {{ min(100, (($mes['total'] / $max) * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
