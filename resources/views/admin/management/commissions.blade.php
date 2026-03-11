<x-layouts.app>
    @php
        $statusMap = [
            'pending' => ['label' => 'Em retenção', 'class' => 'badge-warning'],
            'available' => ['label' => 'Disponível', 'class' => 'badge-info'],
            'paid' => ['label' => 'Pago', 'class' => 'badge-success'],
            'canceled' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
    @endphp

    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Controle de Pagamentos de Comissões</h1>
            <p class="panel-subtitle mt-1">Pendente/liberado: R$ {{ number_format($totals['pending'], 2, ',', '.') }} | Pago: R$ {{ number_format($totals['paid'], 2, ',', '.') }}. Nesta tela o admin registra a liberação e o pagamento manual.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @error('commission_action')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <section class="panel-card p-4">
            <form method="GET" class="flex gap-2">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach (['pending','available','paid','canceled'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $statusMap[$s]['label'] }}</option>
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
                            <th class="px-4 py-3">Vendedor</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Origem</th>
                            <th class="px-4 py-3">Base</th>
                            <th class="px-4 py-3">Comissão</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Disponível</th>
                            <th class="px-4 py-3">Pago em</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $c)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $c->seller?->name }}</td>
                                <td class="px-4 py-3">{{ $c->order?->user?->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $c->source_type === 'contract_installment' ? 'badge-purple' : 'badge-info' }}">
                                        {{ $c->source_type === 'contract_installment' ? 'Parcela de contrato' : 'Pesquisa paga' }}
                                    </span>
                                    <div class="mt-1 text-xs text-slate-500">Pedido {{ $c->order?->protocolo ?: '#'.$c->order_id }}</div>
                                </td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $c->base_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 font-semibold">R$ {{ number_format((float) $c->commission_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @php $statusInfo = $statusMap[$c->status] ?? ['label' => ucfirst((string) $c->status), 'class' => 'badge-neutral']; @endphp
                                    <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $c->available_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $c->paid_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if($c->status === 'pending')
                                            <form method="POST" action="{{ route('admin.management.commissions.release', $c) }}">
                                                @csrf
                                                <button class="btn-dark text-xs" type="submit">Liberar</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.management.commissions.mark-paid', $c) }}">
                                                @csrf
                                                <button class="btn-primary text-xs" type="submit">Pagar manual</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.management.commissions.cancel', $c) }}">
                                                @csrf
                                                <button class="btn-danger text-xs" type="submit">Cancelar</button>
                                            </form>
                                        @elseif($c->status === 'available')
                                            <form method="POST" action="{{ route('admin.management.commissions.mark-paid', $c) }}">
                                                @csrf
                                                <button class="btn-primary text-xs" type="submit">Pagar manual</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.management.commissions.cancel', $c) }}">
                                                @csrf
                                                <button class="btn-danger text-xs" type="submit">Cancelar</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Sem ação disponível</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-slate-500">Sem comissões.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $commissions->links() }}</div>
        </section>
    </div>
</x-layouts.app>
