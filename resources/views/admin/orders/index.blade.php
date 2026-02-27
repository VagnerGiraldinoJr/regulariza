<x-layouts.app>
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

        <section class="panel-card overflow-hidden">
            <div class="space-y-3 border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pedidos</h2>
                <form method="GET" class="grid gap-2 sm:grid-cols-3">
                    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Todos os status</option>
                        @foreach (['pendente', 'em_andamento', 'concluido', 'cancelado'] as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>

                    <select name="pagamento_status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
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
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
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
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->user?->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->service?->nome }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->status }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->pagamento_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem pedidos para exibir.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $orders->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
