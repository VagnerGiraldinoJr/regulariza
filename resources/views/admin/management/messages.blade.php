<x-layouts.app>
    <div class="space-y-5">
        <section class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="panel-title">Trilha de Disparo WhatsApp</h1>
                <p class="panel-subtitle mt-1">Acompanhe o funil de mensagens, filtre falhas rapidamente e remova registros com auditoria.</p>
            </div>
            <a href="{{ route('admin.management.audit-logs', ['action' => 'whatsapp_log_deleted']) }}" class="btn-dark text-xs">
                Ver auditoria de exclusoes
            </a>
        </section>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50/85 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Total</p>
                <p class="mt-3 text-3xl font-black text-slate-900">{{ number_format((int) $stats['total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Todos os disparos registrados.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Enviados</p>
                <p class="mt-3 text-3xl font-black text-emerald-700">{{ number_format((int) $stats['enviado']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Mensagens com resposta positiva da Z-API.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-rose-700">Falhas</p>
                <p class="mt-3 text-3xl font-black text-rose-700">{{ number_format((int) $stats['falhou']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Priorize revisao de token, numero e template.</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">Ultimas 24h</p>
                <p class="mt-3 text-3xl font-black text-cyan-700">{{ number_format((int) $stats['ultimas_24h']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Movimento recente da regua e do onboarding.</p>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.3fr_0.7fr]">
            <div class="panel-card p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-[0.18em] text-slate-700">Regua operacional</h2>
                        <p class="mt-1 text-sm text-slate-500">Os eventos abaixo mostram o caminho padrao do relacionamento por WhatsApp.</p>
                    </div>
                    <span class="badge badge-success">Operacao rastreada</span>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ([
                        'boas_vindas' => 'Entrada apos pagamento e boas-vindas ao cliente',
                        'portal_acesso' => 'Credenciais da area do cliente com link, login e senha provisoria',
                        'contrato_aceite' => 'Envio do link para revisar e aceitar o contrato',
                        'status_atualizado' => 'Atualizacao manual ou automatica de etapa',
                        'lembrete' => 'Cobrança, pendencias ou follow-up',
                        'conclusao' => 'Encerramento do caso e proxima acao',
                    ] as $trailEvent => $trailDescription)
                        <article class="rounded-2xl border border-slate-200 bg-white/80 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-600">{{ str_replace('_', ' ', $trailEvent) }}</p>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-700">
                                    {{ number_format((int) ($eventCounts[$trailEvent] ?? 0)) }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $trailDescription }}</p>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="panel-card p-4">
                <h2 class="text-sm font-black uppercase tracking-[0.18em] text-slate-700">Ultimas exclusoes auditadas</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($recentAuditLogs as $auditLog)
                        <article class="rounded-2xl border border-slate-200 bg-white/80 p-3">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                                {{ $auditLog->created_at?->format('d/m/Y H:i') }}
                            </p>
                            <p class="mt-2 text-sm font-semibold text-slate-800">{{ $auditLog->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $auditLog->admin?->name ?: 'Sistema' }}
                                @if ($auditLog->target_label)
                                    • {{ $auditLog->target_label }}
                                @endif
                            </p>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">Nenhuma exclusao auditada por enquanto.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel-card p-4">
            <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1.2fr_0.7fr_0.7fr_auto_auto]" method="GET">
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Busca</label>
                    <input
                        type="text"
                        name="busca"
                        value="{{ $search }}"
                        placeholder="Cliente, pedido, telefone ou trecho da mensagem"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
                    >
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Status</label>
                    <select name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach (['enviado', 'pendente', 'falhou'] as $messageStatus)
                            <option value="{{ $messageStatus }}" @selected($status === $messageStatus)>{{ ucfirst($messageStatus) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold uppercase tracking-wide text-slate-600">Evento</label>
                    <select name="evento" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @foreach (['boas_vindas', 'portal_acesso', 'contrato_aceite', 'status_atualizado', 'lembrete', 'conclusao'] as $messageEvent)
                            <option value="{{ $messageEvent }}" @selected($event === $messageEvent)>{{ str_replace('_', ' ', $messageEvent) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary self-end">Filtrar</button>
                <a href="{{ route('admin.management.messages') }}" class="btn-dark self-end text-center">Limpar</a>
            </form>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Quando</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Trilha</th>
                            <th class="px-4 py-3">Conteudo</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr class="border-t border-slate-100 align-top">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-slate-700">{{ $message->created_at?->format('d/m/Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">Envio: {{ $message->enviado_em?->format('d/m/Y H:i') ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ $message->user?->name ?: 'Cliente nao vinculado' }}</div>
                                    <div class="text-xs text-slate-500">{{ $message->user?->email ?: 'Sem e-mail' }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Telefone: {{ $message->telefone }}</div>
                                    <div class="text-xs text-slate-500">Pedido: {{ $message->order?->protocolo ?: ($message->order_id ? '#'.$message->order_id : 'Sem pedido') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $message->status === 'falhou' ? 'badge-warning' : 'badge-success' }}">
                                        {{ str_replace('_', ' ', $message->evento) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-md text-sm leading-6 text-slate-700">{{ \Illuminate\Support\Str::limit((string) $message->mensagem, 180) }}</p>
                                    @if (!empty($message->zapi_response))
                                        <details class="mt-2 rounded-xl border border-slate-200 bg-slate-950/95 p-2">
                                            <summary class="cursor-pointer text-xs font-semibold text-emerald-100">Ver retorno tecnico</summary>
                                            <pre class="mt-2 max-h-52 overflow-auto text-[11px] text-emerald-100">{{ json_encode($message->zapi_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge {{
                                        $message->status === 'enviado'
                                            ? 'badge-success'
                                            : ($message->status === 'falhou' ? 'badge-warning' : 'badge-neutral')
                                    }}">
                                        {{ ucfirst($message->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.management.messages.destroy', $message) }}" onsubmit="return confirm('Excluir este log de WhatsApp? A acao ficara registrada na auditoria.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Excluir com log
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Sem mensagens para os filtros atuais.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">{{ $messages->links() }}</div>
        </section>
    </div>
</x-layouts.app>
