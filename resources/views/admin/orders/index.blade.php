<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Painel de Pedidos</h1>
            <p class="panel-subtitle mt-1">Visão operacional para equipe administrativa e SAC.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="metric-card metric-soft-blue">
                <h3>{{ $orders->total() }}</h3>
                <p>Pedidos listados</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>{{ $orders->where('pagamento_status', 'pago')->count() }}</h3>
                <p>Pagos nesta página</p>
            </div>
            <div class="metric-card metric-soft-red">
                <h3>{{ $orders->where('status', 'pendente')->count() }}</h3>
                <p>Pendentes nesta página</p>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Pedidos</h2>
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
        </section>
    </div>
</x-layouts.app>
