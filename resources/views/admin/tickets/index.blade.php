<x-layouts.app>
    @php
        $statusMap = [
            'aberto' => ['label' => 'Aberto', 'class' => 'badge-warning'],
            'em_atendimento' => ['label' => 'Em atendimento', 'class' => 'badge-info'],
            'resolvido' => ['label' => 'Resolvido', 'class' => 'badge-success'],
            'fechado' => ['label' => 'Fechado', 'class' => 'badge-neutral'],
        ];
        $priorityMap = [
            'nova' => ['label' => 'Nova', 'class' => 'badge-neutral'],
            'baixa' => ['label' => 'Baixa', 'class' => 'badge-success'],
            'media' => ['label' => 'Media', 'class' => 'badge-info'],
            'alta' => ['label' => 'Alta', 'class' => 'badge-warning'],
            'critica' => ['label' => 'Critica', 'class' => 'badge-danger'],
        ];
    @endphp

    <div class="space-y-5">
        <section class="hero-card p-5 lg:p-6">
            <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <p class="hero-card__eyebrow">Atendimento</p>
                    <h1 class="hero-card__title">Dashboard SAC</h1>
                    <p class="hero-card__lead">Fila operacional dos chamados sem atendente atribuido, com foco em distribuicao rapida e leitura de prioridade da carteira.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="summary-pill">Fila aberta: {{ $abertos }}</span>
                        <span class="summary-pill">Sem atendente: {{ $semAtendente }}</span>
                        <span class="summary-pill">Resolvidos no mes: {{ $resolvidosMes }}</span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="hero-stat">
                        <p class="hero-stat__label">Criticos</p>
                        <p class="hero-stat__value">{{ $criticos }}</p>
                        <p class="hero-stat__meta">Tickets criticos ainda sem dono no SAC.</p>
                    </article>
                    <article class="hero-stat">
                        <p class="hero-stat__label">Aguardando atribuicao</p>
                        <p class="hero-stat__value">{{ $semAtendente }}</p>
                        <p class="hero-stat__meta">Chamados prontos para alguem assumir agora.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="insight-tile insight-tile--cyan">
                <p class="insight-tile__label">Tickets abertos</p>
                <p class="insight-tile__value">{{ $abertos }}</p>
                <p class="insight-tile__meta">Base total da fila em aberto.</p>
            </article>
            <article class="insight-tile insight-tile--amber">
                <p class="insight-tile__label">Sem atendente</p>
                <p class="insight-tile__value">{{ $semAtendente }}</p>
                <p class="insight-tile__meta">Ponto de atencao para distribuicao imediata.</p>
            </article>
            <article class="insight-tile insight-tile--rose">
                <p class="insight-tile__label">Criticos</p>
                <p class="insight-tile__value">{{ $criticos }}</p>
                <p class="insight-tile__meta">Itens com potencial de maior impacto operacional.</p>
            </article>
            <article class="insight-tile insight-tile--emerald">
                <p class="insight-tile__label">Resolvidos no mes</p>
                <p class="insight-tile__value">{{ $resolvidosMes }}</p>
                <p class="insight-tile__meta">Volume encerrado no periodo atual.</p>
            </article>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-300/50 bg-white/15 px-4 py-3">
                <p class="section-kicker">Fila pendente</p>
                <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Tickets sem atendente</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Pedido</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Prioridade</th>
                            <th class="px-4 py-3 text-right">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                @forelse ($tickets as $ticket)
                    @php
                        $statusInfo = $statusMap[$ticket->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $ticket->status)), 'class' => 'badge-neutral'];
                        $priorityInfo = $priorityMap[$ticket->prioridade] ?? ['label' => ucfirst((string) $ticket->prioridade), 'class' => 'badge-neutral'];
                    @endphp
                    <tr class="border-t border-slate-200/60">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $ticket->protocolo }}</p>
                            <p class="text-xs text-slate-500">{{ $ticket->assunto }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $ticket->user?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $ticket->order?->protocolo ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">
                            <span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <span class="badge {{ $priorityInfo['class'] }}">{{ $priorityInfo['label'] }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.tickets.assign', $ticket->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn-primary text-xs">Assumir</button>
                                </form>
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn-dark text-xs">Abrir chat</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum ticket sem atendente no momento.</td>
                    </tr>
                @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">
                {{ $tickets->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
