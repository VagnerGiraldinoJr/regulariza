<x-layouts.app>
    @php
        $totalBase = max(1, $stats['clientes']);
        $contractsRate = min(100, (int) round(($stats['contratos'] / $totalBase) * 100));
        $paidRate = min(100, (int) round(($stats['pagos'] / max(1, $stats['contratos'])) * 100));
        $commissionTarget = 120000;
        $commissionRate = min(100, (int) round(($stats['comissoes_liberadas'] / $commissionTarget) * 100));
    @endphp

    <div class="space-y-6">
        <section class="premium-hero panel-card p-6 lg:p-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="premium-kicker">OPERAÇÃO DO ANALISTA</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-white lg:text-4xl">Dashboard Executivo</h1>
                    <p class="mt-2 text-sm text-cyan-100/90">Visão diária da carteira, contratos, pagamentos e comissões com foco em fechamento.</p>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full border border-cyan-200/30 bg-cyan-50/10 px-3 py-1 font-semibold text-cyan-100">Código: {{ $referralCode }}</span>
                        <a class="rounded-full border border-cyan-200/30 bg-white/10 px-3 py-1 font-semibold text-white hover:bg-white/20" href="{{ $referralLink }}" target="_blank" rel="noopener">Abrir link comercial</a>
                        <a class="rounded-full border border-emerald-200/40 bg-emerald-400/15 px-3 py-1 font-semibold text-emerald-50 hover:bg-emerald-400/25" href="{{ $referralWhatsappLink }}" target="_blank" rel="noopener">Compartilhar via WhatsApp</a>
                    </div>
                </div>

                <div class="w-full max-w-md rounded-2xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-100/90">Indicadores de conversão</p>
                    <div class="mt-3 space-y-3">
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-cyan-50">
                                <span>Clientes x Contratos</span>
                                <span>{{ $contractsRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-white/20"><div class="h-full rounded-full bg-cyan-300" style="width: {{ $contractsRate }}%"></div></div>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-cyan-50">
                                <span>Contratos x Pagos</span>
                                <span>{{ $paidRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-white/20"><div class="h-full rounded-full bg-emerald-300" style="width: {{ $paidRate }}%"></div></div>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs text-cyan-50">
                                <span>Meta de comissão (mês)</span>
                                <span>{{ $commissionRate }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-white/20"><div class="h-full rounded-full bg-amber-300" style="width: {{ $commissionRate }}%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="premium-metric premium-metric-cyan">
                <div class="premium-metric-icon">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/><path d="M11 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/></svg>
                </div>
                <p class="premium-metric-label">Clientes da carteira</p>
                <h2>{{ number_format($stats['clientes'], 0, ',', '.') }}</h2>
                <p class="premium-metric-foot">Base ativa do analista</p>
            </article>

            <article class="premium-metric premium-metric-blue">
                <div class="premium-metric-icon">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10l3 3v13H4V4h3z"/><path d="M14 4v4h4"/></svg>
                </div>
                <p class="premium-metric-label">Contratos</p>
                <h2>{{ number_format($stats['contratos'], 0, ',', '.') }}</h2>
                <p class="premium-metric-foot">Entrada + 3 parcelas</p>
            </article>

            <article class="premium-metric premium-metric-emerald">
                <div class="premium-metric-icon">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5"/><path d="M20 19H4"/><path d="M7 15l3-3 3 2 4-5"/></svg>
                </div>
                <p class="premium-metric-label">Pagamentos confirmados</p>
                <h2>{{ number_format($stats['pagos'], 0, ',', '.') }}</h2>
                <p class="premium-metric-foot">Pedidos com pesquisa paga</p>
            </article>

            <article class="premium-metric premium-metric-gold">
                <div class="premium-metric-icon">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M9 10h6a2 2 0 1 1 0 4H9"/><path d="M12 8v8"/></svg>
                </div>
                <p class="premium-metric-label">Comissões liberadas</p>
                <h2>R$ {{ number_format($stats['comissoes_liberadas'], 2, ',', '.') }}</h2>
                <p class="premium-metric-foot">Disponível para recebimento</p>
            </article>
        </section>

        <section class="panel-card p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-[0.14em] text-slate-700">Operação diária</h2>
                    <p class="mt-1 text-sm text-slate-500">Atalhos para os módulos principais do analista.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a class="premium-action" href="{{ route('admin.orders.index') }}">
                    <span>Pedidos</span>
                    <small>Pesquisa paga e andamento</small>
                </a>
                <a class="premium-action" href="{{ route('analyst.contracts') }}">
                    <span>Contratos</span>
                    <small>Entrada + parcelas 30/60/90</small>
                </a>
                <a class="premium-action" href="{{ route('analyst.clients') }}">
                    <span>Carteira</span>
                    <small>Pipeline e contato rápido</small>
                </a>
                <a class="premium-action premium-action-dark" href="{{ route('admin.tickets.index') }}">
                    <span>SAC</span>
                    <small>Mensageria e atendimento</small>
                </a>
            </div>
        </section>

        <section class="panel-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-black uppercase tracking-[0.14em] text-slate-700">Timeline dos contratos</h2>
                <a href="{{ route('analyst.contracts') }}" class="text-xs font-semibold text-cyan-700 hover:text-cyan-900">Ver todos</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($timeline as $item)
                    <article class="premium-timeline-item">
                        <div class="premium-timeline-dot"></div>
                        <div>
                            <p class="text-xs text-slate-500">{{ $item['at']?->format('d/m/Y H:i') }} • {{ $item['protocol'] }}</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $item['title'] }}</p>
                            <p class="text-xs text-slate-600">Status: {{ $item['status'] }}</p>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-500">Sem eventos no momento.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
