<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Auditoria Administrativa</h1>
            <p class="panel-subtitle mt-1">Todas as alteracoes sensiveis do admin ficam registradas aqui para rastreabilidade operacional.</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Total</p>
                <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format((int) $stats['total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Eventos auditados desde o inicio.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">Hoje</p>
                <p class="mt-3 text-3xl font-black text-cyan-700">{{ number_format((int) $stats['today']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Mudancas registradas nesta data.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Exclusoes</p>
                <p class="mt-3 text-3xl font-black text-rose-700">{{ number_format((int) $stats['deletions']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Remocoes feitas com trilha obrigatoria.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Integracoes</p>
                <p class="mt-3 text-3xl font-black text-emerald-700">{{ number_format((int) $stats['integrations']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Mudancas em Asaas, API Brasil, Z-API e produto.</p>
            </div>
        </section>

        <section class="panel-card p-4">
            <form method="GET" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto_auto]">
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Busca</label>
                    <input type="text" name="busca" value="{{ $search }}" placeholder="Descricao, alvo ou admin" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Acao</label>
                    <select name="action" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Todas</option>
                        @foreach ($availableActions as $availableAction)
                            <option value="{{ $availableAction }}" @selected($action === $availableAction)>{{ $availableAction }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary self-end">Filtrar</button>
                <a href="{{ route('admin.management.audit-logs') }}" class="btn-dark self-end text-center">Limpar</a>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Quando</th>
                            <th class="px-4 py-3">Acao</th>
                            <th class="px-4 py-3">Descricao</th>
                            <th class="px-4 py-3">Admin</th>
                            <th class="px-4 py-3">Contexto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr class="border-t border-slate-100 align-top">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge badge-neutral">{{ $log->action }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $log->description }}</div>
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $log->target_type ?: 'Sem alvo' }}
                                        @if ($log->target_id)
                                            #{{ $log->target_id }}
                                        @endif
                                        @if ($log->target_label)
                                            • {{ $log->target_label }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-700">{{ $log->admin?->name ?: 'Sistema' }}</div>
                                    <div class="text-xs text-slate-500">{{ $log->admin?->email ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if (! empty($log->context))
                                        <details class="rounded-xl border border-slate-200 bg-slate-950/95 p-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-emerald-100">Ver contexto</summary>
                                            <pre class="mt-2 max-h-56 overflow-auto text-[11px] text-emerald-100">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @else
                                        <span class="text-xs text-slate-500">Sem contexto adicional.</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Nenhum evento de auditoria para os filtros atuais.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $logs->links() }}</div>
        </section>
    </div>
</x-layouts.app>
