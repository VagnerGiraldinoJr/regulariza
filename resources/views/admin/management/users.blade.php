<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Criação de Usuários (Admin / Analistas)</h1></section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any() && ! $errors->has('reset_link'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
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
                <select name="pix_key_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tipo da chave PIX</option>
                    <option value="cpf">CPF</option>
                    <option value="cnpj">CNPJ</option>
                    <option value="email">E-mail</option>
                    <option value="telefone">Telefone</option>
                    <option value="aleatoria">Aleatória</option>
                </select>
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
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn-dark text-xs" data-modal-open="edit-user-modal-{{ $u->id }}">
                                            Editar acesso
                                        </button>
                                        <form method="POST" action="{{ route('admin.management.users.send-reset-link', $u) }}">
                                            @csrf
                                            <button type="submit" class="btn-primary text-xs">
                                                Enviar reset
                                            </button>
                                        </form>
                                    </div>
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

        @foreach($users as $u)
            <div id="edit-user-modal-{{ $u->id }}" class="app-modal" hidden>
                <div class="app-modal__backdrop" data-modal-dismiss></div>
                <div class="app-modal__dialog">
                    <div class="app-modal__header">
                        <div>
                            <h2 class="app-modal__title">Editar acesso</h2>
                            <p class="app-modal__subtitle">Atualize nome, e-mail e senha de {{ $u->name }} sem sair da tela.</p>
                        </div>
                        <button type="button" class="app-modal__close" data-modal-close aria-label="Fechar modal">
                            ×
                        </button>
                    </div>

                    <div class="app-modal__body">
                        <form method="POST" action="{{ route('admin.management.users.update', $u) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Nome</label>
                                <input name="name" value="{{ old('name', $u->name) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">E-mail</label>
                                <input name="email" type="email" value="{{ old('email', $u->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Nova senha</label>
                                <input name="password" type="password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Deixe em branco para manter">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Chave PIX</label>
                                <input name="pix_key" value="{{ old('pix_key', $u->pix_key) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="CPF, e-mail, telefone ou aleatória">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo da chave PIX</label>
                                <select name="pix_key_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <option value="">Selecione</option>
                                    @foreach(['cpf' => 'CPF', 'cnpj' => 'CNPJ', 'email' => 'E-mail', 'telefone' => 'Telefone', 'aleatoria' => 'Aleatória'] as $key => $label)
                                        <option value="{{ $key }}" @selected(old('pix_key_type', $u->pix_key_type) === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Confirmar nova senha</label>
                                <input name="password_confirmation" type="password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Repita a nova senha">
                            </div>
                            <div class="flex flex-wrap justify-end gap-2 pt-2">
                                <button type="button" class="btn-dark text-sm" data-modal-close>Cancelar</button>
                                <button type="submit" class="btn-primary text-sm">Salvar edição</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
