<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPF Clean Brasil</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/cpfclean-logo.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <link rel="preload" as="image" href="{{ asset('assets/backgrounds/premium-lines.jpg') }}">
    <style>
        html, body {
            min-height: 100%;
            margin: 0;
            background:
                linear-gradient(180deg, rgba(229, 235, 241, 0.84), rgba(229, 235, 241, 0.84)),
                url('{{ asset('assets/backgrounds/premium-lines.jpg') }}'),
                #e5ebf1;
            background-size: cover, cover, auto;
            background-position: center, center, 0 0;
            background-attachment: fixed, fixed, scroll;
            color: #102235;
        }

        .app-shell::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(240, 245, 250, 0.78) 0%, rgba(229, 236, 245, 0.8) 100%),
                url('{{ asset('assets/backgrounds/premium-lines.jpg') }}');
            background-size: cover, cover;
            background-position: center;
            pointer-events: none;
            z-index: 0;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="page-entering">
    @auth
        @php
            $authUser = auth()->user();
            $hasPaidOrder = $authUser->orders()->where('pagamento_status', 'pago')->exists();
            $isDemoCliente = $authUser->email === 'cliente@regulariza.local';
            $showReferralNav = $authUser->role === 'cliente' && ($hasPaidOrder || $isDemoCliente);
            $referralCode = $showReferralNav ? $authUser->ensureReferralCode() : null;
            $referralLink = $referralCode ? route('regularizacao.index', ['indicacao' => $referralCode]) : null;
            $referralCredits = (float) $authUser->referral_credits;
            $isAnalystRole = in_array($authUser->role, ['analista', 'vendedor'], true);
            $isBackofficeRole = in_array($authUser->role, ['admin', 'atendente'], true);
            $serverNow = now();
            $serverCity = (string) config('app.server_city', 'São Paulo');
            $serverUf = (string) config('app.server_uf', 'SP');
            $serverCountry = (string) config('app.server_country', 'Brasil');
        @endphp

        <div id="app-shell" class="app-shell">
            <aside id="app-sidebar" class="hidden lg:flex lg:flex-col app-sidebar">
                <div class="app-brand border-b border-white/10 px-5 py-4">
                    <p class="brand-pulse text-xs font-semibold uppercase tracking-[0.24em] text-blue-200/90 sidebar-label">CPF Clean Brasil</p>
                    <h1 class="mt-1 text-2xl font-black text-white sidebar-label">Central de Controle</h1>
                    <p class="mt-1 text-xs text-cyan-200/80 sidebar-label">Operação Premium</p>
                </div>

                <nav class="app-sidebar-nav flex-1 space-y-1 px-3 py-4">
                    @if ($authUser->role === 'cliente')
                        <a href="{{ route('portal.dashboard') }}" class="app-nav-link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12 12 4l9 8"/><path d="M5 10v10h14V10"/></svg>
                            <span class="sidebar-label">Dashboard</span>
                        </a>
                        <a href="{{ route('portal.contracts') }}" class="app-nav-link {{ request()->routeIs('portal.contracts') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10l3 3v13H4V4h3z"/><path d="M14 4v4h4"/></svg>
                            <span class="sidebar-label">Meus Contratos</span>
                        </a>
                        <a href="{{ route('portal.timeline') }}" class="app-nav-link {{ request()->routeIs('portal.timeline') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 5v14"/><path d="M12 5v14"/><path d="M19 5v14"/><circle cx="5" cy="9" r="2"/><circle cx="12" cy="15" r="2"/><circle cx="19" cy="11" r="2"/></svg>
                            <span class="sidebar-label">Timeline</span>
                        </a>
                        <a href="{{ route('portal.analyst-chat') }}" class="app-nav-link {{ request()->routeIs('portal.analyst-chat*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 18H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-9l-5 3v-3z"/></svg>
                            <span class="sidebar-label">Mensageria Analista</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="app-nav-link {{ request()->routeIs('profile.*') || request()->routeIs('portal.profile*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4-5 8-5s6.5 1.5 8 5"/></svg>
                            <span class="sidebar-label">Meus Dados</span>
                        </a>
                        <a href="{{ route('portal.tickets.index') }}" class="app-nav-link {{ request()->routeIs('portal.tickets.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M9 10h6M9 14h4"/></svg>
                            <span class="sidebar-label">SAC</span>
                        </a>
                    @endif

                    @if ($isAnalystRole)
                        <a href="{{ route('analyst.dashboard') }}" class="app-nav-link {{ request()->routeIs('analyst.dashboard') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12 12 4l9 8"/><path d="M5 10v10h14V10"/></svg>
                            <span class="sidebar-label">Dashboard</span>
                        </a>
                        <a href="{{ route('admin.orders.index') }}" class="app-nav-link {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/></svg>
                            <span class="sidebar-label">Pedidos</span>
                        </a>
                        <a href="{{ route('admin.tickets.index') }}" class="app-nav-link {{ request()->routeIs('admin.tickets.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M9 10h6M9 14h4"/></svg>
                            <span class="sidebar-label">SAC</span>
                        </a>
                        <a href="{{ route('analyst.contracts') }}" class="app-nav-link {{ request()->routeIs('analyst.contracts') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10l3 3v13H4V4h3z"/><path d="M14 4v4h4"/></svg>
                            <span class="sidebar-label">Contratos</span>
                        </a>
                        <a href="{{ route('analyst.clients') }}" class="app-nav-link {{ request()->routeIs('analyst.clients') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/><path d="M11 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/></svg>
                            <span class="sidebar-label">Carteira</span>
                        </a>
                        <a href="{{ route('analyst.commissions') }}" class="app-nav-link {{ request()->routeIs('analyst.commissions') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M9 10h6a2 2 0 1 1 0 4H9"/><path d="M12 8v8"/></svg>
                            <span class="sidebar-label">Comissões</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="app-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4-5 8-5s6.5 1.5 8 5"/></svg>
                            <span class="sidebar-label">Meu Perfil</span>
                        </a>
                    @endif

                    @if ($isBackofficeRole)
                        <a href="{{ route('admin.orders.index') }}" class="app-nav-link {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/></svg>
                            <span class="sidebar-label">Pedidos</span>
                        </a>
                        <a href="{{ route('admin.tickets.index') }}" class="app-nav-link {{ request()->routeIs('admin.tickets.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M9 10h6M9 14h4"/></svg>
                            <span class="sidebar-label">SAC</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="app-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4-5 8-5s6.5 1.5 8 5"/></svg>
                            <span class="sidebar-label">Meu Perfil</span>
                        </a>

                        @if ($authUser->role === 'admin')
                            <a href="{{ route('admin.management.dashboard') }}" class="app-nav-link {{ request()->routeIs('admin.management.dashboard') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5"/><path d="M20 19H4"/><path d="M7 14l3-3 3 2 4-5"/></svg>
                                <span class="sidebar-label">Dashboard</span>
                            </a>
                            <a href="{{ route('admin.contracts.index') }}" class="app-nav-link {{ request()->routeIs('admin.contracts.*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 4h10l3 3v13H4V4h3z"/><path d="M14 4v4h4"/></svg>
                                <span class="sidebar-label">Contratos</span>
                            </a>
                            <a href="{{ route('admin.management.contract-payments') }}" class="app-nav-link {{ request()->routeIs('admin.management.contract-payments') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/></svg>
                                <span class="sidebar-label">Controle de Pagamentos</span>
                            </a>
                            <a href="{{ route('admin.management.commissions') }}" class="app-nav-link {{ request()->routeIs('admin.management.commissions') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M9 10h6a2 2 0 1 1 0 4H9"/><path d="M12 8v8"/></svg>
                                <span class="sidebar-label">Controle de Comissões</span>
                            </a>
                            <a href="{{ route('admin.management.payout-requests') }}" class="app-nav-link {{ request()->routeIs('admin.management.payout-requests') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12h18"/><path d="M12 3v18"/></svg>
                                <span class="sidebar-label">Solicitações PIX</span>
                            </a>
                            <a href="{{ route('admin.vendors.index') }}" class="app-nav-link {{ request()->routeIs('admin.vendors.*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 20h18"/><path d="M6 20V8l6-4 6 4v12"/></svg>
                                <span class="sidebar-label">Vendedores</span>
                            </a>
                            <a href="{{ route('admin.management.vendors') }}" class="app-nav-link {{ request()->routeIs('admin.management.vendors*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/><path d="M11 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/></svg>
                                <span class="sidebar-label">Cadastro Analista</span>
                            </a>
                            <a href="{{ route('admin.management.users') }}" class="app-nav-link {{ request()->routeIs('admin.management.users*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="3"/><circle cx="16" cy="8" r="3"/><path d="M3 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/><path d="M11 20c.8-3 2.7-4.5 5-4.5s4.2 1.5 5 4.5"/></svg>
                                <span class="sidebar-label">Usuários</span>
                            </a>
                            <a href="{{ route('admin.management.messages') }}" class="app-nav-link {{ request()->routeIs('admin.management.messages') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M8 10h8M8 14h6"/></svg>
                                <span class="sidebar-label">Mensagens Enviadas</span>
                            </a>
                            <a href="{{ route('admin.management.audit-logs') }}" class="app-nav-link {{ request()->routeIs('admin.management.audit-logs') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l7 4v5c0 5-3.2 7.7-7 9-3.8-1.3-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                                <span class="sidebar-label">Auditoria</span>
                            </a>
                            <a href="{{ route('admin.management.integrations') }}" class="app-nav-link {{ request()->routeIs('admin.management.integrations') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 12h6"/><path d="M14 12h6"/><circle cx="10" cy="12" r="2"/><circle cx="14" cy="12" r="2"/></svg>
                                <span class="sidebar-label">Integrações</span>
                            </a>
                            <a href="{{ route('admin.management.apibrasil-consultations') }}" class="app-nav-link {{ request()->routeIs('admin.management.apibrasil-consultations*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M8 9h8"/><path d="M8 13h4"/></svg>
                                <span class="sidebar-label">Consultas API Brasil</span>
                            </a>
                            <a href="{{ route('admin.finance.dashboard') }}" class="app-nav-link {{ request()->routeIs('admin.finance.*') ? 'is-active' : '' }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5"/><path d="M20 19H4"/><path d="M7 15l3-3 3 2 4-5"/></svg>
                                <span class="sidebar-label">Financeiro</span>
                            </a>
                            <a href="/horizon" class="app-nav-link">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                <span class="sidebar-label">Horizon</span>
                            </a>
                        @endif
                    @endif
                </nav>

                <div class="app-sidebar-user border-t border-white/10 px-4 py-4">
                    @include('components.security-audit-badge', ['inline' => true])
                </div>
            </aside>

            <div class="min-w-0 app-main-zone">
                <header class="app-topbar">
                    <div class="flex items-center justify-between px-4 py-3 lg:px-6">
                        <div class="flex items-center gap-3">
                            <button type="button" id="sidebar-toggle" class="hidden lg:inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:border-cyan-300 hover:text-cyan-700" aria-label="Recolher menu">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 6l-6 6 6 6"/></svg>
                            </button>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Sistema CPF Clean Brasil</p>
                                <p class="text-xs text-slate-500">Operação comercial e atendimento premium</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if (in_array($authUser->role, ['admin', 'atendente', 'analista', 'vendedor'], true))
                                <a href="{{ route('regularizacao.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Funil</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn-dark text-xs">Sair</button>
                            </form>
                        </div>
                    </div>
                    @if ($showReferralNav && $referralCode && $referralLink)
                        <div class="border-t border-slate-200 bg-[#f7fdff] px-4 py-2 lg:px-6">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="font-bold text-[#0f4d57]">INDICAÇÃO:</span>
                                <button type="button" class="rounded bg-white px-2 py-1 font-bold text-[#0f4d57] border border-[#b7e9ee]" data-copy-ref="{{ $referralLink }}">
                                    {{ $referralCode }}
                                </button>
                                <a href="{{ $referralLink }}" class="font-semibold text-[#0f6c78] hover:text-[#0b4f58]" target="_blank" rel="noopener noreferrer">
                                    INDIQUE E GANHE CRÉDITOS
                                </a>
                                <span class="rounded-md bg-[#d8f5e6] px-2 py-1 font-bold text-[#0f8f53]">
                                    R$ {{ number_format($referralCredits, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </header>

                @if (session('access_error'))
                    <div class="px-4 pt-4 lg:px-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('access_error') }}</div>
                    </div>
                @endif

                <main class="flex-1 px-4 py-5 lg:px-6">
                    {{ $slot }}
                </main>

                <footer class="app-footer-wrap px-4 pb-3 lg:px-6">
                    <div class="app-footer">
                        <div class="space-y-1">
                            <div class="app-footer-line">
                                <span class="status-dot-online"></span>
                                <span>Status do sistema: Ativo</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Servidor: {{ $serverNow->format('d/m/Y H:i:s') }} • {{ $serverCity }} - {{ $serverUf }} • {{ $serverCountry }}</p>
                        </div>
                        <div class="app-footer-line app-footer-copy">
                            <span>© {{ now()->format('Y') }} Desenvolvido por 27.674.876/0001-70</span>
                            <a href="https://vgit.com.br/" target="_blank" rel="noopener noreferrer">vgit.com.br</a>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    @else
        <main>
            {{ $slot }}
        </main>
        @include('components.security-audit-badge')
        @include('components.public-whatsapp-widget')
    @endauth

    <script>
        (function () {
            const shell = document.getElementById('app-shell');
            const toggle = document.getElementById('sidebar-toggle');
            const storageKey = 'cpfclean.sidebar.collapsed';

            if (shell && toggle && window.innerWidth >= 1024) {
                if (window.localStorage.getItem(storageKey) === '1') {
                    shell.classList.add('sidebar-collapsed');
                }

                toggle.addEventListener('click', function () {
                    shell.classList.toggle('sidebar-collapsed');
                    const collapsed = shell.classList.contains('sidebar-collapsed');
                    window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
                });
            }

            document.querySelectorAll('[data-copy-ref]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const text = button.getAttribute('data-copy-ref') || '';
                    if (!text) {
                        return;
                    }

                    navigator.clipboard.writeText(text).then(function () {
                        const original = button.textContent;
                        button.textContent = 'COPIADO';
                        setTimeout(function () {
                            button.textContent = original;
                        }, 1200);
                    });
                });
            });
        })();
    </script>
</body>
</html>
