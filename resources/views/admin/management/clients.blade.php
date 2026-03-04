<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Cadastro de Clientes</h1></section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @error('reset_link')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <section class="panel-card p-4">
            <form method="POST" action="{{ route('admin.management.clients.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input name="name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nome" required>
                <input name="email" type="email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Email" required>
                <input name="password" type="password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Senha" required>
                <input name="cpf_cnpj" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="CPF/CNPJ">
                <input name="whatsapp" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="WhatsApp" required>
                <select name="referred_by_user_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Sem analista</option>
                    @foreach($analysts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
                <button class="btn-primary">Cadastrar cliente</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Contato</th><th class="px-4 py-3">Pedidos</th><th class="px-4 py-3">Tickets</th><th class="px-4 py-3">Histórico</th><th class="px-4 py-3 text-right">Ações</th></tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $c)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $c->name }}</td>
                                <td class="px-4 py-3">{{ $c->email }}<br>{{ $c->whatsapp }}</td>
                                <td class="px-4 py-3">{{ $c->orders_count }}</td>
                                <td class="px-4 py-3">{{ $c->sac_tickets_count }}</td>
                                <td class="px-4 py-3"><a class="text-blue-700 font-semibold" href="{{ route('admin.management.clients.history', $c) }}">Ver</a></td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.management.users.send-reset-link', $c) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-700">
                                            Enviar reset
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem clientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $clients->links() }}</div>
        </section>
    </div>
</x-layouts.app>
