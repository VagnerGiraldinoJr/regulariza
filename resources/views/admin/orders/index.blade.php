<x-layouts.app>
    @php
        $serviceBadgeMap = [
            'regularização' => 'bg-sky-100/85 text-sky-800 border-sky-300/70',
            'regularizacao' => 'bg-sky-100/85 text-sky-800 border-sky-300/70',
            'pesquisa' => 'bg-violet-100/85 text-violet-800 border-violet-300/70',
            'contrato' => 'bg-amber-100/85 text-amber-800 border-amber-300/70',
        ];
        $statusMap = [
            'pendente' => ['label' => 'Pendente', 'class' => 'bg-amber-100/85 text-amber-900 border-amber-300/80'],
            'em_andamento' => ['label' => 'Em andamento', 'class' => 'bg-sky-100/85 text-sky-900 border-sky-300/80'],
            'concluido' => ['label' => 'Concluido', 'class' => 'bg-emerald-100/85 text-emerald-900 border-emerald-300/80'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'bg-rose-100/85 text-rose-900 border-rose-300/80'],
        ];
        $paymentMap = [
            'aguardando' => ['label' => 'Aguardando', 'class' => 'bg-amber-100/85 text-amber-900 border-amber-300/80'],
            'pago' => ['label' => 'Pago', 'class' => 'bg-emerald-100/85 text-emerald-900 border-emerald-300/80'],
            'falhou' => ['label' => 'Falhou', 'class' => 'bg-rose-100/85 text-rose-900 border-rose-300/80'],
            'reembolsado' => ['label' => 'Reembolsado', 'class' => 'bg-slate-100/85 text-slate-800 border-slate-300/80'],
        ];
    @endphp

    <div class="space-y-5">
        <section class="hero-card p-5 lg:p-6">
            <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <p class="hero-card__eyebrow">Operacao comercial</p>
                    <h1 class="hero-card__title">Painel de Pedidos</h1>
                    <p class="hero-card__lead">Leitura rapida da base de pedidos para equipe administrativa e SAC, com recorte financeiro e carga operacional real da fila.</p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="summary-pill">Valor total filtrado: R$ {{ number_format($stats['total_value'], 2, ',', '.') }}</span>
                        <span class="summary-pill">Valor pago filtrado: R$ {{ number_format($stats['paid_value'], 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="hero-stat">
                        <p class="hero-stat__label">Pedidos listados</p>
                        <p class="hero-stat__value">{{ $stats['total'] }}</p>
                        <p class="hero-stat__meta">Resultado atual conforme os filtros aplicados.</p>
                    </article>
                    <article class="hero-stat">
                        <p class="hero-stat__label">Em andamento</p>
                        <p class="hero-stat__value">{{ $stats['em_andamento'] }}</p>
                        <p class="hero-stat__meta">Pedidos ativos na esteira operacional.</p>
                    </article>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @error('order_delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-7">
            <article class="insight-tile insight-tile--cyan">
                <p class="insight-tile__label">Pedidos</p>
                <p class="insight-tile__value">{{ $stats['total'] }}</p>
                <p class="insight-tile__meta">Total de pedidos no recorte atual.</p>
            </article>
            <article class="insight-tile insight-tile--emerald">
                <p class="insight-tile__label">Pagos</p>
                <p class="insight-tile__value">{{ $stats['pagos'] }}</p>
                <p class="insight-tile__meta">Pedidos com pagamento confirmado.</p>
            </article>
            <article class="insight-tile insight-tile--amber">
                <p class="insight-tile__label">Pendentes</p>
                <p class="insight-tile__value">{{ $stats['pendentes'] }}</p>
                <p class="insight-tile__meta">Pedidos ainda sem progresso inicial.</p>
            </article>
            @if (auth()->user()?->role === 'admin')
                <article class="insight-tile insight-tile--amber">
                    <p class="insight-tile__label">Comissoes</p>
                    <p class="insight-tile__value">{{ $stats['commissions_pending'] }}</p>
                    <p class="insight-tile__meta">Pendentes ou liberadas aguardando fechamento.</p>
                </article>
                <article class="insight-tile insight-tile--rose">
                    <p class="insight-tile__label">SAC aberto</p>
                    <p class="insight-tile__value">{{ $stats['sac_open'] }}</p>
                    <p class="insight-tile__meta">Tickets ativos exigindo acompanhamento.</p>
                </article>
                <article class="insight-tile insight-tile--slate">
                    <p class="insight-tile__label">Leads sem dono</p>
                    <p class="insight-tile__value">{{ $stats['leads_unassigned'] }}</p>
                    <p class="insight-tile__meta">Oportunidades sem vendedor ou analista definido.</p>
                </article>
                <article class="insight-tile insight-tile--emerald">
                    <p class="insight-tile__label">WhatsApp pendente</p>
                    <p class="insight-tile__value">{{ $stats['messages_pending'] }}</p>
                    <p class="insight-tile__meta">Mensagens aguardando disparo ou reprocesso.</p>
                </article>
            @endif
        </section>

        <section class="panel-card overflow-hidden">
            <div class="space-y-4 border-b border-slate-300/50 bg-white/15 px-4 py-4">
                <div>
                    <p class="section-kicker">Filtros</p>
                    <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Base operacional de pedidos</h2>
                </div>

                <form method="GET" class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                    <select name="status" class="rounded-lg border border-slate-300/80 bg-white/70 px-3 py-2 text-sm">
                        <option value="">Todos os status</option>
                        @foreach (['pendente', 'em_andamento', 'concluido', 'cancelado'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>

                    <select name="pagamento_status" class="rounded-lg border border-slate-300/80 bg-white/70 px-3 py-2 text-sm">
                        <option value="">Todos os pagamentos</option>
                        @foreach (['aguardando', 'pago', 'falhou', 'reembolsado'] as $pagamentoStatus)
                            <option value="{{ $pagamentoStatus }}" @selected(($filters['pagamento_status'] ?? '') === $pagamentoStatus)>{{ $pagamentoStatus }}</option>
                        @endforeach
                    </select>

                    <div class="flex gap-2">
                        <button class="btn-primary text-sm">Filtrar</button>
                        <a href="{{ route('admin.orders.index') }}" class="btn-dark text-sm">Limpar</a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Servico</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Pagamento</th>
                            @if (auth()->user()?->role === 'admin')
                                <th class="px-4 py-3 text-right">Acoes</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $serviceName = (string) ($order->service?->nome ?? 'Nao definido');
                                $serviceBadge = 'bg-slate-100/85 text-slate-800 border-slate-300/80';
                                foreach ($serviceBadgeMap as $needle => $badgeClass) {
                                    if (str_contains(mb_strtolower($serviceName), $needle)) {
                                        $serviceBadge = $badgeClass;
                                        break;
                                    }
                                }
                                $statusInfo = $statusMap[$order->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $order->status)), 'class' => 'bg-slate-100/85 text-slate-800 border-slate-300/80'];
                                $paymentInfo = $paymentMap[$order->pagamento_status] ?? ['label' => ucfirst((string) $order->pagamento_status), 'class' => 'bg-slate-100/85 text-slate-800 border-slate-300/80'];
                            @endphp
                            <tr class="border-t border-slate-200/60">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->user?->name }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $serviceBadge }}">{{ $serviceName }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-slate-800">
                                    R$ {{ number_format((float) $order->valor, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentInfo['class'] }}">{{ $paymentInfo['label'] }}</span>
                                </td>
                                @if (auth()->user()?->role === 'admin')
                                    <td class="px-4 py-3 text-right">
                                        @if ($order->pagamento_status !== 'pago')
                                            <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Excluir o pedido {{ $order->protocolo }}? Esta acao remove apenas pedidos nao pagos.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-danger text-xs" type="submit">Excluir</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Pedido pago</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->role === 'admin' ? 7 : 6 }}" class="px-4 py-6 text-center text-slate-500">Sem pedidos para exibir.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">
                {{ $orders->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
