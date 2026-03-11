<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Cadastro de Vendedores</h1>
            <p class="panel-subtitle mt-1">Criação de analistas/vendedores com envio automático de e-mail para definição de senha.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('reset_preview'))
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <p class="font-semibold">Ambiente local: link de redefinição gerado para {{ session('reset_preview.email') }}.</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <code class="rounded bg-white px-2 py-1 text-xs text-slate-700">{{ session('reset_preview.url') }}</code>
                    <button type="button" class="rounded-md border border-sky-300 bg-white px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100" data-copy-ref="{{ session('reset_preview.url') }}">Copiar link</button>
                </div>
            </div>
        @endif
        @error('reset_link')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
        @enderror

        <section class="panel-card p-4">
            <form method="POST" action="{{ route('admin.management.vendors.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <input name="name" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Nome" required>
                <input name="email" type="email" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="E-mail" required>
                <select name="role" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                    <option value="vendedor">Vendedor</option>
                    <option value="analista">Analista</option>
                </select>
                <input name="cpf_cnpj" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="CPF/CNPJ">
                <input name="whatsapp" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="WhatsApp" required>
                <input name="pix_key" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Chave PIX">
                <select name="pix_key_type" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                    <option value="">Tipo da chave PIX</option>
                    <option value="cpf">CPF</option>
                    <option value="cnpj">CNPJ</option>
                    <option value="email">E-mail</option>
                    <option value="telefone">Telefone</option>
                    <option value="aleatoria">Aleatória</option>
                </select>
                <input name="pix_holder_name" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Nome do titular PIX">
                <input name="pix_holder_document" class="rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Documento titular PIX">
                <button class="btn-primary sm:col-span-2 lg:col-span-4">Cadastrar vendedor</button>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">E-mail</th>
                            <th class="px-4 py-3">Perfil</th>
                            <th class="px-4 py-3">PIX</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $v)
                            <tr class="border-t border-slate-200/60">
                                <td class="px-4 py-3">{{ $v->name }}</td>
                                <td class="px-4 py-3">{{ $v->email }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full border border-sky-300/80 bg-sky-100/85 px-2.5 py-1 text-xs font-bold text-sky-900">{{ ucfirst($v->role) }}</span></td>
                                <td class="px-4 py-3">{{ $v->pix_key ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admin.management.users.send-reset-link', $v) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary text-xs">
                                            Reenviar reset
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sem vendedores cadastrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-300/40 bg-white/10 px-4 py-3">{{ $vendors->links() }}</div>
        </section>
    </div>
</x-layouts.app>
