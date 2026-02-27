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
<body>
    @auth
        <div class="min-h-screen lg:grid lg:grid-cols-[250px_1fr]">
            <aside class="hidden lg:flex lg:flex-col" style="background: var(--sidebar-bg);">
                <div class="border-b border-white/10 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-200/90">CPF Clean Brasil</p>
                    <h1 class="mt-1 text-lg font-bold text-white">Painel Interno</h1>
                </div>

                <div class="flex-1 space-y-1 px-3 py-4">
                    <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">Dashboard</a>

                    @if (auth()->user()->role === 'cliente')
                        <a href="{{ route('portal.dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('portal.dashboard') ? 'bg-[var(--sidebar-active)] text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">Meus Pedidos</a>
                        <a href="{{ route('portal.tickets.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('portal.tickets.*') ? 'bg-[var(--sidebar-active)] text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">Suporte (SAC)</a>
                    @endif

                    @if (in_array(auth()->user()->role, ['admin', 'atendente'], true))
                        <a href="{{ route('admin.orders.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.orders.*') ? 'bg-[var(--sidebar-active)] text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">Pedidos</a>
                        <a href="{{ route('admin.tickets.index') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.tickets.*') ? 'bg-[var(--sidebar-active)] text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">SAC</a>
                    @endif

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.finance.dashboard') }}" class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.finance.*') ? 'bg-[var(--sidebar-active)] text-white' : 'text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white' }}">Financeiro</a>
                        <a href="/horizon" class="block rounded-md px-3 py-2 text-sm font-medium text-[var(--sidebar-muted)] hover:bg-white/10 hover:text-white">Horizon</a>
                    @endif
                </div>

                <div class="border-t border-white/10 px-4 py-4">
                    <p class="text-xs text-[var(--sidebar-muted)]">{{ auth()->user()->name }}</p>
                    <p class="text-xs uppercase tracking-wide text-blue-200">{{ auth()->user()->role }}</p>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="border-b border-slate-200 bg-[var(--topbar-bg)]">
                    <div class="flex items-center justify-between px-4 py-3 lg:px-6">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Sistema CPF Clean Brasil</p>
                            <p class="text-xs text-slate-500">Recuperação de crédito e atendimento SAC</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('regularizacao.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Funil</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn-dark text-xs">Sair</button>
                            </form>
                        </div>
                    </div>
                </header>

                @if (session('access_error'))
                    <div class="px-4 pt-4 lg:px-6">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('access_error') }}</div>
                    </div>
                @endif

                <main class="px-4 py-5 lg:px-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        <main>
            {{ $slot }}
        </main>
        @include('components.public-whatsapp-widget')
    @endauth
</body>
</html>
