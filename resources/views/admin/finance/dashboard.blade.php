<x-layouts.app>
    @php
        $series = collect($seriesMensal)->values();
        $max = max(1, (float) $series->max('total'));
    @endphp

    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Dashboard Financeiro</h1>
            <p class="panel-subtitle mt-1">Separação entre o que foi gerado, o que realmente entrou e o que ainda está em aberto.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card metric-soft-blue">
                <h3>R$ {{ number_format($receitaTotal, 2, ',', '.') }}</h3>
                <p>Recebido consolidado</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>R$ {{ number_format($receitaMes, 2, ',', '.') }}</h3>
                <p>Recebido no mês</p>
            </div>
            <div class="metric-card metric-soft-amber">
                <h3>R$ {{ number_format($ticketMedio, 2, ',', '.') }}</h3>
                <p>Ticket médio da pesquisa</p>
            </div>
            <div class="metric-card metric-soft-red">
                <h3>{{ number_format($taxaRecebimento, 1, ',', '.') }}%</h3>
                <p>Taxa de recebimento</p>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-300/55 bg-white/15 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pesquisas / pedidos</h2>
                    <p class="mt-1 text-xs text-slate-500">Mostra o valor gerado no funil e o que já foi pago.</p>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/70 bg-white/60 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Gerado</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">R$ {{ number_format($ordersSummary['generated_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $ordersSummary['generated_count'] }} pedido(s)</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Pago</p>
                        <p class="mt-2 text-2xl font-black text-emerald-900">R$ {{ number_format($ordersSummary['paid_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-emerald-700">{{ $ordersSummary['paid_count'] }} pedido(s)</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200/70 bg-amber-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-700">Em aberto</p>
                        <p class="mt-2 text-2xl font-black text-amber-900">R$ {{ number_format($ordersSummary['open_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-amber-700">Pedidos sem baixa financeira</p>
                    </div>
                </div>
            </div>

            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-300/55 bg-white/15 px-4 py-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Parcelas de contratos</h2>
                    <p class="mt-1 text-xs text-slate-500">Controle do que foi emitido após o aceite e do que efetivamente entrou.</p>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/70 bg-white/60 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Gerado</p>
                        <p class="mt-2 text-2xl font-black text-slate-900">R$ {{ number_format($installmentsSummary['generated_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $installmentsSummary['generated_count'] }} cobrança(s)</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Pago</p>
                        <p class="mt-2 text-2xl font-black text-emerald-900">R$ {{ number_format($installmentsSummary['paid_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-emerald-700">{{ $installmentsSummary['paid_count'] }} parcela(s)</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200/70 bg-amber-50/70 p-4">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-amber-700">Em aberto</p>
                        <p class="mt-2 text-2xl font-black text-amber-900">R$ {{ number_format($installmentsSummary['open_total'], 2, ',', '.') }}</p>
                        <p class="mt-1 text-xs text-amber-700">Parcelas aguardando baixa</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gráfico de recebimento dos últimos 6 meses</h2>
            <p class="panel-subtitle mt-1">Soma de pesquisas pagas e parcelas efetivamente baixadas.</p>

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
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pesquisas pagas por serviço</h2>
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
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Recebimento últimos 6 meses</h2>
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
