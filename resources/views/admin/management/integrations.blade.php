<x-layouts.app>
    @php
        $maskSecret = static function (?string $value): string {
            $value = trim((string) $value);
            if ($value === '') {
                return 'Não configurado';
            }

            $suffix = mb_substr($value, -4);

            return '••••••••'.$suffix;
        };
    @endphp
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Tela de Integrações</h1>
            <p class="panel-subtitle mt-1">Configure Asaas, API Brasil e Z-API separadamente pela interface administrativa.</p>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/85 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50/85 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão Asaas</h2>
                <p class="mt-2 text-sm">
                    Status:
                    <span class="badge {{ $integrations['asaas']['enabled'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $integrations['asaas']['enabled'] ? 'Configurado' : 'Pendente' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-600">Webhook: <code>{{ $integrations['asaas']['webhook'] }}</code></p>
                <p class="mt-1 text-xs text-slate-600">Base URL: {{ $integrations['asaas']['base_url'] ?: '-' }}</p>
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Gestão API Brasil</h2>
                <p class="mt-2 text-sm">
                    Status:
                    <span class="badge {{ $integrations['apibrasil']['enabled'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $integrations['apibrasil']['enabled'] ? 'Configurado' : 'Pendente' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-600">Base URL: {{ $integrations['apibrasil']['base_url'] ?: '-' }}</p>
                <p class="text-xs text-slate-600">Homologação: {{ $integrations['apibrasil']['homolog'] ? 'Sim' : 'Não' }}</p>
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

        <section class="grid gap-4 xl:grid-cols-3">
            <form method="POST" action="{{ route('admin.management.integrations.update') }}" class="panel-card p-4 space-y-3">
                @csrf
                <input type="hidden" name="integration_group" value="asaas">

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Configurar Asaas</h2>
                    <p class="mt-1 text-xs text-slate-500">Use este bloco para salvar somente a integração de pagamento.</p>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas Base URL</label>
                    <input type="url" name="asaas_base_url" value="{{ old('asaas_base_url', $integrations['asaas']['base_url']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas API Key</label>
                    <input type="password" name="asaas_api_key" value="" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Manter atual: {{ $maskSecret($integrations['asaas']['api_key']) }}" autocomplete="off">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Asaas Webhook Token</label>
                    <input type="password" name="asaas_webhook_token" value="" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Manter atual: {{ $maskSecret($integrations['asaas']['webhook_token']) }}" autocomplete="off">
                </div>

                <button class="btn-primary w-full">Salvar Asaas</button>
            </form>

            <form method="POST" action="{{ route('admin.management.integrations.update') }}" class="panel-card p-4 space-y-3">
                @csrf
                <input type="hidden" name="integration_group" value="apibrasil">

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Configurar API Brasil</h2>
                    <p class="mt-1 text-xs text-slate-500">Catálogo e parâmetros usados nas consultas consolidadas.</p>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">API Brasil Base URL</label>
                    <input type="url" name="apibrasil_base_url" value="{{ old('apibrasil_base_url', $integrations['apibrasil']['base_url']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">API Brasil Token</label>
                    <input type="password" name="apibrasil_token" value="" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Manter atual: {{ $maskSecret($integrations['apibrasil']['token']) }}" autocomplete="off">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">API Brasil Header Token</label>
                    <input type="text" name="apibrasil_token_header" value="{{ old('apibrasil_token_header', $integrations['apibrasil']['token_header']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">API Brasil Prefixo Token</label>
                    <input type="text" name="apibrasil_token_prefix" value="{{ old('apibrasil_token_prefix', $integrations['apibrasil']['token_prefix']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Bearer">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Modo Homologação</label>
                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm text-slate-700">
                        <input type="checkbox" name="apibrasil_homolog" value="1" @checked(old('apibrasil_homolog', $integrations['apibrasil']['homolog']) ? true : false)>
                        Usar dados de homologação (teste)
                    </label>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Path Saldo</label>
                    <input type="text" name="apibrasil_balance_path" value="{{ old('apibrasil_balance_path', $integrations['apibrasil']['balance_path']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="/api/v2/user" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Método Saldo</label>
                    <select name="apibrasil_balance_method" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                        @foreach (['GET', 'POST', 'PUT'] as $method)
                            <option value="{{ $method }}" @selected(old('apibrasil_balance_method', $integrations['apibrasil']['balance_method']) === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Path CPF</label>
                    <input type="text" name="apibrasil_cpf_path" value="{{ old('apibrasil_cpf_path', $integrations['apibrasil']['cpf_path']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="/api/v2/consulta/cpf/credits" required>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Path CNPJ</label>
                    <input type="text" name="apibrasil_cnpj_path" value="{{ old('apibrasil_cnpj_path', $integrations['apibrasil']['cnpj_path']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="/api/v2/consulta/cnpj/credits" required>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Método CPF</label>
                        <select name="apibrasil_cpf_method" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                            @foreach (['GET', 'POST', 'PUT'] as $method)
                                <option value="{{ $method }}" @selected(old('apibrasil_cpf_method', $integrations['apibrasil']['cpf_method']) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Método CNPJ</label>
                        <select name="apibrasil_cnpj_method" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required>
                            @foreach (['GET', 'POST', 'PUT'] as $method)
                                <option value="{{ $method }}" @selected(old('apibrasil_cnpj_method', $integrations['apibrasil']['cnpj_method']) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button class="btn-primary w-full">Salvar API Brasil</button>
            </form>

            <form method="POST" action="{{ route('admin.management.integrations.update') }}" class="panel-card p-4 space-y-3">
                @csrf
                <input type="hidden" name="integration_group" value="zapi">

                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Configurar Z-API</h2>
                    <p class="mt-1 text-xs text-slate-500">Use este bloco para WhatsApp transacional e notificações operacionais.</p>
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Instância</label>
                    <input type="text" name="zapi_instance" value="{{ old('zapi_instance', $integrations['zapi']['instance']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Token</label>
                    <input type="password" name="zapi_token" value="" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Manter atual: {{ $maskSecret($integrations['zapi']['token']) }}" autocomplete="off">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Z-API Client Token</label>
                    <input type="password" name="zapi_client_token" value="" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Manter atual: {{ $maskSecret($integrations['zapi']['client_token']) }}" autocomplete="off">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">WhatsApp padrão CPF Clean</label>
                    <input type="text" name="cpfclean_whatsapp_number" value="{{ old('cpfclean_whatsapp_number', $integrations['zapi']['whatsapp_number']) }}" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Somente números com DDI">
                </div>

                <button class="btn-primary w-full">Salvar Z-API</button>
            </form>
        </section>
    </div>
</x-layouts.app>
