<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Controle de Pagamentos de Comissões</h1>
            <p class="panel-subtitle mt-1">Pendente/liberado: R$ {{ number_format($totals['pending'], 2, ',', '.') }} | Pago: R$ {{ number_format($totals['paid'], 2, ',', '.') }}</p>
        </section>

        <section class="panel-card p-4">
            <form method="GET" class="flex gap-2">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach (['pending','available','paid','canceled'] as $s)
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
                        <tr>
                            <th class="px-4 py-3">Vendedor</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Base</th><th class="px-4 py-3">Comissão</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Disponível</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commissions as $c)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $c->seller?->name }}</td>
                                <td class="px-4 py-3">{{ $c->order?->user?->name }}</td>
                                <td class="px-4 py-3">R$ {{ number_format((float) $c->base_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 font-semibold">R$ {{ number_format((float) $c->commission_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-3">{{ $c->status }}</td>
                                <td class="px-4 py-3">{{ $c->available_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem comissões.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $commissions->links() }}</div>
        </section>
    </div>
</x-layouts.app>
