<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Lead;
use App\Models\SacTicket;
use App\Models\SellerCommission;
use App\Models\WhatsappLog;
use App\Services\CheckoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class OrdersController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $documento = preg_replace('/\D/', '', (string) $request->user()->cpf_cnpj);
        $tipoDocumento = strlen($documento) === 14 ? 'cnpj' : 'cpf';

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

        return view('portal.dashboard', [
            'orders' => $orders,
            'stats' => $stats,
            'creditReport' => $this->sampleCreditReport($tipoDocumento, $request),
            'referralOrders' => $referralOrders,
            'referralStats' => $referralStats,
        ]);
    }

    private function sampleCreditReport(string $tipoDocumento, Request $request): array
    {
        $base = [
            'data_consulta' => now()->format('d/m/Y H:i'),
            'diagnostico' => [
                'risco' => 'Moderado',
                'rating' => 'B',
                'conclusao' => 'Negociação cautelosa',
            ],
            'indicadores' => [
                'prob_inadimplencia' => 30.0,
                'limite_sugerido' => 10695.00,
                'renda_estimada' => 35650.00,
                'score' => 686,
                'pontualidade_pagamento' => 95.22,
            ],
            'resumo' => [
                'saude_financeira' => 'Regular',
                'capacidade_mensal' => 3208.50,
                'limite_credito_mensal' => 10695.00,
                'comprometimento_renda' => '30%',
                'busca_credito_12m' => 'Moderada',
                'endividamento_credito' => 'Baixo',
            ],
            'ocorrencias' => [
                ['item' => 'RGI - Registro Geral de Inadimplentes', 'status' => 'Nada consta'],
                ['item' => 'Cheque sem fundo Bacen', 'status' => 'Nada consta'],
                ['item' => 'Protesto nacional', 'status' => 'Nada consta'],
                ['item' => 'Total de pendências', 'status' => 'Nada consta'],
            ],
            'enderecos' => [
                'Rui Barbosa, 169, Centro, Montes Claros - MG, CEP: 39400-051',
                'João Anastácio, 207, Chácaras do Paiva, Sete Lagoas - MG, CEP: 35700-165',
                'Atlântica, 1551, Centro, Alcobaça - BA, CEP: 04591-001',
            ],
        ];

        if ($tipoDocumento === 'cnpj') {
            return array_merge($base, [
                'tipo' => 'CNPJ',
                'dados_cadastrais' => [
                    'razao_social' => $request->user()->name ?: 'EMPRESA EXEMPLO LTDA',
                    'cnpj' => $request->user()->cpf_cnpj ?: '00.000.000/0001-00',
                    'situacao_cnpj' => 'ATIVA',
                    'porte' => 'Médio porte',
                    'fundacao' => '12/08/2010',
                    'quadro_social' => '3 sócios e 1 administrador',
                    'cliente_premium' => 'SIM',
                ],
                'participacoes' => [],
            ]);
        }

        return array_merge($base, [
            'tipo' => 'CPF',
            'dados_cadastrais' => [
                'nome' => $request->user()->name ?: 'Cliente Regulariza',
                'cpf' => $request->user()->cpf_cnpj ?: '000.000.000-00',
                'data_nascimento' => '23/11/1958 - Domingo',
                'situacao_cpf' => 'REGULAR',
                'nome_mae' => 'EMILIA BOUQUARD BASTOS',
                'classe_social' => 'A1',
                'cliente_premium' => 'SIM',
                'telefones' => '98245-5932, 99813-3051',
            ],
            'participacoes' => [
                ['empresa' => 'PAIOL DE MINAS COMERCIO DE ALIMENTOS LTDA', 'cnpj' => '01.493.955/0001-90', 'participacao' => '100%'],
                ['empresa' => 'EMPRESA BRASILEIRA DE HOTELARIA E INCORPORADORA LTDA', 'cnpj' => '03.566.041/0002-19', 'participacao' => '100%'],
                ['empresa' => 'C.H.B. BASTOS LTDA', 'cnpj' => '46.626.329/0001-63', 'participacao' => '100%'],
            ],
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

        $receitaTotal = (float) Order::pagos()->sum('valor');
        $receitaMes = (float) Order::pagos()
            ->whereBetween('pago_em', [$inicioMes, $fimMes])
            ->sum('valor');

        $pedidosPagos = (int) Order::pagos()->count();
        $ticketMedio = $pedidosPagos > 0 ? $receitaTotal / $pedidosPagos : 0.0;

        $pedidosPendentes = (int) Order::pendentes()->count();
        $totalPedidos = (int) Order::count();
        $taxaPendencia = $totalPedidos > 0 ? ($pedidosPendentes / $totalPedidos) * 100 : 0.0;

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

                return [
                    'label' => ucfirst(Carbon::parse($inicio)->translatedFormat('M/y')),
                    'total' => (float) Order::pagos()->whereBetween('pago_em', [$inicio, $fim])->sum('valor'),
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
            'taxaPendencia' => $taxaPendencia,
            'receitaPorServico' => $receitaPorServico,
            'seriesMensal' => $seriesMensal,
        ]);
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
            $checkoutUrl = $checkoutService->createCheckoutSessionForOrder($order);
        } catch (Throwable $e) {
            report($e);

            return back()->with('payment_link_error', 'Não foi possível gerar o novo link de pagamento agora.');
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
