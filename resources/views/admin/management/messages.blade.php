<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Mensagens Enviadas / Pendentes</h1></section>
        <section class="panel-card p-4">
            <form class="flex gap-2" method="GET">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach (['enviado','pendente','falhou'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <button class="btn-primary">Filtrar</button>
            </form>
        </section>
        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">Data</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Evento</th><th class="px-4 py-3">Telefone</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $m)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $m->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $m->user?->name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $m->evento }}</td>
                                <td class="px-4 py-3">{{ $m->telefone }}</td>
                                <td class="px-4 py-3">{{ $m->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem mensagens.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $messages->links() }}</div>
        </section>
    </div>
</x-layouts.app>
