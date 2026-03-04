<x-layouts.app>
    <div class="min-h-screen bg-[radial-gradient(circle_at_20%_15%,#20b6c7_0%,#118ea0_40%,#061a2d_100%)] px-4 py-8 lg:px-6">
        <div class="mx-auto max-w-xl">
            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl p-6 sm:p-8">
                <h1 class="text-2xl font-black text-[#118ea0]">Recuperar senha</h1>
                <p class="mt-2 text-sm text-slate-600">Informe o e-mail do usuário para receber o link de redefinição.</p>

                @if (session('status'))
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="mt-5 space-y-3">
                    @csrf

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full rounded-md bg-[#20b6c7] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1599a8]">
                        Enviar link de redefinição
                    </button>
                </form>

                <div class="mt-6 text-center text-xs">
                    <a href="{{ route('login') }}" class="font-semibold text-[#118ea0] hover:text-[#0e7d8d]">Voltar para o login</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
