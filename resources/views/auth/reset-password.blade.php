<x-layouts.app>
    <div class="min-h-screen bg-[radial-gradient(circle_at_20%_15%,#20b6c7_0%,#118ea0_40%,#061a2d_100%)] px-4 py-8 lg:px-6">
        <div class="mx-auto max-w-xl">
            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl p-6 sm:p-8">
                <h1 class="text-2xl font-black text-[#118ea0]">Redefinir senha</h1>
                <p class="mt-2 text-sm text-slate-600">Defina a nova senha de acesso.</p>

                <form method="POST" action="{{ route('password.update') }}" class="mt-5 space-y-3">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">E-mail</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            required
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                        />
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Nova senha</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                        />
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-600">Confirmar nova senha</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                        />
                    </div>

                    <button type="submit" class="w-full rounded-md bg-[#20b6c7] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1599a8]">
                        Redefinir senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
