<x-layouts.app>
    <div class="space-y-4">
        <section>
            <h1 class="panel-title">Meu Perfil</h1>
            <p class="panel-subtitle mt-1">Atualize seus dados pessoais e de acesso.</p>
        </section>

        @if (session('profile_attention'))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('profile_attention') }}</div>
        @endif

        <section class="panel-card p-4 sm:p-5">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2">
                @csrf
                @method('PATCH')

                <div class="lg:col-span-2 flex flex-wrap items-center gap-4">
                    @php
                        $avatar = $user->avatar_path ? asset('storage/'.$user->avatar_path) : null;
                    @endphp
                    <div class="h-16 w-16 overflow-hidden rounded-full border border-slate-300 bg-slate-100">
                        @if($avatar)
                            <img src="{{ $avatar }}" alt="Foto de perfil" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500">SEM FOTO</div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Foto de perfil</label>
                        <input type="file" name="avatar" accept="image/*" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        @error('avatar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Nome</label>
                    <input name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" required>
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">E-mail</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" required>
                    @if ($user->hasProvisionalEmail())
                        <p class="mt-1 text-[11px] text-amber-700">Seu cadastro está com e-mail provisório. Informe seu e-mail principal para liberar o uso completo do portal.</p>
                    @endif
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Celular (WhatsApp)</label>
                    <input name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" required>
                    <p class="mt-1 text-[11px] text-slate-500">Se alterar, enviaremos uma mensagem de validação para o novo número.</p>
                    @error('whatsapp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Código de indicação</label>
                    <input
                        name="referral_code"
                        value="{{ old('referral_code', $user->referral_code) }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                        placeholder="exemplo123"
                        inputmode="text"
                        autocapitalize="off"
                        spellcheck="false"
                        pattern="[a-z0-9]+"
                        oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '')"
                    >
                    <p class="mt-1 text-[11px] text-slate-500">Use apenas letras minúsculas e números, sem espaços, acentos ou caracteres especiais.</p>
                    @error('referral_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($isSeller)
                    <div class="lg:col-span-2 mt-1 border-t border-slate-200 pt-4">
                        <h2 class="text-sm font-bold text-slate-700">Dados PIX para saque de comissão</h2>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Chave PIX</label>
                        <input name="pix_key" value="{{ old('pix_key', $user->pix_key) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="CPF, e-mail, telefone ou aleatória">
                        @error('pix_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo da chave</label>
                        <select name="pix_key_type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">Selecione</option>
                            @foreach(['cpf' => 'CPF', 'cnpj' => 'CNPJ', 'email' => 'E-mail', 'telefone' => 'Telefone', 'aleatoria' => 'Aleatória'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('pix_key_type', $user->pix_key_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('pix_key_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Nome do titular</label>
                        <input name="pix_holder_name" value="{{ old('pix_holder_name', $user->pix_holder_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        @error('pix_holder_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Documento do titular</label>
                        <input name="pix_holder_document" value="{{ old('pix_holder_document', $user->pix_holder_document) }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="CPF/CNPJ">
                        @error('pix_holder_document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="lg:col-span-2">
                    <button class="btn-primary" type="submit">Salvar perfil</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
