<x-layouts.app>
    @php
        $paymentMap = [
            'aguardando' => ['label' => 'Aguardando', 'class' => 'badge-warning'],
            'pago' => ['label' => 'Pago', 'class' => 'badge-success'],
            'falhou' => ['label' => 'Falhou', 'class' => 'badge-danger'],
            'reembolsado' => ['label' => 'Reembolsado', 'class' => 'badge-neutral'],
        ];
        $statusMap = [
            'pendente' => ['label' => 'Pendente', 'class' => 'badge-warning'],
            'em_andamento' => ['label' => 'Em andamento', 'class' => 'badge-info'],
            'concluido' => ['label' => 'Concluído', 'class' => 'badge-success'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
    @endphp

    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Controle de Pagamentos de Contratos</h1>
        </section>

        <section class="panel-card p-4">
            <form class="grid gap-2 sm:grid-cols-3" method="GET">
                <select name="pagamento_status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos pagamentos</option>
                    @foreach (['aguardando', 'pago', 'falhou', 'reembolsado'] as $s)
                        <option value="{{ $s }}" @selected($filters['pagamento'] === $s)>{{ $paymentMap[$s]['label'] }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos contratos</option>
                    @foreach (['pendente', 'em_andamento', 'concluido', 'cancelado'] as $s)
                        <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ $statusMap[$s]['label'] }}</option>
                    @endforeach
                </select>
                <button class="btn-primary">Filtrar</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Protocolo</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Valor</th><th class="px-4 py-3">Pagamento</th><th class="px-4 py-3">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 font-semibold">{{ $order->protocolo }}</td>
                            <td class="px-4 py-3">{{ $order->user?->name }}</td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $order->valor, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                @php $payment = $paymentMap[$order->pagamento_status] ?? ['label' => ucfirst((string) $order->pagamento_status), 'class' => 'badge-neutral']; @endphp
                                <span class="badge {{ $payment['class'] }}">{{ $payment['label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php $statusInfo = $statusMap[$order->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $order->status)), 'class' => 'badge-neutral']; @endphp
                                <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem registros.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $orders->links() }}</div>
        </section>
    </div>
</x-layouts.app>
