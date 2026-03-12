<x-layouts.app>
    <div class="space-y-5">
        <div
            class="pointer-events-none fixed inset-0 z-[80] hidden items-center justify-center px-4"
            data-loading-overlay
            aria-hidden="true"
        >
            <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-[4px]"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-[1.75rem] border border-white/35 bg-white/88 p-6 shadow-[0_30px_90px_rgba(7,26,47,0.35)] backdrop-blur-xl">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-cyan-400 via-sky-500 to-cyan-300 opacity-90"></div>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[0.68rem] font-black uppercase tracking-[0.26em] text-cyan-700">Processando análise</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Executando pacote de pesquisas</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600" data-loading-status>
                            Validando fontes e consolidando os dados para gerar o PDF final.
                        </p>
                    </div>
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-700 shadow-inner shadow-cyan-100">
                        <svg class="h-8 w-8 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.18" stroke-width="3"></circle>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="mb-2 flex items-end justify-between gap-3">
                        <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Progresso estimado</span>
                        <span class="text-3xl font-black tracking-tight text-slate-900" data-loading-progress-value>1%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-200/90 shadow-inner">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-sky-500 to-cyan-300 transition-[width] duration-300 ease-out"
                            style="width: 1%;"
                            data-loading-progress-bar
                        ></div>
                    </div>
                </div>

                <div class="mt-5 grid gap-2 text-xs text-slate-500 sm:grid-cols-3">
                    <div class="rounded-2xl bg-white/70 px-3 py-2">Consulta nas fontes</div>
                    <div class="rounded-2xl bg-white/70 px-3 py-2">Consolidação dos dados</div>
                    <div class="rounded-2xl bg-white/70 px-3 py-2">Preparação do PDF</div>
                </div>
            </div>
        </div>

        <section>
            <h1 class="panel-title">Consultas API Brasil</h1>
            <p class="panel-subtitle mt-1">Selecione o tipo de análise, execute o pacote de pesquisas e gere o PDF consolidado para a operação.</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Saldo de Créditos API Brasil</h2>
                @if (is_numeric($balance['balance'] ?? null))
                    <p class="mt-3 text-2xl font-black text-emerald-700">
                        R$ {{ number_format((float) $balance['balance'], 2, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Atualizado automaticamente a cada 45s.
                        @if (($balance['status'] ?? 'error') !== 'success')
                            (saldo recuperado do último retorno da API)
                        @endif
                    </p>
                @else
                    <p class="mt-3 text-sm font-semibold text-amber-700">Não foi possível ler o saldo agora.</p>
                    @if (!empty($balance['error_message']))
                        <p class="mt-1 text-xs text-red-700">{{ \Illuminate\Support\Str::limit((string) $balance['error_message'], 160) }}</p>
                    @endif
                @endif
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Status da Integração</h2>
                <p class="mt-3 text-sm">
                    <span class="badge {{ $apibrasilConfigured ? 'badge-success' : 'badge-warning' }}">
                        {{ $apibrasilConfigured ? 'Configurada' : 'Pendente' }}
                    </span>
                </p>
                <p class="mt-1 text-xs text-slate-500">Valide Base URL, Token e catálogo antes da pesquisa.</p>
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Consultas Registradas</h2>
                <p class="mt-3 text-2xl font-black text-slate-900">{{ number_format((int) $overview['total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Historico operacional consolidado.</p>
            </div>
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Encaminhadas</h2>
                <p class="mt-3 text-2xl font-black text-cyan-700">{{ number_format((int) $overview['forwarded']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Consultas ja distribuidas para analistas.</p>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/85 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50/85 px-4 py-3 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="panel-card p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Nova análise consolidada</h2>
                <span class="badge {{ $apibrasilConfigured ? 'badge-success' : 'badge-warning' }}">
                    {{ $apibrasilConfigured ? 'Integração configurada' : 'Configure API Brasil em Integrações' }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.management.apibrasil-consultations.store') }}" class="grid gap-3 md:grid-cols-2" data-apibrasil-form>
                @csrf
                <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Tipo de análise</label>
                    <select name="report_type" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" required data-report-type-selector>
                        <option value="">Selecionar análise</option>
                        @foreach ($bundles as $key => $bundle)
                            <option value="{{ $key }}" data-document-type="{{ $bundle['document_type'] ?? 'both' }}" @selected(old('report_type') === $key)>
                                {{ $bundle['title'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500">PF executa 2 fontes consolidadas prioritárias. PJ executa o pacote completo da análise empresarial.</p>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Pedido pago (opcional)</label>
                    <select name="order_id" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" data-order-selector>
                        <option value="">Selecionar pedido</option>
                        @foreach ($paidOrders as $order)
                            @php
                                $docValue = (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: '');
                            @endphp
                            <option value="{{ $order->id }}" data-document="{{ preg_replace('/\D+/', '', $docValue) }}" @selected((string) old('order_id') === (string) $order->id)>
                                {{ $order->protocolo }} - {{ $order->user?->name ?? 'Cliente não encontrado' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">CPF/CNPJ</label>
                    <input
                        type="text"
                        name="document_number"
                        value="{{ old('document_number') }}"
                        placeholder="Digite CPF ou CNPJ"
                        class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm"
                        required
                        data-document-input
                    >
                </div>
                <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Observações</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Contexto do dossiê (opcional)">{{ old('notes') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button class="btn-primary inline-flex items-center gap-2" @disabled(!$apibrasilConfigured) data-submit-button>
                        <span data-submit-label>Fazer análise</span>
                        <svg data-submit-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.3" stroke-width="3"></circle>
                            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </section>

        @include('admin.management.partials.research-reports', ['reports' => $reports])

        <section class="grid gap-4 xl:grid-cols-[1.35fr_0.65fr]">
            <div class="panel-card p-4">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Opcoes seguras de manutencao</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <article class="rounded-2xl border border-slate-200 bg-white/80 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Excluir com trilha</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Cada remocao de consulta deixa rastro do admin, pedido, documento e relatorios afetados.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white/80 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Revisao tecnica</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Antes de excluir, confira o JSON, HTTP status e se a consulta ja foi encaminhada.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-white/80 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Visao de limpeza</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Use filtros por sucesso ou erro para higienizar lotes errados sem perder rastreabilidade.</p>
                    </article>
                </div>
            </div>

            <div class="panel-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Ultimas exclusoes auditadas</h2>
                    <a href="{{ route('admin.management.audit-logs', ['action' => 'consultation_deleted']) }}" class="text-xs font-semibold text-cyan-700 hover:text-cyan-800">
                        Abrir auditoria
                    </a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($recentAuditLogs as $auditLog)
                        <article class="rounded-2xl border border-slate-200 bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">{{ $auditLog->created_at?->format('d/m/Y H:i') }}</p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">{{ $auditLog->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $auditLog->admin?->name ?: 'Sistema' }}
                                @if ($auditLog->target_label)
                                    • {{ $auditLog->target_label }}
                                @endif
                            </p>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">Nenhuma exclusao auditada registrada.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Histórico de consultas</h2>
                <form method="GET" action="{{ route('admin.management.apibrasil-consultations') }}" class="flex items-center gap-2">
                    <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Todos os status</option>
                        <option value="success" @selected($status === 'success')>Sucesso</option>
                        <option value="error" @selected($status === 'error')>Erro</option>
                    </select>
                    <button class="btn-primary text-xs">Filtrar</button>
                    <a href="{{ route('admin.management.apibrasil-consultations') }}" class="btn-dark text-xs">Limpar</a>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Quando</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Pedido</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Analista</th>
                            <th class="px-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consultations as $consultation)
                            <tr class="border-t border-slate-100 align-top">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $consultation->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $consultation->consultation_title ?: 'Consulta API Brasil' }}
                                    </div>
                                    <div class="font-semibold text-slate-800">{{ $consultation->document_type === 'cnpj' ? 'CNPJ' : 'CPF' }}</div>
                                    <div class="text-xs text-slate-600">{{ $consultation->document_number }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($consultation->order)
                                        <div class="font-semibold text-slate-700">{{ $consultation->order->protocolo }}</div>
                                        <div class="text-xs text-slate-500">{{ $consultation->user?->name }}</div>
                                    @else
                                        <span class="text-xs text-slate-500">Sem vínculo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $consultation->status === 'success' ? 'badge-success' : 'badge-warning' }}">
                                        {{ $consultation->status === 'success' ? 'Sucesso' : 'Erro' }}
                                    </span>
                                    @if ($consultation->http_status)
                                        <div class="mt-1 text-xs text-slate-500">HTTP {{ $consultation->http_status }}</div>
                                    @endif
                                    @if ($consultation->error_message)
                                        <div class="mt-1 max-w-xs text-xs text-red-700">{{ \Illuminate\Support\Str::limit($consultation->error_message, 140) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($consultation->analyst)
                                        <div class="font-semibold text-slate-700">{{ $consultation->analyst->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $consultation->forwarded_at?->format('d/m/Y H:i') }}</div>
                                    @else
                                        <span class="text-xs text-slate-500">Não encaminhado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($consultation->status === 'success')
                                        <details class="mb-2 rounded border border-slate-200 bg-white p-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-700">Ver retorno JSON</summary>
                                            <pre class="mt-2 max-h-56 overflow-auto rounded bg-slate-900/95 p-2 text-[11px] text-emerald-100">{{ json_encode($consultation->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                        @if ($consultation->order)
                                            <a href="{{ route('admin.management.apibrasil-consultations.order-pdf', $consultation->order) }}" data-no-transition class="mb-2 inline-flex rounded-md bg-slate-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                Dossiê do pedido ({{ $consultationCountByOrder[$consultation->order_id] ?? 1 }} consultas)
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.management.apibrasil-consultations.pdf', $consultation) }}" data-no-transition class="mb-2 inline-flex rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                            Baixar PDF
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('admin.management.apibrasil-consultations.forward', $consultation) }}" class="space-y-2">
                                        @csrf
                                        <select name="analyst_user_id" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs" @disabled($consultation->status !== 'success' || $consultation->forwarded_at !== null)>
                                            <option value="">Encaminhar para analista</option>
                                            @foreach ($analysts as $analyst)
                                                <option value="{{ $analyst->id }}" @selected((int) $consultation->analyst_user_id === (int) $analyst->id)>
                                                    {{ $analyst->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-md bg-cyan-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700 disabled:cursor-not-allowed disabled:opacity-60" @disabled($consultation->status !== 'success' || $consultation->forwarded_at !== null)>
                                            {{ $consultation->forwarded_at ? 'Encaminhado' : 'Encaminhar' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.management.apibrasil-consultations.destroy', $consultation) }}" class="mt-2" onsubmit="return confirm('Excluir esta consulta? A acao sera auditada e os relatórios vinculados perderao apenas este item.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Excluir com log
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Sem consultas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $consultations->links() }}
            </div>
        </section>
    </div>

    <script>
        (function () {
            const reportTypeSelect = document.querySelector('[data-report-type-selector]');
            const orderSelect = document.querySelector('[data-order-selector]');
            const documentInput = document.querySelector('[data-document-input]');
            const form = document.querySelector('[data-apibrasil-form]');
            const submitButton = document.querySelector('[data-submit-button]');
            const submitLabel = document.querySelector('[data-submit-label]');
            const submitSpinner = document.querySelector('[data-submit-spinner]');
            const loadingOverlay = document.querySelector('[data-loading-overlay]');
            const loadingProgressValue = document.querySelector('[data-loading-progress-value]');
            const loadingProgressBar = document.querySelector('[data-loading-progress-bar]');
            const loadingStatus = document.querySelector('[data-loading-status]');

            if (!documentInput) {
                return;
            }

            let loadingTimer = null;
            let loadingProgress = 1;

            const loadingSteps = [
                { until: 28, text: 'Conectando com a API Brasil e validando os parâmetros da consulta.' },
                { until: 61, text: 'Executando as fontes selecionadas e organizando os retornos recebidos.' },
                { until: 87, text: 'Consolidando o conteúdo do dossiê para preparar a visualização final.' },
                { until: 100, text: 'Finalizando a montagem do relatório e liberando o PDF para download.' },
            ];

            const setLoadingProgress = (value) => {
                loadingProgress = Math.max(1, Math.min(100, value));

                if (loadingProgressValue) {
                    loadingProgressValue.textContent = `${loadingProgress}%`;
                }

                if (loadingProgressBar) {
                    loadingProgressBar.style.width = `${loadingProgress}%`;
                }

                if (loadingStatus) {
                    const activeStep = loadingSteps.find((step) => loadingProgress <= step.until) || loadingSteps[loadingSteps.length - 1];
                    loadingStatus.textContent = activeStep.text;
                }
            };

            const startLoadingOverlay = () => {
                if (!loadingOverlay) {
                    return;
                }

                setLoadingProgress(1);
                loadingOverlay.classList.remove('hidden');
                loadingOverlay.classList.add('flex', 'pointer-events-auto');
                loadingOverlay.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                if (loadingTimer) {
                    window.clearInterval(loadingTimer);
                }

                loadingTimer = window.setInterval(function () {
                    if (loadingProgress < 84) {
                        setLoadingProgress(loadingProgress + 3);
                        return;
                    }

                    if (loadingProgress < 96) {
                        setLoadingProgress(loadingProgress + 1);
                    }
                }, 180);
            };

            const finishLoadingOverlay = () => {
                if (!loadingOverlay) {
                    return;
                }

                if (loadingTimer) {
                    window.clearInterval(loadingTimer);
                    loadingTimer = null;
                }

                setLoadingProgress(100);
            };

            const onlyDigits = (value) => (value || '').replace(/\D+/g, '');

            const formatCpf = (value) => {
                const digits = onlyDigits(value).slice(0, 11);
                return digits
                    .replace(/^(\d{3})(\d)/, '$1.$2')
                    .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1-$2');
            };

            const formatCnpj = (value) => {
                const digits = onlyDigits(value).slice(0, 14);
                return digits
                    .replace(/^(\d{2})(\d)/, '$1.$2')
                    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            };

            const selectedDocumentType = () => {
                if (!reportTypeSelect) {
                    return 'both';
                }
                const selected = reportTypeSelect.options[reportTypeSelect.selectedIndex];
                return selected ? (selected.dataset.documentType || 'both') : 'both';
            };

            const applyMask = () => {
                const raw = documentInput.value;
                const digits = onlyDigits(raw);
                const type = selectedDocumentType();
                if (type === 'cpf') {
                    documentInput.value = formatCpf(digits);
                    documentInput.placeholder = '000.000.000-00';
                    return;
                }
                if (type === 'cnpj') {
                    documentInput.value = formatCnpj(digits);
                    documentInput.placeholder = '00.000.000/0000-00';
                    return;
                }
                documentInput.value = digits.length > 11 ? formatCnpj(digits) : formatCpf(digits);
                documentInput.placeholder = 'CPF: 000.000.000-00 ou CNPJ: 00.000.000/0000-00';
            };

            if (reportTypeSelect) {
                reportTypeSelect.addEventListener('change', applyMask);
            }

            documentInput.addEventListener('input', applyMask);

            if (orderSelect) {
                orderSelect.addEventListener('change', function () {
                    const selected = orderSelect.options[orderSelect.selectedIndex];
                    const document = selected ? (selected.dataset.document || '') : '';
                    if (document !== '') {
                        documentInput.value = document;
                        applyMask();
                    }
                });
            }

            if (form && submitButton && submitLabel && submitSpinner) {
                form.addEventListener('submit', function () {
                    submitButton.setAttribute('disabled', 'disabled');
                    submitSpinner.classList.remove('hidden');
                    submitLabel.textContent = 'Executando pacote de pesquisas...';
                    startLoadingOverlay();
                });
            }

            window.addEventListener('beforeunload', finishLoadingOverlay);
            window.addEventListener('pagehide', finishLoadingOverlay);

            if (orderSelect && orderSelect.value) {
                const selected = orderSelect.options[orderSelect.selectedIndex];
                const document = selected ? (selected.dataset.document || '') : '';
                if (document !== '') {
                    documentInput.value = document;
                }
            }

            applyMask();
        })();
    </script>
</x-layouts.app>
