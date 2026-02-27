<x-layouts.app>
    <div class="min-h-screen bg-[radial-gradient(circle_at_20%_15%,#60a5fa_0%,#2563eb_32%,#1e3a8a_100%)] px-4 py-8 lg:px-6">
        <div class="mx-auto max-w-5xl">
            <div class="overflow-hidden rounded-2xl border border-black/10 bg-white shadow-2xl">
                <div class="grid lg:grid-cols-[1.45fr_0.85fr]">
                    <section class="relative min-h-[380px] bg-[radial-gradient(circle_at_20%_20%,#0f172a_0%,#0b1220_40%,#05070f_100%)] p-8 text-white sm:p-10">
                        <div class="absolute inset-0 bg-[linear-gradient(130deg,rgba(59,130,246,.34),rgba(14,165,233,.10)_45%,transparent_68%)]"></div>
                        <div class="relative z-10">
                            <img src="{{ asset('assets/branding/cpfclean-logo.svg') }}" alt="CPF Clean Brasil" class="h-14 w-14 rounded-xl border border-white/20">
                            <p class="mt-4 text-xs font-bold uppercase tracking-[0.2em] text-sky-300">CPF Clean Brasil</p>
                            <h1 class="mt-4 text-4xl font-black leading-[0.98] text-blue-400 sm:text-5xl">
                                Limpe seu CPF ou CNPJ com segurança.
                            </h1>
                            <div class="mt-4 space-y-1 text-base text-slate-200">
                                <p>Limpe seu CPF ou CNPJ</p>
                                <p>Aumente seu Score</p>
                                <p>Mais de 1.000 clientes atendidos</p>
                                <p>Atendemos em todo Brasil</p>
                            </div>

                            <div class="mt-8 inline-flex rounded-lg border border-sky-300/60 bg-sky-500/20 px-4 py-2 text-sm font-bold text-sky-100">
                                @cpfclean.brasil
                            </div>
                        </div>
                    </section>

                    <section class="bg-[#f8f9fb] p-6 sm:p-8">
                        <div class="mx-auto max-w-xs">
                            <h2 class="text-center text-4xl font-black tracking-tight text-[#1d4ed8]">CPF CLEAN</h2>
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
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#2563eb] focus:outline-none"
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
                                        class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#2563eb] focus:outline-none"
                                    />
                                </div>

                                <button type="submit" class="w-full rounded-md bg-[#1d4ed8] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#1e40af]">
                                    Entrar
                                </button>
                            </form>

                            <div class="mt-6 text-center text-xs text-slate-500">
                                <a href="#" class="hover:text-slate-700">Esqueci minha senha?</a>
                            </div>

                            <div class="mt-10 space-y-1 text-center text-xs font-semibold text-blue-600">
                                <a href="#" class="hover:text-blue-700">Termos de Uso</a>
                                <span class="text-slate-400">•</span>
                                <a href="#" class="hover:text-blue-700">Política de Privacidade</a>
                                <span class="text-slate-400">•</span>
                                <a href="#" class="hover:text-blue-700">Segurança da Informação</a>
                            </div>

                            <div class="mt-5 text-center text-xs text-slate-500">
                                <p>©2026 - CPF Clean Brasil</p>
                                <p>0800 878 1179 | (11) 3197-0719</p>
                                <p>atendimento@regulariza.com.br</p>
                            </div>

                            <div class="mt-3 text-center text-xs">
                                <a class="font-semibold text-blue-600 hover:text-blue-700" href="https://www.instagram.com/cpfclean.brasil/" target="_blank" rel="noopener noreferrer">Instagram: @cpfclean.brasil</a>
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
