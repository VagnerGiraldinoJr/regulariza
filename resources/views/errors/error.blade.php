<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code ?? 'Erro' }} - CPF Clean Brasil</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="min-h-screen px-5 py-8 lg:px-10">
        <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center">
            <div class="grid w-full items-center gap-8 rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-xl backdrop-blur md:grid-cols-[1fr_1.1fr] lg:p-10">
                <section class="relative min-h-[280px] overflow-hidden rounded-2xl bg-[radial-gradient(circle_at_18%_18%,#1fbad1_0%,#0f7ea3_46%,#0a3f69_100%)] p-6 text-white">
                    <div class="absolute -left-10 -top-8 h-40 w-40 rounded-full bg-white/15 blur-xl"></div>
                    <div class="absolute bottom-0 right-0 h-40 w-40 rounded-full bg-cyan-200/20 blur-xl"></div>
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div>
                            <img src="{{ asset('assets/branding/cpfclean-logo.svg') }}" alt="CPF Clean Brasil" class="h-12 w-12 rounded-xl border border-white/30 bg-white/10 p-1">
                            <p class="mt-4 text-xs font-bold uppercase tracking-[0.24em] text-cyan-100">CPF Clean Brasil</p>
                            <h1 class="mt-3 text-3xl font-black">Ops!</h1>
                            <p class="mt-2 text-sm text-cyan-100/95">{{ $visualText ?? 'Algo saiu do fluxo esperado.' }}</p>
                        </div>
                        <div class="mt-6 flex items-center gap-3 text-xs text-cyan-100/90">
                            <img src="{{ asset('assets/branding/letsencrypt-logo-horizontal.svg') }}" alt="Let's Encrypt" class="h-4 w-auto opacity-90">
                            <span>Conexão segura • Ambiente protegido</span>
                        </div>
                    </div>
                </section>

                <section>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Erro {{ $code ?? '---' }}</p>
                    <h2 class="mt-2 text-4xl font-black text-[#0f4b8d]">{{ $headline ?? 'Aconteceu um erro.' }}</h2>
                    <p class="mt-3 text-lg font-semibold text-slate-800">{{ $title ?? 'Não foi possível concluir esta operação.' }}</p>
                    <p class="mt-4 max-w-xl text-sm text-slate-600">{{ $description ?? 'Tente novamente em alguns minutos. Se o problema persistir, acione o suporte.' }}</p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ url('/') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-800 hover:bg-slate-50">Ir para o início</a>
                        <button type="button" onclick="window.history.back()" class="rounded-lg bg-[#0b49c8] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#073aa3]">Voltar para a página anterior</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</body>
</html>
