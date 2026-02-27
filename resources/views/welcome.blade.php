<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPF Clean Brasil</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/cpfclean-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 lg:px-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('assets/branding/cpfclean-logo.svg') }}" alt="CPF Clean Brasil" class="h-9 w-9 rounded-lg">
                <span class="text-lg font-black text-slate-900">CPF Clean Brasil</span>
            </div>

            <nav class="hidden items-center gap-6 text-sm font-semibold text-slate-600 lg:flex">
                <a href="#plataforma" class="hover:text-slate-900">Plataforma</a>
                <a href="#solucoes" class="hover:text-slate-900">Soluções</a>
                <a href="#cases" class="hover:text-slate-900">Cases</a>
                <a href="#conteudos" class="hover:text-slate-900">Conteúdos</a>
                <a href="#sobre" class="hover:text-slate-900">Sobre Nós</a>
                <a href="#lgpd" class="hover:text-slate-900">LGPD</a>
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="btn-dark text-sm">Área do Cliente</a>
            </div>
        </div>
    </header>

    <main>
        <section id="plataforma" class="bg-[radial-gradient(circle_at_top_right,#d7f4f8_0%,#e9f7fa_38%,#f1f8fa_100%)]">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 lg:grid-cols-[1.1fr_0.9fr] lg:px-6 lg:py-20">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#1599a8]">Plataforma</p>
                    <h1 class="mt-3 text-4xl font-black leading-tight text-slate-900 lg:text-5xl">
                        Soluções para limpar CPF/CNPJ e aumentar score com atendimento em todo Brasil.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base text-slate-600">
                        A CPF Clean Brasil já atendeu mais de 1.000 clientes e atua com processos de regularização, análise cadastral e recuperação de crédito com foco em resultado.
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="#contato" class="btn-primary">Fale com nosso time</a>
                        <a href="{{ route('regularizacao.index') }}" class="rounded-lg border border-[#8ccfd8] px-4 py-2.5 text-sm font-bold text-[#0e3a4f] hover:bg-white">Ver plataforma</a>
                    </div>
                </div>

                <div class="panel-card p-6">
                    <p class="text-sm font-bold uppercase tracking-wide text-slate-700">Indicadores</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="metric-card metric-soft-blue">
                            <h3>6k+</h3>
                            <p>Empresas atendidas</p>
                        </div>
                        <div class="metric-card metric-soft-green">
                            <h3>60M+</h3>
                            <p>Registros processados</p>
                        </div>
                        <div class="metric-card metric-soft-amber">
                            <h3>99.9%</h3>
                            <p>Disponibilidade</p>
                        </div>
                        <div class="metric-card metric-soft-red">
                            <h3>LGPD</h3>
                            <p>Conformidade ativa</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-[#0d2a41] bg-[#061a2d]">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 lg:grid-cols-[1.2fr_0.8fr] lg:px-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#53e0ef]">Instagram Oficial</p>
                    <h2 class="mt-2 text-2xl font-black text-white">Acompanhe a CPF Clean Brasil nas redes</h2>
                    <p class="mt-3 max-w-2xl text-sm text-slate-300">
                        Conteúdos com dicas para limpar nome, aumentar score e entender o processo de regularização de CPF e CNPJ.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="https://www.instagram.com/cpfclean.brasil/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg bg-[#20b6c7] px-4 py-2 text-sm font-bold text-[#062132] hover:bg-[#1599a8]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2Zm0 1.8A3.95 3.95 0 0 0 3.8 7.75v8.5a3.95 3.95 0 0 0 3.95 3.95h8.5a3.95 3.95 0 0 0 3.95-3.95v-8.5a3.95 3.95 0 0 0-3.95-3.95h-8.5Zm8.95 1.35a1.1 1.1 0 1 1 0 2.2 1.1 1.1 0 0 1 0-2.2ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 1.8A3.2 3.2 0 1 0 12 15.2 3.2 3.2 0 0 0 12 8.8Z"/>
                            </svg>
                            @cpfclean.brasil
                        </a>
                        <a href="https://www.instagram.com/cpfclean.brasil/reels/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg border border-slate-600 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-slate-400 hover:text-white">
                            Ver publicações
                        </a>
                    </div>
                </div>
                <div class="panel-card border border-[#164161]/80 bg-[#0d2a41]/80 p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#53e0ef]">Destaques</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-100">
                        <li>• Limpe seu CPF ou CNPJ</li>
                        <li>• Aumente seu score com orientação especializada</li>
                        <li>• Mais de 1.000 clientes atendidos</li>
                        <li>• Atendimento em todo Brasil</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="solucoes" class="mx-auto max-w-7xl space-y-10 px-4 py-14 lg:px-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900">Validação de Dados e Consulta de Documentos</h2>
                <p class="mt-2 text-sm text-slate-600">Soluções para localizar, validar e enriquecer dados de pessoas físicas e jurídicas.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Localize</p>
                    <h3 class="mt-2 text-lg font-black">Consulta cadastral</h3>
                    <p class="mt-2 text-sm text-slate-600">Acesse e atualize dados com agilidade para melhorar onboarding e reduzir inconsistências.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Dossiê</p>
                    <h3 class="mt-2 text-lg font-black">Investigações jurídicas</h3>
                    <p class="mt-2 text-sm text-slate-600">Centralize consultas patrimoniais e públicas em um único fluxo operacional.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Veículos</p>
                    <h3 class="mt-2 text-lg font-black">Histórico veicular</h3>
                    <p class="mt-2 text-sm text-slate-600">Consulte informações por placa, chassi, CPF ou CNPJ para apoiar decisões comerciais.</p>
                </article>
            </div>

            <div>
                <h2 class="text-2xl font-black text-slate-900">Análise de Crédito e Segurança Cadastral</h2>
                <p class="mt-2 text-sm text-slate-600">Ferramentas para análise de perfil, prevenção a fraudes e formalização segura.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Análise 360</p>
                    <h3 class="mt-2 text-lg font-black">Informações financeiras</h3>
                    <p class="mt-2 text-sm text-slate-600">Visualize score, renda, risco e sinais de inadimplência em uma visão consolidada.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Autentica</p>
                    <h3 class="mt-2 text-lg font-black">Proteção antifraude</h3>
                    <p class="mt-2 text-sm text-slate-600">Valide identidade e documentos com trilhas de auditoria e regras personalizadas.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Assinaturas</p>
                    <h3 class="mt-2 text-lg font-black">Assinatura eletrônica</h3>
                    <p class="mt-2 text-sm text-slate-600">Formalize contratos com segurança, biometria e evidências técnicas.</p>
                </article>
            </div>

            <div>
                <h2 class="text-2xl font-black text-slate-900">Cobrança e Relacionamento</h2>
                <p class="mt-2 text-sm text-slate-600">Automação para régua de cobrança e comunicação multicanal.</p>
            </div>
            <div class="grid gap-4 lg:grid-cols-3">
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Cobrança</p>
                    <h3 class="mt-2 text-lg font-black">Gestão inteligente</h3>
                    <p class="mt-2 text-sm text-slate-600">Priorize carteiras com maior chance de recuperação e acompanhe performance por etapa.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza SMS</p>
                    <h3 class="mt-2 text-lg font-black">Comunicação em massa</h3>
                    <p class="mt-2 text-sm text-slate-600">Envie lembretes, avisos e notificações com monitoramento de entrega e resposta.</p>
                </article>
                <article class="panel-card p-5">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#118ea0]">Regulariza Webphone</p>
                    <h3 class="mt-2 text-lg font-black">Discador inteligente</h3>
                    <p class="mt-2 text-sm text-slate-600">Aumente taxa de contato e produtividade com discagem assistida e relatórios.</p>
                </article>
            </div>
        </section>

        <section id="cases" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-14 lg:px-6">
                <h2 class="text-2xl font-black text-slate-900">Marcas que crescem com a Regulariza</h2>
                <p class="mt-2 text-sm text-slate-600">Junte-se a milhares de empresas que operam com dados confiáveis e processos seguros.</p>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="panel-card p-4 text-center text-sm font-bold text-slate-700">Financeiro</div>
                    <div class="panel-card p-4 text-center text-sm font-bold text-slate-700">Varejo</div>
                    <div class="panel-card p-4 text-center text-sm font-bold text-slate-700">Imobiliário</div>
                    <div class="panel-card p-4 text-center text-sm font-bold text-slate-700">Cobrança</div>
                </div>
            </div>
        </section>

        <section id="contato" class="mx-auto max-w-7xl px-4 py-14 lg:px-6">
            <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Fale com nosso especialista</h2>
                    <p class="mt-3 text-sm text-slate-600">
                        Conheça planos e pacotes sob medida. Receba uma demonstração guiada da plataforma e tire todas as dúvidas sem compromisso.
                    </p>
                    <ul class="mt-4 space-y-2 text-sm text-slate-700">
                        <li>• Diagnóstico rápido do seu cenário</li>
                        <li>• Demonstração prática de funcionalidades</li>
                        <li>• Proposta personalizada por segmento</li>
                    </ul>
                </div>

                <form class="panel-card space-y-3 p-5" method="POST" action="#">
                    <label class="block text-sm font-semibold text-slate-700">Nome completo *</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="Seu nome" required>

                    <label class="block text-sm font-semibold text-slate-700">E-mail corporativo *</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="email" placeholder="nome@empresa.com" required>

                    <label class="block text-sm font-semibold text-slate-700">Celular *</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="tel" placeholder="(00) 00000-0000" required>

                    <label class="block text-sm font-semibold text-slate-700">CNPJ *</label>
                    <input class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" type="text" placeholder="00.000.000/0000-00" required>

                    <label class="mt-2 flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" class="mt-0.5" required>
                        <span>Estou de acordo com a Política de Privacidade e com o tratamento de dados para contato comercial.</span>
                    </label>

                    <button class="btn-primary w-full" type="submit">Receber demonstração gratuita</button>
                    <p class="text-xs text-slate-500">Serviço exclusivo para pessoa jurídica.</p>
                    <div class="pt-2">
                        <img src="{{ asset('assets/selos-seguranca/siteblindado.svg') }}" alt="Site Blindado" class="h-10 w-auto object-contain" />
                    </div>
                </form>
            </div>
        </section>
    </main>

    <footer id="sobre" class="border-t border-slate-200 bg-slate-900 text-slate-300">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 lg:grid-cols-4 lg:px-6">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-white">Plataforma</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>Regulariza Cobrança</li>
                    <li>Regulariza Análise 360</li>
                    <li>Regulariza Assinaturas</li>
                    <li>Regulariza Autentica</li>
                    <li>Regulariza API</li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-white">Institucional</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>Atendimento ao Cliente</li>
                    <li>Sobre Nós</li>
                    <li>Contato</li>
                    <li id="lgpd">LGPD</li>
                    <li>Política de Privacidade</li>
                </ul>
            </div>
            <div id="conteudos">
                <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-white">Conteúdos</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>Blog</li>
                    <li>Cases</li>
                    <li>Parcerias</li>
                    <li>Segmentos</li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-white">Fale Conosco</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>São Paulo: (11) 3197-0719</li>
                    <li>Demais regiões: 0800 878 1179</li>
                    <li>atendimento@regulariza.com.br</li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 text-xs text-slate-400 lg:flex-row lg:items-center lg:justify-between lg:px-6">
                <p>Copyright © 2012-2026 CPF Clean Brasil. Todos os direitos reservados.</p>
                <div class="flex items-center gap-4">
                    <p>CPF Clean Brasil - Atendimento nacional</p>
                    <img src="{{ asset('assets/selos-seguranca/siteblindado.svg') }}" alt="Site Blindado" class="h-8 w-auto object-contain" />
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
