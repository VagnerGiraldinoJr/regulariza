<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Leads sem Vendedor Atrelado</h1></section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Documento</th><th class="px-4 py-3">WhatsApp</th><th class="px-4 py-3">Ação</th></tr>
                    </thead>
                    <tbody>
                        @forelse($leads as $lead)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">#{{ $lead->id }}</td>
                                <td class="px-4 py-3">{{ $lead->cpf_cnpj }}</td>
                                <td class="px-4 py-3">{{ $lead->whatsapp }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.management.orphan-leads.assign', $lead) }}" class="flex gap-2">
                                        @csrf
                                        <select name="seller_id" class="rounded border border-slate-300 px-2 py-1 text-xs" required>
                                            <option value="">Selecione analista</option>
                                            @foreach($sellers as $seller)
                                                <option value="{{ $seller->id }}">{{ $seller->name }} ({{ $seller->referral_code ?: 'sem código' }})</option>
                                            @endforeach
                                        </select>
                                        <button class="btn-primary text-xs">Vincular</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Sem leads órfãos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $leads->links() }}</div>
        </section>
    </div>
</x-layouts.app>
