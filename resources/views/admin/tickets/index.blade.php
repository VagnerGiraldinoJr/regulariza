<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Dashboard SAC</h1>
            <p class="panel-subtitle mt-1">Visão operacional de atendimento com foco nos tickets sem atendente.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="metric-card metric-soft-blue">
                <h3>{{ $abertos }}</h3>
                <p>Tickets abertos</p>
            </div>
            <div class="metric-card metric-soft-amber">
                <h3>{{ $semAtendente }}</h3>
                <p>Sem atendente</p>
            </div>
            <div class="metric-card metric-soft-red">
                <h3>{{ $criticos }}</h3>
                <p>Críticos na fila</p>
            </div>
            <div class="metric-card metric-soft-green">
                <h3>{{ $resolvidosMes }}</h3>
                <p>Resolvidos no mês</p>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Tickets pendentes de atribuição</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Pedido</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Prioridade</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                @forelse ($tickets as $ticket)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ $ticket->protocolo }}</p>
                            <p class="text-xs text-slate-500">{{ $ticket->assunto }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $ticket->user?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $ticket->order?->protocolo ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $ticket->status }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $ticket->prioridade }}</td>
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
        </section>
    </div>
</x-layouts.app>
