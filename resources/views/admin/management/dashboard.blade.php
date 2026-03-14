<x-layouts.app>
    <div class="space-y-5">
        <section class="hero-card p-5 lg:p-6">
            <div class="grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
                <div>
                    <p class="hero-card__eyebrow">Operacao central</p>
                    <h1 class="hero-card__title">Painel do Administrador</h1>
                    <p class="hero-card__lead">Leitura unica da esteira comercial, contratos, atendimento e liquidez do mes. Todos os indicadores abaixo sao calculados a partir da operacao atual.</p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="summary-pill">Receita do mes: R$ {{ number_format($stats['monthly_revenue'], 2, ',', '.') }}</span>
                        <span class="summary-pill">Parcelas em aberto: R$ {{ number_format($stats['open_installments_total'], 2, ',', '.') }}</span>
                        <span class="summary-pill">Saques PIX pendentes: {{ $stats['payout_requests_open'] }}</span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="hero-stat">
                        <p class="hero-stat__label">Pedidos pagos</p>
                        <p class="hero-stat__value">{{ $stats['orders_paid'] }}</p>
                        <p class="hero-stat__meta">Base atual de pedidos com confirmacao financeira.</p>
                    </article>
                    <article class="hero-stat">
                        <p class="hero-stat__label">Contratos ativos</p>
                        <p class="hero-stat__value">{{ $stats['contracts_active'] }}</p>
                        <p class="hero-stat__meta">Inclui aceite pendente, aguardando entrada e contratos em curso.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            <article class="insight-tile insight-tile--cyan">
                <p class="insight-tile__label">Base total</p>
                <p class="insight-tile__value">{{ $stats['orders_total'] }}</p>
                <p class="insight-tile__meta">Pedidos e contratos alimentando a operacao.</p>
            </article>
            <article class="insight-tile insight-tile--emerald">
                <p class="insight-tile__label">Pagamentos confirmados</p>
                <p class="insight-tile__value">{{ $stats['orders_paid'] }}</p>
                <p class="insight-tile__meta">Pedidos com baixa financeira efetivada.</p>
            </article>
            <article class="insight-tile insight-tile--amber">
                <p class="insight-tile__label">Aceite pendente</p>
                <p class="insight-tile__value">{{ $stats['contracts_acceptance_pending'] }}</p>
                <p class="insight-tile__meta">Contratos aguardando retorno do cliente.</p>
            </article>
            <article class="insight-tile insight-tile--rose">
                <p class="insight-tile__label">SAC aberto</p>
                <p class="insight-tile__value">{{ $stats['sac_open'] }}</p>
                <p class="insight-tile__meta">Tickets exigindo acompanhamento da equipe.</p>
            </article>
            <article class="insight-tile insight-tile--slate">
                <p class="insight-tile__label">Leads sem dono</p>
                <p class="insight-tile__value">{{ $stats['leads_unassigned'] }}</p>
                <p class="insight-tile__meta">Oportunidades sem vendedor ou analista atrelado.</p>
            </article>
            <article class="insight-tile insight-tile--amber">
                <p class="insight-tile__label">WhatsApp pendente</p>
                <p class="insight-tile__value">{{ $stats['messages_pending'] }}</p>
                <p class="insight-tile__meta">Eventos ainda aguardando disparo ou reprocesso.</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
            <div class="panel-card p-4">
                <p class="section-kicker">Navegacao rapida</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <a class="action-card" href="{{ route('admin.contracts.index') }}">
                        <p class="action-card__eyebrow">Contratos</p>
                        <p class="action-card__title">Operar a esteira contratual</p>
                        <p class="action-card__meta">Criacao, aceite e cobrancas da carteira.</p>
                    </a>
                    <a class="action-card" href="{{ route('admin.orders.index') }}">
                        <p class="action-card__eyebrow">Pedidos</p>
                        <p class="action-card__title">Revisar base comercial</p>
                        <p class="action-card__meta">Status, pagamentos e exclusoes controladas.</p>
                    </a>
                    <a class="action-card" href="{{ route('admin.finance.dashboard') }}">
                        <p class="action-card__eyebrow">Financeiro</p>
                        <p class="action-card__title">Abrir leitura analitica</p>
                        <p class="action-card__meta">Gráficos, metas, risco e caixa.</p>
                    </a>
                    <a class="action-card" href="{{ route('admin.management.contract-payments') }}">
                        <p class="action-card__eyebrow">Pagamentos</p>
                        <p class="action-card__title">Controlar contratos e boletos</p>
                        <p class="action-card__meta">Filtrar status de pagamento e execucao.</p>
                    </a>
                    <a class="action-card" href="{{ route('admin.management.apibrasil-consultations') }}">
                        <p class="action-card__eyebrow">API Brasil</p>
                        <p class="action-card__title">Gerenciar analises</p>
                        <p class="action-card__meta">Execucao, PDF e encaminhamento para analistas.</p>
                    </a>
                    <a class="action-card" href="{{ route('admin.tickets.index') }}">
                        <p class="action-card__eyebrow">Atendimento</p>
                        <p class="action-card__title">Priorizar fila de SAC</p>
                        <p class="action-card__meta">Distribuicao de tickets e tempo de resposta.</p>
                    </a>
                </div>
            </div>

            <div class="panel-card p-4">
                <p class="section-kicker">Pipeline contratual</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <article class="stack-card">
                        <p class="stack-card__label">Aguardando aceite</p>
                        <p class="stack-card__title">{{ $pipeline['aguardando_aceite'] }} contrato(s)</p>
                        <p class="stack-card__meta">Clientes que ja receberam o link e ainda nao finalizaram o aceite.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Aguardando entrada</p>
                        <p class="stack-card__title">{{ $pipeline['aguardando_entrada'] }} contrato(s)</p>
                        <p class="stack-card__meta">Contratos aceitos aguardando evolucao financeira.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Ativos</p>
                        <p class="stack-card__title">{{ $pipeline['ativo'] }} contrato(s)</p>
                        <p class="stack-card__meta">Carteira em execucao operacional neste momento.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Concluidos</p>
                        <p class="stack-card__title">{{ $pipeline['concluido'] }} contrato(s)</p>
                        <p class="stack-card__meta">Historico concluido e finalizado na plataforma.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-300/50 bg-white/15 px-4 py-3">
                    <p class="section-kicker">Pedidos recentes</p>
                    <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Ultima movimentacao comercial</h2>
                </div>
                <div class="divide-y divide-slate-200/60">
                    @forelse ($recentOrders as $order)
                        <article class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $order->protocolo }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $order->user?->name ?: 'Cliente removido' }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $order->service?->nome ?: 'Servico removido' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-slate-900">R$ {{ number_format((float) $order->valor, 2, ',', '.') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-4 py-8 text-sm text-slate-500">Ainda nao ha pedidos para exibir.</div>
                    @endforelse
                </div>
            </div>

            <div class="panel-card overflow-hidden">
                <div class="border-b border-slate-300/50 bg-white/15 px-4 py-3">
                    <p class="section-kicker">Fila critica</p>
                    <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Tickets que merecem atencao</h2>
                </div>
                <div class="divide-y divide-slate-200/60">
                    @forelse ($priorityTickets as $ticket)
                        <article class="px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-900">{{ $ticket->protocolo }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $ticket->user?->name ?: 'Cliente removido' }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $ticket->assunto }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex rounded-full border border-slate-300/70 bg-white/70 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-700">
                                        {{ ucfirst((string) $ticket->prioridade) }}
                                    </span>
                                    <p class="mt-2 text-xs text-slate-500">{{ $ticket->order?->protocolo ?: 'Sem pedido' }}</p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-4 py-8 text-sm text-slate-500">Nenhum ticket aberto na fila critica agora.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-300/50 bg-white/15 px-4 py-3">
                <p class="section-kicker">Servicos lideres</p>
                <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Receita consolidada por servico pago</h2>
            </div>
            <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-5">
                @forelse ($servicePerformance as $service)
                    <article class="stack-card">
                        <p class="stack-card__label">Servico</p>
                        <p class="stack-card__title">{{ $service->service?->nome ?: 'Servico removido' }}</p>
                        <p class="stack-card__meta">{{ (int) $service->total_count }} pagamento(s) | R$ {{ number_format((float) $service->total_value, 2, ',', '.') }}</p>
                    </article>
                @empty
                    <div class="px-2 py-6 text-sm text-slate-500 md:col-span-2 xl:col-span-5">Ainda nao ha servicos pagos suficientes para consolidacao.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
