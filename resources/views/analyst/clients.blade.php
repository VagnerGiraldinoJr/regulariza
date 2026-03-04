<x-layouts.app>
    @php
        $tagMap = [
            'novo' => ['label' => 'Novo', 'class' => 'badge-neutral'],
            'aguardando_pagamento' => ['label' => 'Aguardando pagamento', 'class' => 'badge-warning'],
            'pesquisa_analise' => ['label' => 'Pesquisa em análise', 'class' => 'badge-info'],
            'em_regularizacao' => ['label' => 'Em regularização', 'class' => 'badge-purple'],
            'concluido' => ['label' => 'Concluído', 'class' => 'badge-success'],
        ];
    @endphp

    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Carteira de Clientes e Pipeline</h1>
            <p class="panel-subtitle mt-1">Visão comercial da sua carteira com contato rápido.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="metric-card metric-soft-blue"><h3>{{ $pipeline['novo'] }}</h3><p>Novo</p></div>
            <div class="metric-card metric-soft-amber"><h3>{{ $pipeline['aguardando_pagamento'] }}</h3><p>Aguardando pagamento</p></div>
            <div class="metric-card metric-soft-green"><h3>{{ $pipeline['pesquisa_analise'] }}</h3><p>Pesquisa em análise</p></div>
            <div class="metric-card metric-soft-blue"><h3>{{ $pipeline['em_regularizacao'] }}</h3><p>Em regularização</p></div>
            <div class="metric-card metric-soft-green"><h3>{{ $pipeline['concluido'] }}</h3><p>Concluído</p></div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Contato rápido</th>
                            <th class="px-4 py-3">Pedidos</th>
                            <th class="px-4 py-3">Tickets</th>
                            <th class="px-4 py-3">Tag pipeline</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $c)
                            @php
                                $digits = preg_replace('/\D+/', '', (string) $c->whatsapp);
                                $phone = $digits !== '' && strlen($digits) <= 11 ? '55'.$digits : $digits;
                                $wa = $phone ? 'https://wa.me/'.$phone : null;
                                $lastOrder = $c->orders()->latest('id')->first();
                                $tag = 'novo';
                                if ($lastOrder) {
                                    if ($lastOrder->pagamento_status !== 'pago') {
                                        $tag = 'aguardando_pagamento';
                                    } elseif ($lastOrder->status === 'em_andamento') {
                                        $tag = 'pesquisa_analise';
                                    } elseif ($lastOrder->status === 'concluido') {
                                        $tag = 'concluido';
                                    } else {
                                        $tag = 'em_regularizacao';
                                    }
                                }
                                $tagInfo = $tagMap[$tag] ?? ['label' => ucfirst(str_replace('_', ' ', $tag)), 'class' => 'badge-neutral'];
                                $statusInfo = $lastOrder?->pagamento_status === 'pago'
                                    ? ['label' => 'Ativo', 'class' => 'badge-success']
                                    : ['label' => 'Pendente', 'class' => 'badge-warning'];
                            @endphp
                            <tr class="border-t border-slate-200/60">
                                <td class="px-4 py-3">{{ $c->name }}<br><span class="text-xs text-slate-500">{{ $c->email }}</span></td>
                                <td class="px-4 py-3">
                                    @if($wa)
                                        <a class="inline-flex items-center gap-1 rounded-lg border border-emerald-300/70 bg-emerald-50/70 px-2 py-1 text-xs font-semibold text-emerald-700" href="{{ $wa }}" target="_blank" rel="noopener noreferrer">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12a8 8 0 0 1-11.8 7L4 20l1-4.2A8 8 0 1 1 20 12Z"/><path d="M8.4 10.5c.2 1.8 2.3 3.9 4.1 4.1.4 0 .9-.3 1.1-.7l.4-.8c.1-.3.5-.5.8-.4l1.3.4c.4.1.6.5.5.9-.2 1.2-1.3 2.1-2.5 2-3.7-.3-6.7-3.3-7-7-.1-1.2.8-2.3 2-2.5.4-.1.8.1.9.5l.4 1.3c.1.3 0 .7-.4.8l-.8.4c-.4.2-.7.7-.7 1.1Z"/></svg>
                                            WhatsApp
                                        </a>
                                    @else
                                        <span class="text-slate-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><span class="badge badge-info">{{ $c->orders_count }}</span></td>
                                <td class="px-4 py-3"><span class="badge badge-purple">{{ $c->sac_tickets_count }}</span></td>
                                <td class="px-4 py-3"><span class="badge {{ $tagInfo['class'] }}">{{ $tagInfo['label'] }}</span></td>
                                <td class="px-4 py-3"><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem clientes na carteira.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $clients->links() }}</div>
        </section>
    </div>
</x-layouts.app>
