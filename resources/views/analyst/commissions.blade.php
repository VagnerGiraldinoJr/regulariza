<x-layouts.app>
    @php
        $tipoMap = [
            'research' => 'Pesquisa',
            'contract_installment' => 'Parcela de contrato',
        ];

        $statusMap = [
            'pending' => ['label' => 'Pendente', 'class' => 'badge-neutral'],
            'available' => ['label' => 'Liberado', 'class' => 'badge-success'],
            'paid' => ['label' => 'Pago', 'class' => 'badge-info'],
            'canceled' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
    @endphp

    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Financeiro de Comissões</h1>
            <p class="panel-subtitle mt-1">Pendente/liberado: R$ {{ number_format($totals['pending'], 2, ',', '.') }} | Pago: R$ {{ number_format($totals['paid'], 2, ',', '.') }}</p>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50/70 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Valor</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Disponível</th>
                            <th class="px-4 py-3">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($commissions as $c)
                        @php
                            $tipo = $tipoMap[$c->source_type] ?? 'Comissão';
                            $status = $statusMap[$c->status] ?? ['label' => ucfirst((string) $c->status), 'class' => 'badge-neutral'];
                            $liberada = $c->status === 'available' && (! $c->available_at || $c->available_at->lte(now()));
                        @endphp
                        <tr class="border-t border-slate-100/70">
                            <td class="px-4 py-3">{{ $c->order?->user?->name ?: '-' }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $c->source_type === 'contract_installment' ? 'badge-purple' : 'badge-info' }}">{{ $tipo }}</span></td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $c->commission_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $status['class'] }}">{{ $status['label'] }}</span></td>
                            <td class="px-4 py-3">{{ $c->available_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($c->payout_requested_at)
                                    <span class="badge badge-warning">Saque solicitado</span>
                                @elseif($liberada)
                                    <form method="POST" action="{{ route('analyst.commissions.request-payout', $c) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary text-xs">Solicitar saque PIX</button>
                                    </form>
                                @else
                                    <span class="badge badge-neutral">Indisponível</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem comissões.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200/70 px-4 py-3">{{ $commissions->links() }}</div>
        </section>
    </div>
</x-layouts.app>
