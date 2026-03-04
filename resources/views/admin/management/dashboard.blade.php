<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Painel do Administrador</h1>
            <p class="panel-subtitle mt-1">Visão geral operacional, financeira e de atendimento.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div class="metric-card metric-soft-blue"><h3>{{ $stats['orders_total'] }}</h3><p>Contratos/Pedidos</p></div>
            <div class="metric-card metric-soft-green"><h3>{{ $stats['orders_paid'] }}</h3><p>Pagamentos confirmados</p></div>
            <div class="metric-card metric-soft-amber"><h3>{{ $stats['commissions_pending'] }}</h3><p>Comissões pendentes/liberadas</p></div>
            <div class="metric-card metric-soft-red"><h3>{{ $stats['sac_open'] }}</h3><p>Tickets SAC em aberto</p></div>
            <div class="metric-card metric-soft-blue"><h3>{{ $stats['leads_unassigned'] }}</h3><p>Leads sem vendedor</p></div>
            <div class="metric-card metric-soft-amber"><h3>{{ $stats['messages_pending'] }}</h3><p>WhatsApp pendentes</p></div>
        </section>

        <section class="panel-card p-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <a class="btn-primary text-center" href="{{ route('admin.contracts.index') }}">Módulo de contratos</a>
            <a class="btn-primary text-center" href="{{ route('admin.management.contract-payments') }}">Controle de pagamentos</a>
            <a class="btn-primary text-center" href="{{ route('admin.management.commissions') }}">Controle de comissões</a>
            <a class="btn-primary text-center" href="{{ route('admin.management.payout-requests') }}">Solicitações de saque PIX</a>
            <a class="btn-primary text-center" href="{{ route('admin.finance.dashboard') }}">Dashboard financeiro</a>
            <a class="btn-dark text-center" href="{{ route('admin.management.integrations') }}">Tela de integrações</a>
            <a class="btn-dark text-center" href="{{ route('admin.management.messages') }}">Mensagens enviadas/pendentes</a>
            <a class="btn-dark text-center" href="{{ route('admin.management.orphan-leads') }}">Leads sem vendedor</a>
            <a class="btn-dark text-center" href="{{ route('admin.management.users') }}">Usuários admin/analista</a>
            <a class="btn-dark text-center" href="{{ route('admin.management.clients') }}">Cadastro de clientes</a>
            <a class="btn-dark text-center" href="{{ route('admin.tickets.index') }}">SAC</a>
        </section>

        <section class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Dados de demonstração</h2>
            <p class="panel-subtitle mt-1">Gera dados fake para validar comportamento dos painéis e remove tudo com segurança quando terminar.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.management.fake-data.generate') }}">
                    @csrf
                    <button type="submit" class="btn-primary">Gerar dados fake</button>
                </form>
                <form method="POST" action="{{ route('admin.management.fake-data.clear') }}" onsubmit="return confirm('Remover todos os dados FAKE agora?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-dark">Limpar dados fake</button>
                </form>
            </div>
        </section>
    </div>
</x-layouts.app>
