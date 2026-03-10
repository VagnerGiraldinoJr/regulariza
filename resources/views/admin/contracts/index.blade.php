<x-layouts.app>
    @php
        $statusMap = [
            'aguardando_aceite' => ['label' => 'Aguardando aceite', 'class' => 'bg-orange-100/85 text-orange-900 border-orange-300/80'],
            'aguardando_entrada' => ['label' => 'Aguardando entrada', 'class' => 'bg-amber-100/85 text-amber-900 border-amber-300/80'],
            'ativo' => ['label' => 'Ativo', 'class' => 'bg-emerald-100/85 text-emerald-900 border-emerald-300/80'],
            'concluido' => ['label' => 'Concluído', 'class' => 'bg-sky-100/85 text-sky-900 border-sky-300/80'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'bg-rose-100/85 text-rose-900 border-rose-300/80'],
        ];
    @endphp

    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Módulo de Contratos</h1>
            <p class="panel-subtitle mt-1">Contrato com link de aceite do cliente, PDF final de evidência e liberação de cobranças após o aceite.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/85 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Novo contrato</h2>
            @if($eligibleOrders->isEmpty())
                <p class="mt-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    Nenhum pedido pago disponível para gerar contrato neste momento.
                </p>
            @endif
            <form method="POST" action="{{ route('admin.contracts.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @csrf
                <select name="order_id" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required @disabled($eligibleOrders->isEmpty())>
                    <option value="" selected disabled>Selecione um pedido pago sem contrato</option>
                    @foreach($eligibleOrders as $order)
                        <option value="{{ $order->id }}">{{ $order->protocolo }} - {{ $order->user?->name }} - R$ {{ number_format((float) $order->valor, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" min="0" name="debt_amount" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Dívida (R$)" required @disabled($eligibleOrders->isEmpty())>
                <input type="number" step="0.01" min="0.01" name="fee_amount" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Honorários (R$)" required @disabled($eligibleOrders->isEmpty())>
                <input type="number" step="0.01" min="1" max="99" name="entry_percentage" value="50" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Entrada %" required @disabled($eligibleOrders->isEmpty())>
                <input type="file" name="document" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" accept=".doc,.docx,.pdf" @disabled($eligibleOrders->isEmpty())>
                <button class="btn-primary sm:col-span-2 lg:col-span-5" @disabled($eligibleOrders->isEmpty())>Criar contrato e link de aceite</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Contrato</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Analista</th>
                            <th class="px-4 py-3">Honorários</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Parcelas</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $statusInfo = $statusMap[$contract->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $contract->status)), 'class' => 'bg-slate-100/85 text-slate-800 border-slate-300/80'];
                            $acceptUrl = $contract->acceptanceUrl();
                        @endphp
                        <tr class="border-t border-slate-200/60 align-top">
                            <td class="px-4 py-3 font-semibold">#{{ $contract->id }}<br><span class="text-xs text-slate-500">Pedido {{ $contract->order?->protocolo }}</span></td>
                            <td class="px-4 py-3">{{ $contract->user?->name }}</td>
                            <td class="px-4 py-3">{{ $contract->analyst?->name ?: '-' }}</td>
                            <td class="px-4 py-3">R$ {{ number_format((float) $contract->fee_amount, 2, ',', '.') }}<br><span class="text-xs text-slate-500">Entrada: R$ {{ number_format((float) $contract->entry_amount, 2, ',', '.') }}</span></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                                @if($contract->accepted_at)
                                    <div class="mt-1 text-xs text-slate-500">Aceito em {{ $contract->accepted_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="space-y-1 text-xs">
                                    @foreach($contract->installments->sortBy('installment_number') as $installment)
                                        @php
                                            $installmentClass = $installment->status === 'pago'
                                                ? 'bg-emerald-100/85 text-emerald-900 border-emerald-300/80'
                                                : ($installment->status === 'vencido'
                                                    ? 'bg-rose-100/85 text-rose-900 border-rose-300/80'
                                                    : 'bg-amber-100/85 text-amber-900 border-amber-300/80');
                                        @endphp
                                        <div class="rounded border border-slate-300/60 bg-white/40 px-2 py-1">
                                            {{ $installment->label }} - R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}
                                            <span class="ml-1 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $installmentClass }}">{{ ucfirst(str_replace('_', ' ', (string) $installment->status)) }}</span>
                                            @if($installment->payment_link_url)
                                                <a class="ml-1 text-cyan-800 font-semibold" href="{{ $installment->payment_link_url }}" target="_blank" rel="noopener noreferrer">link</a>
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
                                    @if($acceptUrl)
                                        <div class="max-w-xs break-all text-slate-500">{{ $acceptUrl }}</div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Sem contratos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $contracts->links() }}</div>
        </section>
    </div>
</x-layouts.app>
