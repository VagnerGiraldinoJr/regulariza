<x-layouts.app>
    <div class="space-y-5">
        <section>
            <h1 class="panel-title">Consultas API Brasil</h1>
            <p class="panel-subtitle mt-1">Consulta de CPF/CNPJ após pagamento e encaminhamento para analista.</p>
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
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-700">Nova consulta</h2>
                <span class="badge {{ $apibrasilConfigured ? 'badge-success' : 'badge-warning' }}">
                    {{ $apibrasilConfigured ? 'Integração configurada' : 'Configure API Brasil em Integrações' }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.management.apibrasil-consultations.store') }}" class="grid gap-3 md:grid-cols-2">
                @csrf
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
                        placeholder="Somente números ou formatado"
                        class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm"
                        required
                        data-document-input
                    >
                </div>
                <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Observações</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 bg-white/70 px-3 py-2 text-sm" placeholder="Contexto da consulta (opcional)">{{ old('notes') }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button class="btn-primary" @disabled(!$apibrasilConfigured)>Consultar API Brasil</button>
                </div>
            </form>
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
                                    @endif

                                    <form method="POST" action="{{ route('admin.management.apibrasil-consultations.forward', $consultation) }}" class="space-y-2">
                                        @csrf
                                        <select name="analyst_user_id" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs">
                                            <option value="">Encaminhar para analista</option>
                                            @foreach ($analysts as $analyst)
                                                <option value="{{ $analyst->id }}" @selected((int) $consultation->analyst_user_id === (int) $analyst->id)>
                                                    {{ $analyst->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-md bg-cyan-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-cyan-700" @disabled($consultation->status !== 'success')>
                                            Encaminhar
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
            const orderSelect = document.querySelector('[data-order-selector]');
            const documentInput = document.querySelector('[data-document-input]');
            if (!orderSelect || !documentInput) {
                return;
            }

            orderSelect.addEventListener('change', function () {
                const selected = orderSelect.options[orderSelect.selectedIndex];
                const document = selected ? (selected.dataset.document || '') : '';
                if (document !== '') {
                    documentInput.value = document;
                }
            });
        })();
    </script>
</x-layouts.app>
