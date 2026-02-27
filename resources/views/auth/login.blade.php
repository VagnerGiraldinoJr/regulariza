<x-layouts.app>
    <div class="min-h-screen bg-[radial-gradient(circle_at_20%_20%,#1e3a8a_0%,#0f172a_36%,#0b1220_100%)] px-4 py-10">
        <div class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-2xl border border-white/15 bg-white/5 p-8 text-white backdrop-blur-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-300">Regulariza Plataforma</p>
                <h1 class="mt-4 text-3xl font-black leading-tight">Painel administrativo e portal do cliente com fluxo de regularização.</h1>
                <p class="mt-3 max-w-xl text-sm text-slate-200">Visual inspirado em AdminLTE: foco em leitura rápida, navegação lateral clara e painéis orientados a operação.</p>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-white/15 bg-white/10 p-3">
                        <p class="text-xs uppercase tracking-wide text-blue-200">BACEN</p>
                   
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 p-3">
                        <p class="text-xs uppercase tracking-wide text-blue-200">CONSULTORIA</p>
                       
                    </div>
                    <div class="rounded-xl border border-white/15 bg-white/10 p-3">
                        <p class="text-xs uppercase tracking-wide text-blue-200">SERASA</p>
                       
                    </div>
                </div>
            </section>

            <section class="panel-card p-6 sm:p-8">
                <h2 class="text-2xl font-black text-slate-800">Acessar sistema</h2>
                <p class="panel-subtitle mt-1">Entre com suas credenciais.</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-semibold text-slate-700">Senha</label>
                        <input id="password" name="password" type="password" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none" />
                    </div>

                    <button type="submit" class="btn-primary w-full">Entrar</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
