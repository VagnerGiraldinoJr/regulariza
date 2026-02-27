<x-layouts.app>
    <div class="min-h-screen bg-[radial-gradient(circle_at_20%_15%,#20b6c7_0%,#118ea0_40%,#061a2d_100%)] px-4 py-8 lg:px-6">
        <div class="mx-auto max-w-5xl">
            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl">
                <div class="grid lg:grid-cols-[1.45fr_0.85fr]">
                    <section class="relative min-h-[380px] bg-[radial-gradient(circle_at_20%_20%,#0a263e_0%,#071a2f_42%,#04101f_100%)] p-8 text-white sm:p-10">
                        <div class="absolute inset-0 bg-[linear-gradient(130deg,rgba(32,182,199,.36),rgba(56,231,247,.14)_45%,transparent_68%)]"></div>
                        <div class="relative z-10">
                            <img src="{{ asset('assets/branding/cpfclean-logo.svg') }}" alt="CPF Clean Brasil" class="h-14 w-14 rounded-xl border border-white/20">
                            <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">CPF Clean Brasil</p>
                            <h1 class="mt-4 text-4xl font-black leading-[0.98] text-cyan-300 sm:text-5xl">
                                Limpe seu CPF ou CNPJ com segurança.
                            </h1>
                            <div class="mt-4 space-y-1 text-base text-slate-200">
                                <p>Limpe seu CPF ou CNPJ</p>
                                <p>Aumente seu Score</p>
                                <p>Mais de 1.000 clientes atendidos</p>
                                <p>Atendemos em todo Brasil</p>
                            </div>

                            <a
                                href="https://www.instagram.com/cpfclean.brasil/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-8 inline-flex items-center gap-2 rounded-lg border border-cyan-300/60 bg-cyan-500/20 px-4 py-2 text-sm font-bold text-cyan-100 transition hover:bg-cyan-500/30"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.35a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8A3.2 3.2 0 1 0 12 15.2 3.2 3.2 0 0 0 12 8.8Z"/>
                                </svg>
                                <span>@cpfclean.brasil</span>
                            </a>
                        </div>
                    </section>

                    <section class="bg-[#f8f9fb] p-6 sm:p-8">
                        <div class="mx-auto max-w-xs">
                            <h2 class="text-center text-4xl font-black tracking-tight text-[#118ea0]">CPF CLEAN</h2>
                            <p class="mt-1 text-center text-sm font-semibold text-slate-500">Área do Cliente</p>

                            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-3">
                                @csrf

                                <div>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        placeholder="E-mail *"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                                    />
                                    @error('email')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        required
                                        placeholder="Senha *"
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#20b6c7] focus:outline-none"
                                    />
                                </div>

                                <button type="submit" class="w-full rounded-md bg-[#20b6c7] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1599a8]">
                                    Entrar
                                </button>
                            </form>

                            <div class="mt-6 text-center text-xs text-slate-500">
                                <a href="#" class="hover:text-slate-700">Esqueci minha senha?</a>
                            </div>

                            <div class="mt-10 space-y-1 text-center text-xs font-semibold text-[#118ea0]">
                                <a href="#" class="hover:text-[#0e7d8d]">Termos de Uso</a>
                                <span class="text-slate-400">•</span>
                                <a href="#" class="hover:text-[#0e7d8d]">Política de Privacidade</a>
                                <span class="text-slate-400">•</span>
                                <a href="#" class="hover:text-[#0e7d8d]">Segurança da Informação</a>
                            </div>

                            <div class="mt-5 text-center text-xs text-slate-500">
                                <p>©2026 - CPF Clean Brasil</p>
                                <p>0800 878 1179 | (11) 3197-0719</p>
                                <p>atendimento@regulariza.com.br</p>
                            </div>

                            <div class="mt-3 text-center text-xs">
                                <a class="font-semibold text-[#118ea0] hover:text-[#0e7d8d]" href="https://www.instagram.com/cpfclean.brasil/" target="_blank" rel="noopener noreferrer">Instagram: @cpfclean.brasil</a>
                            </div>

                            <div class="mt-5 flex justify-center">
                                <img src="{{ asset('assets/selos-seguranca/siteblindado.svg') }}" alt="Site Blindado" class="h-10 w-auto object-contain" />
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
