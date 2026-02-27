<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Portal do Cliente</h1>
            <p class="panel-subtitle mt-1">Acompanhe pedidos, pagamentos e andamento da regularização.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="metric-card metric-soft-blue">
                <h3>{{ $stats['total'] }}</h3>
                <p>Total de pedidos</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>{{ $stats['pagos'] }}</h3>
                <p>Pagos</p>
            </div>
            <div class="metric-card metric-soft-amber">
                <h3>{{ $stats['em_andamento'] }}</h3>
                <p>Em andamento</p>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Meus pedidos</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Serviço</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->service?->nome }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->status }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->pagamento_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Nenhum pedido encontrado.</td>
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
