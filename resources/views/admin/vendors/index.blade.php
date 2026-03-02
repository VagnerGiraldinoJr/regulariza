<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Vendedores e Indicações</h1>
            <p class="panel-subtitle mt-1">Acompanhe quantos contratos cada vendedor trouxe e o valor total vendido.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="metric-card metric-soft-blue">
                <h3>{{ $totalSellers }}</h3>
                <p>Vendedores ativos</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>{{ $totalContracts }}</h3>
                <p>Contratos indicados</p>
            </div>
            <div class="metric-card metric-soft-amber">
                <h3>R$ {{ number_format($totalValue, 2, ',', '.') }}</h3>
                <p>Total vendido</p>
            </div>
        </section>

        <section class="space-y-4">
            @forelse ($sellers as $seller)
                <article class="panel-card overflow-hidden">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-extrabold text-slate-800">{{ $seller['name'] }}</h2>
                                <p class="text-xs text-slate-500">{{ $seller['email'] }} | Código: {{ $seller['referral_code'] ?: 'sem código' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-slate-800">{{ $seller['total_contratos'] }} contratos</p>
                                <p class="text-xs font-semibold text-emerald-700">R$ {{ number_format($seller['total_valor'], 2, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-white text-left text-xs uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="px-4 py-3">Contrato</th>
                                    <th class="px-4 py-3">Cliente</th>
                                    <th class="px-4 py-3">Documento</th>
                                    <th class="px-4 py-3">Valor</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($seller['contratos'] as $index => $contrato)
                                    <tr class="border-t border-slate-100">
                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            #{{ $index + 1 }} {{ $contrato['protocolo'] ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $contrato['cliente_nome'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $contrato['tipo_documento'] }} {{ $contrato['documento'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">R$ {{ number_format($contrato['valor'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3">
                                            @if ($contrato['pagamento_status'] === 'pago')
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">Válido</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">Pendente</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($contrato['whatsapp_link'])
                                                <a href="{{ $contrato['whatsapp_link'] }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-700 hover:text-blue-900">
                                                    Abrir WhatsApp
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-400">Sem número</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="panel-card p-5 text-sm text-slate-500">
                    Nenhuma venda por indicação encontrada até o momento.
                </div>
            @endforelse
        </section>
    </div>
</x-layouts.app>
