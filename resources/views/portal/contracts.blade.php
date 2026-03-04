<x-layouts.app>
    @php
        $statusMap = [
            'rascunho' => ['label' => 'Rascunho', 'class' => 'badge-neutral'],
            'enviado' => ['label' => 'Enviado', 'class' => 'badge-info'],
            'aguardando_assinatura' => ['label' => 'Aguardando assinatura', 'class' => 'badge-warning'],
            'assinado' => ['label' => 'Assinado', 'class' => 'badge-success'],
            'ativo' => ['label' => 'Ativo', 'class' => 'badge-success'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
    @endphp
    <div class="space-y-4">
        <section><h1 class="panel-title">Meus Contratos</h1></section>
        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr><th class="px-4 py-3">Contrato</th><th class="px-4 py-3">Analista</th><th class="px-4 py-3">Valor</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Parcelas</th></tr>
                    </thead>
                    <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'badge-neutral'];
                        @endphp
                        <tr class="border-t border-slate-200/60 align-top">
                            <td class="px-4 py-3">#{{ $contract->id }}<br><span class="text-xs text-slate-500">{{ $contract->order?->protocolo }}</span></td>
                            <td class="px-4 py-3">{{ $contract->analyst?->name ?: '-' }}</td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 text-xs">
                                    @foreach($contract->installments->sortBy('installment_number') as $installment)
                                        @php
                                            $installmentClass = $installment->status === 'pago' ? 'badge-success' : ($installment->status === 'vencido' ? 'badge-danger' : 'badge-warning');
                                        @endphp
                                        <div class="rounded border border-slate-200/70 bg-white/40 px-2 py-1">
                                            {{ $installment->label }} - R$ {{ number_format((float) $installment->amount, 2, ',', '.') }} - <span class="badge {{ $installmentClass }}">{{ ucfirst(str_replace('_', ' ', (string) $installment->status)) }}</span>
                                            @if($installment->status !== 'pago' && $installment->payment_link_url)
                                                <a class="ml-1 text-cyan-800 font-semibold" href="{{ $installment->payment_link_url }}" target="_blank" rel="noopener noreferrer">pagar</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem contratos ativos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $contracts->links() }}</div>
        </section>
    </div>
</x-layouts.app>
