<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Criação de Usuários (Admin / Analistas)</h1></section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @error('reset_link')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <section class="panel-card p-4">
            <form method="POST" action="{{ route('admin.management.users.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input name="name" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nome" required>
                <input name="email" type="email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Email" required>
                <input name="password" type="password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Senha" required>
                <select name="role" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                    @foreach(['admin','atendente','analista','vendedor'] as $role)
                        <option value="{{ $role }}">{{ $role }}</option>
                    @endforeach
                </select>
                <input name="cpf_cnpj" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="CPF/CNPJ">
                <input name="whatsapp" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="WhatsApp">
                <input name="pix_key" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Chave PIX">
                <button class="btn-primary">Criar usuário</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Role</th><th class="px-4 py-3">PIX</th><th class="px-4 py-3 text-right">Ações</th></tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ $u->name }}</td>
                                <td class="px-4 py-3">{{ $u->email }}</td>
                                <td class="px-4 py-3">{{ $u->role }}</td>
                                <td class="px-4 py-3">{{ $u->pix_key ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.management.users.send-reset-link', $u) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-700">
                                            Enviar reset
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem usuários.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $users->links() }}</div>
        </section>
    </div>
</x-layouts.app>
