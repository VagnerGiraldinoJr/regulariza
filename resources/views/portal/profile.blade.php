<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Alterar Dados Pessoais</h1></section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="panel-card p-4">
            <form method="POST" action="{{ route('portal.profile.update') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                @method('PATCH')
                <input name="name" value="{{ old('name', $user->name) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nome" required>
                <input name="email" value="{{ old('email', $user->email) }}" type="email" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Email" required>
                <input name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="WhatsApp" required>
                <input name="cpf_cnpj" value="{{ old('cpf_cnpj', $user->cpf_cnpj) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="CPF/CNPJ">
                <input name="password" type="password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Nova senha (opcional)">
                <input name="password_confirmation" type="password" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Confirmar nova senha">
                <button class="btn-primary sm:col-span-2">Salvar alterações</button>
            </form>
        </section>
    </div>
</x-layouts.app>
