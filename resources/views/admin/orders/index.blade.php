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
            'concluido' => ['label' => 'Concluído', 'class' => 'bg-emerald-100/85 text-emerald-900 border-emerald-300/80'],
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
        <section>
            <h1 class="panel-title">Painel de Pedidos</h1>
            <p class="panel-subtitle mt-1">Visão operacional para equipe administrativa e SAC.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="metric-card metric-soft-blue">
                <h3>{{ $stats['total'] }}</h3>
                <p>Pedidos listados</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>{{ $stats['pagos'] }}</h3>
                <p>Pagos</p>
            </div>
            <div class="metric-card metric-soft-red">
                <h3>{{ $stats['pendentes'] }}</h3>
                <p>Pendentes</p>
            </div>
        </section>

        @if (auth()->user()?->role === 'admin')
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="metric-card metric-soft-amber"><h3>{{ $stats['commissions_pending'] }}</h3><p>Comissões pendentes/liberadas</p></div>
                <div class="metric-card metric-soft-red"><h3>{{ $stats['sac_open'] }}</h3><p>Tickets SAC em aberto</p></div>
                <div class="metric-card metric-soft-blue"><h3>{{ $stats['leads_unassigned'] }}</h3><p>Leads sem vendedor</p></div>
                <div class="metric-card metric-soft-green"><h3>{{ $stats['messages_pending'] }}</h3><p>WhatsApp pendentes</p></div>
            </section>

            <section class="panel-card p-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                <a class="btn-primary text-center" href="{{ route('admin.contracts.index') }}">Módulo de contratos</a>
                <a class="btn-primary text-center" href="{{ route('admin.management.contract-payments') }}">Controle de pagamentos</a>
                <a class="btn-primary text-center" href="{{ route('admin.management.commissions') }}">Controle de comissões</a>
                <a class="btn-primary text-center" href="{{ route('admin.management.payout-requests') }}">Solicitações PIX</a>
                <a class="btn-dark text-center" href="{{ route('admin.finance.dashboard') }}">Dashboard financeiro</a>
                <a class="btn-dark text-center" href="{{ route('admin.management.integrations') }}">Integrações</a>
                <a class="btn-dark text-center" href="{{ route('admin.management.messages') }}">Mensagens enviadas</a>
                <a class="btn-dark text-center" href="{{ route('admin.management.users') }}">Usuários</a>
                <a class="btn-dark text-center" href="{{ route('admin.management.vendors') }}">Cadastrar vendedor</a>
            </section>
        @endif

        <section class="panel-card overflow-hidden">
            <div class="space-y-3 border-b border-slate-300/50 bg-white/15 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pedidos</h2>
                <form method="GET" class="grid gap-2 sm:grid-cols-3">
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
                            <th class="px-4 py-3">Serviço</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $serviceName = (string) ($order->service?->nome ?? 'Não definido');
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
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $paymentInfo['class'] }}">{{ $paymentInfo['label'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem pedidos para exibir.</td>
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
