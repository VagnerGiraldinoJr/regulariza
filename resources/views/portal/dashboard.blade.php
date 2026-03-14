<x-layouts.app>
    @php
        $orderStatusMap = [
            'pendente' => ['label' => 'Pendente', 'class' => 'badge-warning'],
            'em_andamento' => ['label' => 'Em andamento', 'class' => 'badge-info'],
            'concluido' => ['label' => 'Concluido', 'class' => 'badge-success'],
            'cancelado' => ['label' => 'Cancelado', 'class' => 'badge-danger'],
        ];
        $paymentMap = [
            'aguardando' => ['label' => 'Aguardando', 'class' => 'badge-warning'],
            'pago' => ['label' => 'Pago', 'class' => 'badge-success'],
            'falhou' => ['label' => 'Falhou', 'class' => 'badge-danger'],
            'reembolsado' => ['label' => 'Reembolsado', 'class' => 'badge-neutral'],
        ];
    @endphp

    <div class="space-y-5">
        <section class="hero-card p-5 lg:p-6">
            <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <div>
                    <p class="hero-card__eyebrow">Minha carteira</p>
                    <h1 class="hero-card__title">Portal do Cliente</h1>
                    <p class="hero-card__lead">Acompanhe pedidos, contratos, cobrancas e atendimento em um painel unico. Os indicadores abaixo refletem apenas os seus dados reais na plataforma.</p>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="summary-pill">Contratos ativos: {{ $portfolioSummary['contracts_active'] }}</span>
                        <span class="summary-pill">Parcelas em aberto: {{ $portfolioSummary['open_installments'] }}</span>
                        <span class="summary-pill">SAC aberto: {{ $portfolioSummary['support_open'] }}</span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="hero-stat">
                        <p class="hero-stat__label">Saldo em aberto</p>
                        <p class="hero-stat__value">R$ {{ number_format($portfolioSummary['open_installments_total'], 2, ',', '.') }}</p>
                        <p class="hero-stat__meta">Valor atual das parcelas ainda nao quitadas.</p>
                    </article>
                    <article class="hero-stat">
                        <p class="hero-stat__label">Total pago em contratos</p>
                        <p class="hero-stat__value">R$ {{ number_format($portfolioSummary['paid_installments_total'], 2, ',', '.') }}</p>
                        <p class="hero-stat__meta">Parcelas ja liquidadas dentro da sua carteira.</p>
                    </article>
                </div>
            </div>
        </section>

        @if (session('payment_link_error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('payment_link_error') }}
            </div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            <article class="insight-tile insight-tile--cyan">
                <p class="insight-tile__label">Pedidos</p>
                <p class="insight-tile__value">{{ $stats['total'] }}</p>
                <p class="insight-tile__meta">Total de pedidos criados na plataforma.</p>
            </article>
            <article class="insight-tile insight-tile--emerald">
                <p class="insight-tile__label">Pedidos pagos</p>
                <p class="insight-tile__value">{{ $stats['pagos'] }}</p>
                <p class="insight-tile__meta">Pedidos com pagamento confirmado.</p>
            </article>
            <article class="insight-tile insight-tile--amber">
                <p class="insight-tile__label">Em andamento</p>
                <p class="insight-tile__value">{{ $stats['em_andamento'] }}</p>
                <p class="insight-tile__meta">Pedidos ja ativos na esteira operacional.</p>
            </article>
            <article class="insight-tile insight-tile--slate">
                <p class="insight-tile__label">Contratos</p>
                <p class="insight-tile__value">{{ $portfolioSummary['contracts_total'] }}</p>
                <p class="insight-tile__meta">Contratos vinculados ao seu cadastro.</p>
            </article>
            <article class="insight-tile insight-tile--rose">
                <p class="insight-tile__label">Chamados abertos</p>
                <p class="insight-tile__value">{{ $supportSummary['open'] }}</p>
                <p class="insight-tile__meta">Atendimentos em aberto com a equipe.</p>
            </article>
            <article class="insight-tile insight-tile--emerald">
                <p class="insight-tile__label">Chamados resolvidos</p>
                <p class="insight-tile__value">{{ $supportSummary['resolved'] }}</p>
                <p class="insight-tile__meta">Historico de tickets ja finalizados.</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="panel-card overflow-hidden">
                <div class="border-b border-slate-200 px-4 py-3">
                    <p class="section-kicker">Indicacoes</p>
                    <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Minhas vendas por referencia</h2>
                    <p class="mt-2 text-xs text-slate-500">Contratos vendidos com o seu codigo de indicacao e status financeiro real de cada pedido.</p>
                </div>

                <div class="grid gap-3 border-b border-slate-200/60 bg-white/25 px-4 py-4 sm:grid-cols-4">
                    <article class="stack-card">
                        <p class="stack-card__label">Contratos</p>
                        <p class="stack-card__title">{{ $referralStats['total_contratos'] }}</p>
                        <p class="stack-card__meta">Base total de indicacoes convertidas.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Total vendido</p>
                        <p class="stack-card__title">R$ {{ number_format($referralStats['valor_total'], 2, ',', '.') }}</p>
                        <p class="stack-card__meta">Somatorio financeiro das vendas geradas.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Validos</p>
                        <p class="stack-card__title">{{ $referralStats['validos'] }}</p>
                        <p class="stack-card__meta">Pedidos com pagamento confirmado.</p>
                    </article>
                    <article class="stack-card">
                        <p class="stack-card__label">Pendentes</p>
                        <p class="stack-card__title">{{ $referralStats['pendentes'] }}</p>
                        <p class="stack-card__meta">Vendas ainda aguardando conversao financeira.</p>
                    </article>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                            <tr>
                                <th class="px-4 py-3">Protocolo</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3">Servico</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">WhatsApp</th>
                                <th class="px-4 py-3">Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($referralOrders as $refOrder)
                                @php
                                    $doc = $refOrder->lead?->cpf_cnpj ?: $refOrder->user?->cpf_cnpj;
                                    $docDigits = preg_replace('/\D+/', '', (string) $doc);
                                    $docType = strlen($docDigits) > 11 ? 'CNPJ' : 'CPF';
                                    $phoneDigits = preg_replace('/\D+/', '', (string) ($refOrder->lead?->whatsapp ?: $refOrder->user?->whatsapp));
                                    $phoneDigits = $phoneDigits !== '' && strlen($phoneDigits) <= 11 ? '55'.$phoneDigits : $phoneDigits;
                                    $phoneLink = $phoneDigits !== '' ? 'https://wa.me/'.$phoneDigits : null;
                                @endphp
                                <tr class="border-t border-slate-200/60">
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $refOrder->protocolo }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $refOrder->user?->name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $docType }} {{ $doc ?: '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $refOrder->service?->nome }}</td>
                                    <td class="px-4 py-3">
                                        @if ($refOrder->pagamento_status === 'pago')
                                            <span class="badge badge-success">Valido</span>
                                        @else
                                            <span class="badge badge-warning">Pendente</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($phoneLink)
                                            <a href="{{ $phoneLink }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-blue-700 hover:text-blue-900">
                                                Abrir WhatsApp
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400">Sem numero</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($refOrder->pagamento_status !== 'pago')
                                            <form method="POST" action="{{ route('portal.orders.resend-payment-link', $refOrder) }}">
                                                @csrf
                                                <button type="submit" class="rounded-md bg-[#20b6c7] px-2 py-1 text-xs font-semibold text-white hover:bg-[#1599a8]">
                                                    Reenviar link
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-400">Pago</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-5 text-center text-slate-500">Voce ainda nao possui contratos vendidos por indicacao.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $referralOrders->links() }}
                </div>
            </section>

            <div class="grid gap-5">
                <section class="panel-card p-4">
                    <p class="section-kicker">Proximos vencimentos</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($upcomingInstallments as $installment)
                            <article class="stack-card">
                                <p class="stack-card__label">{{ $installment->contract?->order?->protocolo ?: 'Contrato' }}</p>
                                <p class="stack-card__title">{{ $installment->label }} - R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</p>
                                <p class="stack-card__meta">
                                    Vencimento: {{ $installment->due_date?->format('d/m/Y') ?: 'a definir' }}
                                    @if ($installment->contract?->analyst)
                                        | Analista: {{ $installment->contract->analyst->name }}
                                    @endif
                                </p>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">
                                Voce nao possui parcelas pendentes neste momento.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="panel-card p-4">
                    <p class="section-kicker">Atendimento</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <article class="stack-card">
                            <p class="stack-card__label">Tickets</p>
                            <p class="stack-card__title">{{ $supportSummary['total'] }}</p>
                            <p class="stack-card__meta">Historico completo de interacoes abertas.</p>
                        </article>
                        <article class="stack-card">
                            <p class="stack-card__label">Em aberto</p>
                            <p class="stack-card__title">{{ $supportSummary['open'] }}</p>
                            <p class="stack-card__meta">Chamados ainda em tratativa.</p>
                        </article>
                        <article class="stack-card">
                            <p class="stack-card__label">Resolvidos</p>
                            <p class="stack-card__title">{{ $supportSummary['resolved'] }}</p>
                            <p class="stack-card__meta">Tickets finalizados pela equipe.</p>
                        </article>
                    </div>
                </section>
            </div>
        </section>

        <section class="panel-card overflow-hidden">
            <div class="border-b border-slate-200 px-4 py-3">
                <p class="section-kicker">Pedidos</p>
                <h2 class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-slate-700">Meus pedidos</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white/45 text-left text-xs uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3">Protocolo</th>
                            <th class="px-4 py-3">Servico</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Pagamento</th>
                            <th class="px-4 py-3">Acao</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $statusInfo = $orderStatusMap[$order->status] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $order->status)), 'class' => 'badge-neutral'];
                                $paymentInfo = $paymentMap[$order->pagamento_status] ?? ['label' => ucfirst((string) $order->pagamento_status), 'class' => 'badge-neutral'];
                            @endphp
                            <tr class="border-t border-slate-200/60">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $order->protocolo }}</td>
                                <td class="px-4 py-3 text-slate-700"><span class="badge badge-info">{{ $order->service?->nome }}</span></td>
                                <td class="px-4 py-3 text-slate-700"><span class="badge {{ $statusInfo['class'] }}">{{ $statusInfo['label'] }}</span></td>
                                <td class="px-4 py-3 text-slate-700"><span class="badge {{ $paymentInfo['class'] }}">{{ $paymentInfo['label'] }}</span></td>
                                <td class="px-4 py-3">
                                    @if ($order->pagamento_status !== 'pago')
                                        <form method="POST" action="{{ route('portal.orders.resend-payment-link', $order) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-[#20b6c7] px-2 py-1 text-xs font-semibold text-white hover:bg-[#1599a8]">
                                                Pagar agora
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhum pedido encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3">
                {{ $orders->links() }}
            </div>
        </section>
    </div>
</x-layouts.app>
