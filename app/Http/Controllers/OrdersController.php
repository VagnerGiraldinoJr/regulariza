<?php

namespace App\Http\Controllers;

use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\Lead;
use App\Models\SacTicket;
use App\Models\SellerCommission;
use App\Models\WhatsappLog;
use App\Services\AdminAuditService;
use App\Services\CheckoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdersController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $ordersQuery = $request->user()
            ->orders()
            ->with('service');

        $orders = (clone $ordersQuery)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => (clone $ordersQuery)->count(),
            'pagos' => (clone $ordersQuery)->where('pagamento_status', 'pago')->count(),
            'em_andamento' => (clone $ordersQuery)->where('status', 'em_andamento')->count(),
        ];

        $referralOrdersQuery = $this->referralOrdersQueryForUser((int) $request->user()->id);

        $referralOrders = (clone $referralOrdersQuery)
            ->with(['service', 'lead', 'user'])
            ->latest()
            ->paginate(10, ['*'], 'indicacoes_page')
            ->withQueryString();

        $referralStats = [
            'total_contratos' => (clone $referralOrdersQuery)->count(),
            'valor_total' => (float) (clone $referralOrdersQuery)->sum('valor'),
            'validos' => (clone $referralOrdersQuery)->where('pagamento_status', 'pago')->count(),
            'pendentes' => (clone $referralOrdersQuery)->where('pagamento_status', '!=', 'pago')->count(),
        ];

        $contractsQuery = $request->user()->contracts();
        $installmentsQuery = ContractInstallment::query()
            ->whereHas('contract', fn ($query) => $query->where('user_id', $request->user()->id));
        $ticketsQuery = $request->user()->sacTickets();

        $portfolioSummary = [
            'contracts_total' => (clone $contractsQuery)->count(),
            'contracts_active' => (clone $contractsQuery)->whereIn('status', ['aguardando_aceite', 'aguardando_entrada', 'ativo'])->count(),
            'open_installments' => (clone $installmentsQuery)->where('status', '!=', 'pago')->count(),
            'open_installments_total' => (float) (clone $installmentsQuery)->where('status', '!=', 'pago')->sum('amount'),
            'paid_installments_total' => (float) (clone $installmentsQuery)->where('status', 'pago')->sum('amount'),
            'support_open' => (clone $ticketsQuery)->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente'])->count(),
        ];

        $supportSummary = [
            'total' => (clone $ticketsQuery)->count(),
            'open' => (clone $ticketsQuery)->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente'])->count(),
            'resolved' => (clone $ticketsQuery)->whereIn('status', ['resolvido', 'fechado'])->count(),
        ];

        $upcomingInstallments = ContractInstallment::query()
            ->whereHas('contract', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['contract.order', 'contract.analyst'])
            ->where('status', '!=', 'pago')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(4)
            ->get();

        return view('portal.dashboard', [
            'orders' => $orders,
            'stats' => $stats,
            'referralOrders' => $referralOrders,
            'referralStats' => $referralStats,
            'portfolioSummary' => $portfolioSummary,
            'supportSummary' => $supportSummary,
            'upcomingInstallments' => $upcomingInstallments,
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $status = (string) $request->query('status', '');
        $pagamentoStatus = (string) $request->query('pagamento_status', '');

        $allowedStatus = ['pendente', 'em_andamento', 'concluido', 'cancelado'];
        $allowedPagamentoStatus = ['aguardando', 'pago', 'falhou', 'reembolsado'];

        $ordersQuery = Order::query()
            ->with(['user', 'service'])
            ->when(in_array($status, $allowedStatus, true), fn ($query) => $query->where('status', $status))
            ->when(in_array($pagamentoStatus, $allowedPagamentoStatus, true), fn ($query) => $query->where('pagamento_status', $pagamentoStatus));

        $orders = (clone $ordersQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => (clone $ordersQuery)->count(),
            'pagos' => (clone $ordersQuery)->where('pagamento_status', 'pago')->count(),
            'pendentes' => (clone $ordersQuery)->where('status', 'pendente')->count(),
            'em_andamento' => (clone $ordersQuery)->where('status', 'em_andamento')->count(),
            'total_value' => (float) (clone $ordersQuery)->sum('valor'),
            'paid_value' => (float) (clone $ordersQuery)->where('pagamento_status', 'pago')->sum('valor'),
            'commissions_pending' => (int) SellerCommission::query()->whereIn('status', ['pending', 'available'])->count(),
            'sac_open' => (int) SacTicket::query()->whereIn('status', ['aberto', 'em_atendimento', 'aguardando_cliente'])->count(),
            'leads_unassigned' => (int) Lead::query()->whereNull('referred_by_user_id')->count(),
            'messages_pending' => (int) WhatsappLog::query()->where('status', 'pendente')->count(),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'pagamento_status' => $pagamentoStatus,
            ],
        ]);
    }

    public function adminFinance(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $ordersGeneratedTotal = (float) Order::query()->sum('valor');
        $ordersPaidTotal = (float) Order::pagos()->sum('valor');
        $ordersOpenTotal = max(0, $ordersGeneratedTotal - $ordersPaidTotal);
        $ordersPaidCount = (int) Order::pagos()->count();
        $ordersGeneratedCount = (int) Order::query()->count();

        $installmentsGeneratedTotal = (float) ContractInstallment::query()->sum('amount');
        $installmentsPaidTotal = (float) ContractInstallment::query()->where('status', 'pago')->sum('amount');
        $installmentsOpenTotal = max(0, $installmentsGeneratedTotal - $installmentsPaidTotal);
        $installmentsPaidCount = (int) ContractInstallment::query()->where('status', 'pago')->count();
        $installmentsGeneratedCount = (int) ContractInstallment::query()->count();

        $receitaTotal = $ordersPaidTotal + $installmentsPaidTotal;
        $receitaMesPedidos = (float) Order::pagos()
            ->whereBetween('pago_em', [$inicioMes, $fimMes])
            ->sum('valor');
        $receitaMesParcelas = (float) ContractInstallment::query()
            ->where('status', 'pago')
            ->whereBetween('paid_at', [$inicioMes, $fimMes])
            ->sum('amount');
        $receitaMes = $receitaMesPedidos + $receitaMesParcelas;

        $ticketMedio = $ordersPaidCount > 0 ? $ordersPaidTotal / $ordersPaidCount : 0.0;

        $geradoTotal = $ordersGeneratedTotal + $installmentsGeneratedTotal;
        $emAbertoTotal = $ordersOpenTotal + $installmentsOpenTotal;
        $taxaRecebimento = $geradoTotal > 0 ? ($receitaTotal / $geradoTotal) * 100 : 0.0;

        $receitaPorServico = Order::query()
            ->with('service')
            ->where('pagamento_status', 'pago')
            ->selectRaw('service_id, SUM(valor) as total')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $seriesMensal = collect(range(5, 1))
            ->map(function (int $i) {
                $mes = now()->subMonths($i);
                $inicio = $mes->copy()->startOfMonth();
                $fim = $mes->copy()->endOfMonth();
                $totalPedidos = (float) Order::pagos()->whereBetween('pago_em', [$inicio, $fim])->sum('valor');
                $totalParcelas = (float) ContractInstallment::query()
                    ->where('status', 'pago')
                    ->whereBetween('paid_at', [$inicio, $fim])
                    ->sum('amount');

                return [
                    'label' => ucfirst(Carbon::parse($inicio)->translatedFormat('M/y')),
                    'total' => $totalPedidos + $totalParcelas,
                ];
            })
            ->push([
                'label' => ucfirst(now()->translatedFormat('M/y')),
                'total' => $receitaMes,
            ]);

        return view('admin.finance.dashboard', [
            'receitaTotal' => $receitaTotal,
            'receitaMes' => $receitaMes,
            'ticketMedio' => $ticketMedio,
            'taxaRecebimento' => $taxaRecebimento,
            'receitaPorServico' => $receitaPorServico,
            'seriesMensal' => $seriesMensal,
            'ordersSummary' => [
                'generated_total' => $ordersGeneratedTotal,
                'paid_total' => $ordersPaidTotal,
                'open_total' => $ordersOpenTotal,
                'generated_count' => $ordersGeneratedCount,
                'paid_count' => $ordersPaidCount,
            ],
            'installmentsSummary' => [
                'generated_total' => $installmentsGeneratedTotal,
                'paid_total' => $installmentsPaidTotal,
                'open_total' => $installmentsOpenTotal,
                'generated_count' => $installmentsGeneratedCount,
                'paid_count' => $installmentsPaidCount,
            ],
        ]);
    }

    public function destroy(Request $request, Order $order, AdminAuditService $adminAuditService): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $order->loadMissing(['user', 'service', 'lead', 'contract']);

        if ($order->pagamento_status === 'pago') {
            return back()->withErrors(['order_delete' => 'Pedidos pagos não podem ser excluídos.']);
        }

        if ($order->contract()->exists()) {
            return back()->withErrors(['order_delete' => 'Pedidos com contrato vinculado não podem ser excluídos.']);
        }

        if ($order->sellerCommissions()->exists()) {
            return back()->withErrors(['order_delete' => 'Pedidos com comissão vinculada não podem ser excluídos.']);
        }

        DB::transaction(function () use ($order, $request, $adminAuditService): void {
            foreach ($order->sacTickets()->get() as $ticket) {
                $ticket->delete();
            }

            $order->whatsappLogs()->delete();

            $adminAuditService->record(
                $request->user(),
                'order_deleted',
                $order,
                'Pedido não pago removido manualmente pelo admin.',
                [
                    'user_id' => $order->user_id,
                    'service_id' => $order->service_id,
                    'lead_id' => $order->lead_id,
                    'status' => $order->status,
                    'payment_status' => $order->pagamento_status,
                    'value' => (float) $order->valor,
                ]
            );

            $order->delete();
        });

        return back()->with('success', "Pedido {$order->protocolo} excluído com sucesso.");
    }

    public function adminSellers(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['service', 'lead', 'user', 'lead.referredBy', 'user.referredBy'])
            ->latest()
            ->get();

        $sellerMap = [];

        foreach ($orders as $order) {
            $seller = $order->lead?->referredBy ?? $order->user?->referredBy;

            if (! $seller) {
                continue;
            }

            $sellerId = (int) $seller->id;

            if (! isset($sellerMap[$sellerId])) {
                $sellerMap[$sellerId] = [
                    'id' => $sellerId,
                    'name' => $seller->name,
                    'email' => $seller->email,
                    'referral_code' => $seller->referral_code,
                    'total_contratos' => 0,
                    'total_valor' => 0.0,
                    'contratos' => [],
                ];
            }

            $documento = (string) ($order->lead?->cpf_cnpj ?: $order->user?->cpf_cnpj ?: '-');
            $tipoDocumento = strlen(preg_replace('/\D+/', '', $documento)) > 11 ? 'CNPJ' : 'CPF';
            $whatsappDigits = preg_replace('/\D+/', '', (string) ($order->lead?->whatsapp ?: $order->user?->whatsapp ?: ''));
            $whatsappDigits = $whatsappDigits !== '' && strlen($whatsappDigits) <= 11 ? '55'.$whatsappDigits : $whatsappDigits;
            $whatsappLink = $whatsappDigits !== '' ? 'https://wa.me/'.$whatsappDigits : null;

            $sellerMap[$sellerId]['contratos'][] = [
                'order_id' => $order->id,
                'protocolo' => $order->protocolo,
                'documento' => $documento,
                'tipo_documento' => $tipoDocumento,
                'valor' => (float) $order->valor,
                'status' => $order->status,
                'pagamento_status' => $order->pagamento_status,
                'cliente_nome' => $order->user?->name ?: '-',
                'whatsapp_link' => $whatsappLink,
            ];

            $sellerMap[$sellerId]['total_contratos']++;
            $sellerMap[$sellerId]['total_valor'] += (float) $order->valor;
        }

        $sellers = collect($sellerMap)
            ->sortByDesc('total_valor')
            ->values();

        return view('admin.vendors.index', [
            'sellers' => $sellers,
            'totalSellers' => $sellers->count(),
            'totalContracts' => (int) $sellers->sum('total_contratos'),
            'totalValue' => (float) $sellers->sum('total_valor'),
        ]);
    }

    public function resendPaymentLink(Request $request, Order $order, CheckoutService $checkoutService)
    {
        $this->authorize('viewAny', Order::class);

        $order->loadMissing(['user', 'lead']);

        $actor = $request->user();
        $actorId = (int) $actor->id;
        $isOwner = (int) $order->user_id === $actorId;
        $isReferrer = (int) ($order->lead?->referred_by_user_id ?? 0) === $actorId
            || (int) ($order->user?->referred_by_user_id ?? 0) === $actorId;

        if (! $isOwner && ! $isReferrer) {
            abort(403);
        }

        if ($order->pagamento_status === 'pago') {
            return back()->with('payment_link_error', 'Este pedido já está com pagamento confirmado.');
        }

        try {
            $checkout = $checkoutService->createCheckoutSessionForOrder($order);
        } catch (Throwable $e) {
            report($e);

            return back()->with('payment_link_error', 'Não foi possível gerar o novo link de pagamento agora.');
        }

        $checkoutUrl = (string) ($checkout['payment_url'] ?? $order->payment_link_url ?? '');

        if ($checkoutUrl === '') {
            return back()->with('payment_link_error', 'A cobrança foi criada, mas nenhum link de pagamento foi retornado.');
        }

        if ($isReferrer) {
            $phoneDigits = preg_replace('/\D+/', '', (string) ($order->lead?->whatsapp ?: $order->user?->whatsapp ?: ''));
            $phoneDigits = $phoneDigits !== '' && strlen($phoneDigits) <= 11 ? '55'.$phoneDigits : $phoneDigits;

            if ($phoneDigits !== '') {
                $customerName = (string) ($order->user?->name ?: 'cliente');
                $message = "Oi {$customerName}, segue seu link para concluir o pagamento da regularização: {$checkoutUrl}";
                $whatsappUrl = 'https://wa.me/'.$phoneDigits.'?text='.rawurlencode($message);

                return redirect()->away($whatsappUrl);
            }
        }

        return redirect()->away($checkoutUrl);
    }

    private function referralOrdersQueryForUser(int $userId)
    {
        return Order::query()
            ->where(function ($query) use ($userId): void {
                $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('referred_by_user_id', $userId))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('referred_by_user_id', $userId));
            });
    }
}
