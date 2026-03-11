<x-layouts.app>
    @php
        $statusMap = [
            'aguardando_aceite' => ['label' => 'Aguardando aceite', 'class' => 'badge-warning'],
            'aguardando_entrada' => ['label' => 'Aguardando entrada', 'class' => 'badge-warning'],
            'ativo' => ['label' => 'Ativo', 'class' => 'badge-success'],
            'concluido' => ['label' => 'Concluído', 'class' => 'badge-info'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
    @endphp
    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Meus Contratos</h1>
            <p class="panel-subtitle mt-1">Aceite o contrato quando estiver pendente. Após a entrada ser confirmada, as parcelas restantes ficam disponíveis aqui no portal com botão de pagamento.</p>
        </section>
        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr><th class="px-4 py-3">Contrato</th><th class="px-4 py-3">Analista</th><th class="px-4 py-3">Valor</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Parcelas</th><th class="px-4 py-3">Ações</th></tr>
                    </thead>
                    <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'badge-neutral'];
                            $acceptUrl = $contract->acceptanceUrl();
                        @endphp
                        <tr class="border-t border-slate-200/60 align-top">
                            <td class="px-4 py-3">#{{ $contract->id }}<br><span class="text-xs text-slate-500">{{ $contract->order?->protocolo }}</span></td>
                            <td class="px-4 py-3">{{ $contract->analyst?->name ?: '-' }}</td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                @if($contract->accepted_at)
                                    <div class="mt-1 text-xs text-slate-500">Aceito em {{ $contract->accepted_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
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
                            <td class="px-4 py-3">
                                <div class="space-y-2 text-xs">
                                    @if($acceptUrl && ! $contract->accepted_at)
                                        <a href="{{ $acceptUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-md bg-cyan-700 px-2.5 py-1.5 font-semibold text-white hover:bg-cyan-800">
                                            Aceitar contrato
                                        </a>
                                    @endif
                                    @if($contract->document_path && $contract->acceptance_token)
                                        <a href="{{ route('contracts.accept.document', $contract->acceptance_token) }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-md bg-slate-700 px-2.5 py-1.5 font-semibold text-white hover:bg-slate-800">
                                            Contrato-base
                                        </a>
                                    @endif
                                    @if($contract->accepted_at && $contract->acceptance_token)
                                        <a href="{{ route('contracts.accept.pdf', $contract->acceptance_token) }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-md bg-emerald-700 px-2.5 py-1.5 font-semibold text-white hover:bg-emerald-800">
                                            PDF final
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem contratos ativos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $contracts->links() }}</div>
        </section>
    </div>
</x-layouts.app>
