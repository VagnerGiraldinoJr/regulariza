<x-layouts.app>
    @php
        $viewMode = request()->query('view', 'tabela');
        $statusMap = [
            'aguardando_aceite' => ['label' => 'Aguardando aceite', 'class' => 'badge-warning'],
            'aguardando_entrada' => ['label' => 'Aguardando entrada', 'class' => 'badge-warning'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
            'ativo' => ['label' => 'Ativo', 'class' => 'badge-success'],
            'concluido' => ['label' => 'Concluído', 'class' => 'badge-info'],
        ];

        $kanbanColumns = [
            'aguardando_aceite' => 'Aguardando aceite',
            'aguardando_entrada' => 'Aguardando entrada',
            'ativo' => 'Ativo',
            'concluido' => 'Concluído',
            'cancelado' => 'Cancelado',
        ];

        $contractsCollection = collect($contracts->items());
    @endphp

    <div class="space-y-5">
        <section class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="panel-title">Acompanhamento de Contratos</h1>
                <p class="panel-subtitle mt-1">Visão operacional da carteira com status em tempo real.</p>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-300/50 bg-white/45 p-1 backdrop-blur">
                <a href="{{ route('analyst.contracts', ['view' => 'tabela']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold {{ $viewMode === 'tabela' ? 'bg-cyan-500 text-white' : 'text-slate-700 hover:bg-white/70' }}">Tabela</a>
                <a href="{{ route('analyst.contracts', ['view' => 'kanban']) }}" class="rounded-lg px-3 py-1.5 text-xs font-bold {{ $viewMode === 'kanban' ? 'bg-cyan-500 text-white' : 'text-slate-700 hover:bg-white/70' }}">Kanban</a>
            </div>
        </section>

        @if ($viewMode === 'kanban')
            <section class="grid gap-3 xl:grid-cols-3 2xl:grid-cols-6">
                @foreach ($kanbanColumns as $statusKey => $columnLabel)
                    @php
                        $bucket = $contractsCollection->filter(fn ($contract) => $contract->status === $statusKey);
                    @endphp
                    <article class="panel-card p-3">
                        <header class="mb-3 flex items-center justify-between">
                            <h2 class="text-xs font-black uppercase tracking-[0.1em] text-slate-700">{{ $columnLabel }}</h2>
                            <span class="badge badge-neutral">{{ $bucket->count() }}</span>
                        </header>
                        <div class="space-y-2">
                            @forelse ($bucket as $contract)
                                <div class="rounded-xl border border-slate-300/50 bg-white/45 p-3 backdrop-blur-sm">
                                    @php $acceptUrl = $contract->acceptanceUrl(); @endphp
                                    <p class="text-sm font-bold text-slate-800">#{{ $contract->id }} • {{ $contract->user?->name }}</p>
                                    <p class="mt-1 text-xs text-slate-600">{{ $contract->order?->protocolo ?? 'Sem protocolo' }}</p>
                                    <p class="mt-2 text-xs font-semibold text-slate-700">Honorários: R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}</p>
                                    <p class="mt-2">
                                        @php $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'badge-neutral']; @endphp
                                        <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                    </p>
                                    @if($acceptUrl)
                                        <div class="mt-2">
                                            <a href="{{ $acceptUrl }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-cyan-800 hover:text-cyan-950">abrir link de aceite</a>
                                        </div>
                                    @endif
                                    @if($contract->accepted_at && $contract->acceptance_token)
                                        <div class="mt-1">
                                            <a href="{{ route('contracts.accept.pdf', $contract->acceptance_token) }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">baixar PDF final</a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">Sem contratos nesta etapa.</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="panel-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                            <tr>
                                <th class="px-4 py-3">Contrato</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Honorários</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Parcelas</th>
                                <th class="px-4 py-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($contracts as $contract)
                            @php
                                $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'badge-neutral'];
                                $acceptUrl = $contract->acceptanceUrl();
                            @endphp
                            <tr class="border-t border-slate-200/60 align-top">
                                <td class="px-4 py-3">#{{ $contract->id }}<br><span class="text-xs text-slate-500">{{ $contract->order?->protocolo }}</span></td>
                                <td class="px-4 py-3">{{ $contract->user?->name }}</td>
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
                                                $installmentLabel = ucfirst(str_replace('_', ' ', (string) $installment->status));
                                            @endphp
                                            <div class="rounded border border-slate-200/70 bg-white/40 px-2 py-1">
                                                <span class="font-semibold text-slate-700">{{ $installment->label }}</span>
                                                <span class="ml-1 badge {{ $installmentClass }}">{{ $installmentLabel }}</span>
                                                @if($installment->payment_link_url)
                                                    <a class="ml-2 text-cyan-800 font-semibold" href="{{ $installment->payment_link_url }}" target="_blank" rel="noopener noreferrer">link</a>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-2 text-xs">
                                        @if($acceptUrl)
                                            <a href="{{ $acceptUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-md bg-cyan-700 px-2.5 py-1.5 font-semibold text-white hover:bg-cyan-800">
                                                Link de aceite
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
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem contratos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $contracts->appends(['view' => $viewMode])->links() }}</div>
            </section>
        @endif
    </div>
</x-layouts.app>
