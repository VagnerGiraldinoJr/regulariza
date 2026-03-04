<x-layouts.app>
    <div class="space-y-5">
        <section><h1 class="panel-title">Tela de Integração</h1></section>
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão Asaas</h2>
                <p class="mt-2 text-sm">Status: <strong>{{ $integrations['asaas']['enabled'] ? 'Configurado' : 'Não configurado' }}</strong></p>
                <p class="text-sm">Base URL: {{ $integrations['asaas']['base_url'] }}</p>
                <p class="text-sm">Webhook: <code>{{ $integrations['asaas']['webhook'] }}</code></p>
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão Z-API</h2>
                <p class="mt-2 text-sm">Status: <strong>{{ $integrations['zapi']['enabled'] ? 'Configurado' : 'Não configurado' }}</strong></p>
                <p class="text-sm">Número padrão: {{ $integrations['zapi']['instance'] ?: '-' }}</p>
            </div>
        </section>
    </div>
</x-layouts.app>
