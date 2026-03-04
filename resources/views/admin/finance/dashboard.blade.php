<x-layouts.app>
    @php
        $series = collect($seriesMensal)->values();
        $max = max(1, (float) $series->max('total'));
    @endphp

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

        <section class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gráfico de receita dos últimos 6 meses</h2>
            <p class="panel-subtitle mt-1">Evolução mensal com base em pagamentos confirmados.</p>

            <div class="mt-3 rounded-xl border border-slate-300/55 bg-white/45 p-4">
                <div class="grid h-56 grid-cols-6 gap-3">
                    @foreach($series as $mes)
                        @php
                            $percent = min(100, (((float) $mes['total'] / $max) * 100));
                        @endphp
                        <div class="flex h-full flex-col justify-end">
                            <div class="relative h-full rounded-t-lg border border-cyan-200/70 bg-cyan-50/35">
                                <div class="absolute bottom-0 left-0 right-0 rounded-t-md bg-gradient-to-t from-cyan-600 to-cyan-400" style="height: {{ max(3, $percent) }}%"></div>
                            </div>
                            <p class="mt-2 text-center text-[11px] font-semibold text-slate-600">{{ $mes['label'] }}</p>
                            <p class="text-center text-[11px] text-slate-500">R$ {{ number_format((float) $mes['total'], 2, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-300/55 bg-white/15 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Receita por serviço</h2>
                </div>

                <div class="divide-y divide-slate-200/60">
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
                <div class="border-b border-slate-300/55 bg-white/15 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Receita últimos 6 meses</h2>
                </div>

                <div class="space-y-3 px-4 py-4">
                    @foreach($series as $mes)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                <span>{{ $mes['label'] }}</span>
                                <span>R$ {{ number_format((float) $mes['total'], 2, ',', '.') }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100/80">
                                <div class="h-2 rounded-full bg-cyan-500" style="width: {{ min(100, (((float) $mes['total'] / $max) * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
