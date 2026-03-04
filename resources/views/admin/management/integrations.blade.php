<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Tela de Integrações</h1>
            <p class="panel-subtitle mt-1">Configure credenciais e parâmetros operacionais do Asaas e Z-API.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/85 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão Asaas</h2>
                <p class="mt-2 text-sm">
                    Status:
                    <span class="badge {{ $integrations['asaas']['enabled'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $integrations['asaas']['enabled'] ? 'Configurado' : 'Pendente' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-600">Webhook: <code>{{ $integrations['asaas']['webhook'] }}</code></p>
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão Z-API</h2>
                <p class="mt-2 text-sm">
                    Status:
                    <span class="badge {{ $integrations['zapi']['enabled'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $integrations['zapi']['enabled'] ? 'Configurado' : 'Pendente' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-600">Instância: {{ $integrations['zapi']['instance'] ?: '-' }}</p>
                <p class="text-xs text-slate-600">Número WhatsApp padrão: {{ $integrations['zapi']['whatsapp_number'] ?: '-' }}</p>
            </div>
        </section>

        <section class="panel-card p-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Editar integrações</h2>
            <form method="POST" action="{{ route('admin.management.integrations.update') }}" class="mt-3 grid gap-3 md:grid-cols-2">
                @csrf

                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas Base URL</label>
                    <input type="url" name="asaas_base_url" value="{{ old('asaas_base_url', $integrations['asaas']['base_url']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas API Key</label>
                    <input type="text" name="asaas_api_key" value="{{ old('asaas_api_key', $integrations['asaas']['api_key']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas Webhook Token</label>
                    <input type="text" name="asaas_webhook_token" value="{{ old('asaas_webhook_token', $integrations['asaas']['webhook_token']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Instância</label>
                    <input type="text" name="zapi_instance" value="{{ old('zapi_instance', $integrations['zapi']['instance']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Token</label>
                    <input type="text" name="zapi_token" value="{{ old('zapi_token', $integrations['zapi']['token']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Client Token</label>
                    <input type="text" name="zapi_client_token" value="{{ old('zapi_client_token', $integrations['zapi']['client_token']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">WhatsApp padrão CPF Clean</label>
                    <input type="text" name="cpfclean_whatsapp_number" value="{{ old('cpfclean_whatsapp_number', $integrations['zapi']['whatsapp_number']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Somente números com DDI">
                </div>
                <div class="md:col-span-2">
                    <button class="btn-primary">Salvar integrações</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
