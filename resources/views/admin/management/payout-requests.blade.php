<x-layouts.app>
    @php
        $statusMap = [
            'pending' => ['label' => 'Pendente', 'class' => 'badge-neutral'],
            'available' => ['label' => 'Aguardando pagamento', 'class' => 'badge-warning'],
            'paid' => ['label' => 'Pago', 'class' => 'badge-success'],
            'canceled' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];

        $tipoMap = [
            'research' => 'Pesquisa',
            'contract_installment' => 'Parcela de contrato',
        ];
    @endphp

    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Solicitações de Saque PIX</h1>
            <p class="panel-subtitle mt-1">Acompanhamento das requisições enviadas por analistas/vendedores e processamento automático via Asaas.</p>
        </section>

        <section class="panel-card p-4">
            <form method="GET" class="flex flex-wrap gap-2">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos os status</option>
                    <option value="pending" @selected($status === 'pending')>Pendente</option>
                    <option value="available" @selected($status === 'available')>Aguardando pagamento</option>
                    <option value="paid" @selected($status === 'paid')>Pago</option>
                    <option value="canceled" @selected($status === 'canceled')>Cancelado</option>
                </select>

                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <input type="checkbox" name="pendentes" value="1" @checked($apenasPendentes)>
                    Apenas aguardando pagamento
                </label>

                <button class="btn-primary" type="submit">Filtrar</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Solicitado em</th>
                            <th class="px-4 py-3">Vendedor</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Transferência Asaas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                            @php
                                $statusBadge = $statusMap[$r->status] ?? ['label' => ucfirst((string) $r->status), 'class' => 'badge-neutral'];
                                $tipo = $tipoMap[$r->source_type] ?? 'Comissão';
                            @endphp
                            <tr class="border-t border-slate-100/70">
                                <td class="px-4 py-3">{{ $r->payout_requested_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $r->seller?->name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $r->order?->user?->name ?: '-' }}</td>
                                <td class="px-4 py-3"><span class="badge badge-purple">{{ $tipo }}</span></td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $r->commission_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3"><span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span></td>
                                <td class="px-4 py-3">{{ $r->asaas_transfer_id ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Nenhuma solicitação de saque encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200/70 px-4 py-3">{{ $requests->links() }}</div>
        </section>
    </div>
</x-layouts.app>
