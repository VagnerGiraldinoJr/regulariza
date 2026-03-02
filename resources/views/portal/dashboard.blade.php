<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Portal do Cliente</h1>
            <p class="panel-subtitle mt-1">Acompanhe pedidos, pagamentos e andamento da regularização.</p>
        </section>

        @if (session('payment_link_error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('payment_link_error') }}
            </div>
        @endif

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
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Minhas indicações (vendas)</h2>
                <p class="mt-1 text-xs text-slate-500">Contratos vendidos com seu código de referência.</p>
            </div>

            <div class="grid gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:grid-cols-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Contratos</p>
                    <p class="text-base font-extrabold text-slate-800">{{ $referralStats['total_contratos'] }}</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Total vendido</p>
                    <p class="text-base font-extrabold text-emerald-700">R$ {{ number_format($referralStats['valor_total'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Válidos</p>
                    <p class="text-base font-extrabold text-emerald-700">{{ $referralStats['validos'] }}</p>
                </div>
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Pendentes</p>
                    <p class="text-base font-extrabold text-amber-700">{{ $referralStats['pendentes'] }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Serviço</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">WhatsApp</th>
                            <th class="px-4 py-3">Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($referralOrders as $refOrder)
                            @php
                                $doc = $refOrder->lead?->cpf_cnpj ?: $refOrder->user?->cpf_cnpj;
                                $docDigits = preg_replace('/\D+/', '', (string) $doc);
                                $docType = strlen($docDigits) > 11 ? 'CNPJ' : 'CPF';
                                $phoneDigits = preg_replace('/\D+/', '', (string) ($refOrder->lead?->whatsapp ?: $refOrder->user?->whatsapp));
                                $phoneDigits = $phoneDigits !== '' && strlen($phoneDigits) <= 11 ? '55'.$phoneDigits : $phoneDigits;
                                $phoneLink = $phoneDigits !== '' ? 'https://wa.me/'.$phoneDigits : null;
                            @endphp
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $refOrder->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $refOrder->user?->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $docType }} {{ $doc ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $refOrder->service?->nome }}</td>
                                <td class="px-4 py-3">
                                    @if ($refOrder->pagamento_status === 'pago')
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">Válido</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">Pendente</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($phoneLink)
                                        <a href="{{ $phoneLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-700 hover:text-blue-900">
                                            Abrir WhatsApp
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400">Sem número</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($refOrder->pagamento_status !== 'pago')
                                        <form method="POST" action="{{ route('portal.orders.resend-payment-link', $refOrder) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-[#20b6c7] px-2 py-1 text-xs font-semibold text-white hover:bg-[#1599a8]">
                                                Reenviar link
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Pago</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-5 text-center text-slate-500">Você ainda não possui contratos vendidos por indicação.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $referralOrders->links() }}
            </div>
        </section>

        <section class="panel-card p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Diagnóstico Financeiro - {{ $creditReport['tipo'] }}</h2>
                    <p class="text-xs text-slate-500">Dados de demonstração. Data da consulta: {{ $creditReport['data_consulta'] }}</p>
                </div>
                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">Rating {{ $creditReport['diagnostico']['rating'] }}</span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="metric-card metric-soft-blue">
                    <h3>{{ number_format($creditReport['indicadores']['score'], 0, ',', '.') }}</h3>
                    <p>Score</p>
                </div>
                <div class="metric-card metric-soft-red">
                    <h3>{{ number_format($creditReport['indicadores']['prob_inadimplencia'], 1, ',', '.') }}%</h3>
                    <p>Prob. inadimplência</p>
                </div>
                <div class="metric-card metric-soft-green">
                    <h3>R$ {{ number_format($creditReport['indicadores']['limite_sugerido'], 2, ',', '.') }}</h3>
                    <p>Limite sugerido</p>
                </div>
                <div class="metric-card metric-soft-amber">
                    <h3>R$ {{ number_format($creditReport['indicadores']['renda_estimada'], 2, ',', '.') }}</h3>
                    <p>Renda estimada</p>
                </div>
                <div class="metric-card metric-soft-blue">
                    <h3>{{ number_format($creditReport['indicadores']['pontualidade_pagamento'], 2, ',', '.') }}%</h3>
                    <p>Pontualidade</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Dados cadastrais</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        @foreach ($creditReport['dados_cadastrais'] as $campo => $valor)
                            <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                                <dt class="font-semibold text-slate-600">{{ str_replace('_', ' ', ucfirst($campo)) }}</dt>
                                <dd class="text-right text-slate-800">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Resumo financeiro</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                            <dt class="font-semibold text-slate-600">Saúde financeira</dt>
                            <dd class="text-right text-slate-800">{{ $creditReport['resumo']['saude_financeira'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                            <dt class="font-semibold text-slate-600">Capacidade mensal</dt>
                            <dd class="text-right text-slate-800">R$ {{ number_format($creditReport['resumo']['capacidade_mensal'], 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                            <dt class="font-semibold text-slate-600">Limite crédito mensal</dt>
                            <dd class="text-right text-slate-800">R$ {{ number_format($creditReport['resumo']['limite_credito_mensal'], 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                            <dt class="font-semibold text-slate-600">Comprometimento renda</dt>
                            <dd class="text-right text-slate-800">{{ $creditReport['resumo']['comprometimento_renda'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2 border-b border-slate-100 pb-1">
                            <dt class="font-semibold text-slate-600">Busca de crédito 12m</dt>
                            <dd class="text-right text-slate-800">{{ $creditReport['resumo']['busca_credito_12m'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="font-semibold text-slate-600">Endividamento crédito</dt>
                            <dd class="text-right text-slate-800">{{ $creditReport['resumo']['endividamento_credito'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Ocorrências</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody>
                                @foreach ($creditReport['ocorrencias'] as $ocorrencia)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-1 pr-2 text-slate-700">{{ $ocorrencia['item'] }}</td>
                                        <td class="py-1 text-right font-semibold text-emerald-700">{{ $ocorrencia['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Endereços cadastrados</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-700">
                        @foreach ($creditReport['enderecos'] as $endereco)
                            <li class="rounded-md bg-slate-50 px-3 py-2">{{ $endereco }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if (! empty($creditReport['participacoes']))
                <div class="mt-4 rounded-xl border border-slate-200 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-600">Participações societárias</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="py-2 pr-2">Empresa</th>
                                    <th class="py-2 pr-2">CNPJ</th>
                                    <th class="py-2 text-right">Participação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($creditReport['participacoes'] as $participacao)
                                    <tr class="border-t border-slate-100">
                                        <td class="py-2 pr-2 text-slate-700">{{ $participacao['empresa'] }}</td>
                                        <td class="py-2 pr-2 text-slate-600">{{ $participacao['cnpj'] }}</td>
                                        <td class="py-2 text-right font-semibold text-slate-800">{{ $participacao['participacao'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <p class="mt-4 text-xs text-slate-500">
                {{ $creditReport['diagnostico']['conclusao'] }}. O modelo é estatístico e a decisão final de crédito deve considerar a política interna.
            </p>
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
                            <th class="px-4 py-3">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->service?->nome }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->status }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->pagamento_status }}</td>
                                <td class="px-4 py-3">
                                    @if ($order->pagamento_status !== 'pago')
                                        <form method="POST" action="{{ route('portal.orders.resend-payment-link', $order) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-[#20b6c7] px-2 py-1 text-xs font-semibold text-white hover:bg-[#1599a8]">
                                                Pagar agora
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhum pedido encontrado.</td>
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
