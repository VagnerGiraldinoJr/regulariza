<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Portal do Cliente</h1>
            <p class="panel-subtitle mt-1">Acompanhe pedidos, pagamentos e andamento da regularização.</p>
        </section>

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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $order->service?->nome }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->status }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->pagamento_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Nenhum pedido encontrado.</td>
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
