<?php

namespace App\Http\Controllers;

use App\Models\ContractInstallment;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdminFinanceDashboardController extends Controller
{
    public function __invoke(Request $request): View
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

        $topServices = Order::query()
            ->with('service')
            ->where('pagamento_status', 'pago')
            ->selectRaw('service_id, SUM(valor) as total, COUNT(*) as total_count')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $monthlySeries = $this->buildMonthlySeries();
        $spiderMetrics = $this->buildSpiderMetrics(
            taxaRecebimento: $taxaRecebimento,
            receitaMes: $receitaMes,
            receitaTotal: $receitaTotal,
            ordersPaidCount: $ordersPaidCount,
            ordersGeneratedCount: $ordersGeneratedCount,
            installmentsPaidCount: $installmentsPaidCount,
            installmentsGeneratedCount: $installmentsGeneratedCount,
            emAbertoTotal: $emAbertoTotal,
            geradoTotal: $geradoTotal
        );
        $riskItems = $this->buildRiskItems(
            ordersOpenTotal: $ordersOpenTotal,
            ordersGeneratedTotal: $ordersGeneratedTotal,
            installmentsOpenTotal: $installmentsOpenTotal,
            installmentsGeneratedTotal: $installmentsGeneratedTotal,
            receitaMes: $receitaMes,
            receitaTotal: $receitaTotal,
            topServices: $topServices
        );
        $heatmap = $this->buildHeatmap($topServices, $monthlySeries);
        $burndown = $this->buildBurndownSeries($monthlySeries);

        $dashboardPayload = [
            'headline' => [
                'title' => 'Dashboard Financeiro',
                'subtitle' => 'Leitura visual da receita, risco operacional e tração do funil financeiro em um único painel.',
            ],
            'metricCards' => [
                [
                    'label' => 'Recebido consolidado',
                    'display' => $this->formatCurrency($receitaTotal),
                    'hint' => 'Pedidos pagos + parcelas liquidadas',
                    'tone' => 'cyan',
                ],
                [
                    'label' => 'Recebido no mês',
                    'display' => $this->formatCurrency($receitaMes),
                    'hint' => 'Entrada financeira do mês corrente',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Ticket médio da pesquisa',
                    'display' => $this->formatCurrency($ticketMedio),
                    'hint' => 'Média por pedido pago',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Taxa de recebimento',
                    'display' => $this->formatPercent($taxaRecebimento),
                    'hint' => 'Quanto do total gerado já virou caixa',
                    'tone' => 'rose',
                ],
            ],
            'comparisons' => [
                [
                    'title' => 'Pesquisas / pedidos',
                    'subtitle' => 'Mostra o valor gerado no funil e o que já foi pago.',
                    'items' => [
                        [
                            'label' => 'Gerado',
                            'display' => $this->formatCurrency($ordersGeneratedTotal),
                            'meta' => "{$ordersGeneratedCount} pedido(s)",
                            'tone' => 'slate',
                        ],
                        [
                            'label' => 'Pago',
                            'display' => $this->formatCurrency($ordersPaidTotal),
                            'meta' => "{$ordersPaidCount} pedido(s)",
                            'tone' => 'emerald',
                        ],
                        [
                            'label' => 'Em aberto',
                            'display' => $this->formatCurrency($ordersOpenTotal),
                            'meta' => 'Pedidos sem baixa financeira',
                            'tone' => 'amber',
                        ],
                    ],
                ],
                [
                    'title' => 'Parcelas de contratos',
                    'subtitle' => 'Controle do que foi emitido após o aceite e do que efetivamente entrou.',
                    'items' => [
                        [
                            'label' => 'Gerado',
                            'display' => $this->formatCurrency($installmentsGeneratedTotal),
                            'meta' => "{$installmentsGeneratedCount} cobrança(s)",
                            'tone' => 'slate',
                        ],
                        [
                            'label' => 'Pago',
                            'display' => $this->formatCurrency($installmentsPaidTotal),
                            'meta' => "{$installmentsPaidCount} parcela(s)",
                            'tone' => 'emerald',
                        ],
                        [
                            'label' => 'Em aberto',
                            'display' => $this->formatCurrency($installmentsOpenTotal),
                            'meta' => 'Parcelas aguardando baixa',
                            'tone' => 'amber',
                        ],
                    ],
                ],
            ],
            'gauge' => [
                'title' => 'Gauge de eficiência financeira',
                'label' => 'Taxa de recebimento',
                'value' => round($taxaRecebimento, 1),
                'display' => $this->formatPercent($taxaRecebimento),
                'min' => 0,
                'max' => 100,
            ],
            'satisfaction' => [
                'title' => 'Humor da operação',
                'label' => 'Satisfação estimada da carteira',
                'value' => round(min(100, max(0, ($taxaRecebimento * 0.6) + (($receitaMes > 0 ? 25 : 10)) + ($ordersPaidCount > 0 ? 8 : 0))), 1),
            ],
            'spider' => [
                'title' => 'Radar de saúde financeira',
                'subtitle' => 'Combina liquidez, ritmo do mês, fechamento de pedidos e controle do saldo em aberto.',
                'metrics' => $spiderMetrics,
            ],
            'riskMatrix' => [
                'title' => 'Matriz de risco operacional',
                'subtitle' => 'Distribui os principais riscos por probabilidade e impacto.',
                'items' => $riskItems,
            ],
            'heatmap' => [
                'title' => 'Heatmap de ocorrências por serviço',
                'subtitle' => 'Pedidos pagos por serviço nos últimos 6 meses.',
                'xLabels' => $heatmap['xLabels'],
                'rows' => $heatmap['rows'],
                'maxValue' => $heatmap['maxValue'],
            ],
            'bullet' => [
                'title' => 'Bullet de metas',
                'subtitle' => 'Compara resultado atual com referência esperada do painel.',
                'items' => [
                    [
                        'label' => 'Caixa realizado',
                        'value' => round($receitaTotal, 2),
                        'target' => round($geradoTotal, 2),
                        'max' => round(max($geradoTotal, $receitaTotal) * 1.08, 2),
                        'displayValue' => $this->formatCurrency($receitaTotal),
                        'displayTarget' => $this->formatCurrency($geradoTotal),
                    ],
                    [
                        'label' => 'Pedidos fechados',
                        'value' => $ordersPaidCount,
                        'target' => max($ordersGeneratedCount, 1),
                        'max' => max($ordersGeneratedCount, $ordersPaidCount, 1),
                        'displayValue' => number_format($ordersPaidCount, 0, ',', '.'),
                        'displayTarget' => number_format($ordersGeneratedCount, 0, ',', '.'),
                    ],
                    [
                        'label' => 'Parcelas liquidadas',
                        'value' => $installmentsPaidCount,
                        'target' => max($installmentsGeneratedCount, 1),
                        'max' => max($installmentsGeneratedCount, $installmentsPaidCount, 1),
                        'displayValue' => number_format($installmentsPaidCount, 0, ',', '.'),
                        'displayTarget' => number_format($installmentsGeneratedCount, 0, ',', '.'),
                    ],
                ],
            ],
            'treemap' => [
                'title' => 'Treemap de riscos',
                'subtitle' => 'Peso relativo de cada bloco de atenção no financeiro.',
                'items' => collect($riskItems)->map(fn (array $item) => [
                    'label' => $item['label'],
                    'value' => $item['severity'],
                    'display' => $item['display'],
                ])->values()->all(),
            ],
            'burndown' => [
                'title' => 'Burndown do saldo em aberto',
                'subtitle' => 'Mostra o comportamento do backlog financeiro mês a mês.',
                'series' => $burndown,
            ],
            'services' => [
                'title' => 'Pesquisas pagas por serviço',
                'items' => $topServices->map(fn ($item) => [
                    'label' => $item->service?->nome ?? 'Serviço removido',
                    'display' => $this->formatCurrency((float) $item->total),
                    'count' => (int) $item->total_count,
                ])->values()->all(),
            ],
        ];

        return view('admin.finance.dashboard', [
            'dashboardPayload' => $dashboardPayload,
        ]);
    }

    private function buildMonthlySeries(): array
    {
        return collect(range(5, 0))
            ->map(function (int $i): array {
                $mes = now()->subMonths($i)->startOfMonth();
                $inicio = $mes->copy()->startOfMonth();
                $fim = $mes->copy()->endOfMonth();

                $generatedOrders = (float) Order::query()
                    ->whereBetween('created_at', [$inicio, $fim])
                    ->sum('valor');
                $generatedInstallments = (float) ContractInstallment::query()
                    ->whereBetween('created_at', [$inicio, $fim])
                    ->sum('amount');
                $receivedOrders = (float) Order::pagos()
                    ->whereBetween('pago_em', [$inicio, $fim])
                    ->sum('valor');
                $receivedInstallments = (float) ContractInstallment::query()
                    ->where('status', 'pago')
                    ->whereBetween('paid_at', [$inicio, $fim])
                    ->sum('amount');

                return [
                    'key' => $inicio->format('Y-m'),
                    'label' => ucfirst(Carbon::parse($inicio)->translatedFormat('M/y')),
                    'generated' => round($generatedOrders + $generatedInstallments, 2),
                    'received' => round($receivedOrders + $receivedInstallments, 2),
                ];
            })
            ->reduce(function (array $carry, array $item): array {
                $previousOpen = empty($carry) ? 0.0 : (float) $carry[array_key_last($carry)]['open'];
                $item['open'] = round(max(0, $previousOpen + $item['generated'] - $item['received']), 2);
                $carry[] = $item;

                return $carry;
            }, []);
    }

    private function buildSpiderMetrics(
        float $taxaRecebimento,
        float $receitaMes,
        float $receitaTotal,
        int $ordersPaidCount,
        int $ordersGeneratedCount,
        int $installmentsPaidCount,
        int $installmentsGeneratedCount,
        float $emAbertoTotal,
        float $geradoTotal
    ): array {
        $monthlyRhythm = $receitaTotal > 0 ? min(100, ($receitaMes / max($receitaTotal, 1)) * 220) : 0;
        $ordersCloseRate = $ordersGeneratedCount > 0 ? ($ordersPaidCount / $ordersGeneratedCount) * 100 : 0;
        $installmentsCloseRate = $installmentsGeneratedCount > 0 ? ($installmentsPaidCount / $installmentsGeneratedCount) * 100 : 0;
        $openControl = $geradoTotal > 0 ? 100 - min(100, ($emAbertoTotal / $geradoTotal) * 100) : 100;

        return [
            ['label' => 'Recebimento', 'value' => round($taxaRecebimento, 1), 'max' => 100],
            ['label' => 'Ritmo mensal', 'value' => round($monthlyRhythm, 1), 'max' => 100],
            ['label' => 'Pedidos pagos', 'value' => round($ordersCloseRate, 1), 'max' => 100],
            ['label' => 'Parcelas baixadas', 'value' => round($installmentsCloseRate, 1), 'max' => 100],
            ['label' => 'Controle do aberto', 'value' => round($openControl, 1), 'max' => 100],
        ];
    }

    private function buildRiskItems(
        float $ordersOpenTotal,
        float $ordersGeneratedTotal,
        float $installmentsOpenTotal,
        float $installmentsGeneratedTotal,
        float $receitaMes,
        float $receitaTotal,
        Collection $topServices
    ): array {
        $topServiceTotal = (float) ($topServices->first()->total ?? 0);
        $paidServicesTotal = (float) $topServices->sum('total');
        $concentrationRatio = $paidServicesTotal > 0 ? ($topServiceTotal / $paidServicesTotal) : 0;
        $monthlyRatio = $receitaTotal > 0 ? ($receitaMes / max($receitaTotal, 1)) : 0;

        return [
            $this->makeRiskItem('Pedidos em aberto', $ordersOpenTotal, $ordersGeneratedTotal, 4),
            $this->makeRiskItem('Parcelas em aberto', $installmentsOpenTotal, $installmentsGeneratedTotal, 5),
            $this->makeRiskItem('Concentração no serviço líder', $topServiceTotal, max($paidServicesTotal, 1), 3, $concentrationRatio),
            $this->makeRiskItem('Baixa financeira do mês', 1 - $monthlyRatio, 1, 4, 1 - $monthlyRatio),
        ];
    }

    private function makeRiskItem(string $label, float $value, float $base, int $impact, ?float $ratioOverride = null): array
    {
        $ratio = $ratioOverride ?? ($base > 0 ? $value / $base : 0);
        $probability = max(1, min(5, (int) ceil(max(0.08, $ratio) * 5)));
        $severity = $probability * $impact;

        return [
            'label' => $label,
            'impact' => $impact,
            'probability' => $probability,
            'severity' => $severity,
            'display' => $this->formatPercent($ratio * 100),
        ];
    }

    private function buildHeatmap(Collection $topServices, array $monthlySeries): array
    {
        $months = collect($monthlySeries)
            ->mapWithKeys(fn (array $item) => [$item['key'] => $item['label']]);

        $serviceIds = $topServices->pluck('service_id')->filter()->values();

        $orders = Order::query()
            ->with('service')
            ->where('pagamento_status', 'pago')
            ->when($serviceIds->isNotEmpty(), fn ($query) => $query->whereIn('service_id', $serviceIds))
            ->whereBetween('pago_em', [now()->subMonths(5)->startOfMonth(), now()->endOfMonth()])
            ->get();

        $rows = $topServices->map(function ($serviceItem) use ($orders, $months): array {
            $values = $months->keys()->map(function (string $monthKey) use ($orders, $serviceItem): int {
                return (int) $orders
                    ->filter(fn (Order $order) => (int) $order->service_id === (int) $serviceItem->service_id)
                    ->filter(fn (Order $order) => optional($order->pago_em)->format('Y-m') === $monthKey)
                    ->count();
            })->all();

            return [
                'label' => $serviceItem->service?->nome ?? 'Serviço removido',
                'values' => $values,
            ];
        })->values()->all();

        $maxValue = max(1, ...collect($rows)->flatMap(fn (array $row) => $row['values'])->all());

        return [
            'xLabels' => $months->values()->all(),
            'rows' => $rows,
            'maxValue' => $maxValue,
        ];
    }

    private function buildBurndownSeries(array $monthlySeries): array
    {
        $actualOpen = collect($monthlySeries)->pluck('open')->map(fn ($value) => round((float) $value, 2))->values();
        $startingOpen = (float) $actualOpen->first();
        $steps = max(1, $actualOpen->count() - 1);
        $expectedStep = $startingOpen / $steps;

        return collect($monthlySeries)->values()->map(function (array $item, int $index) use ($startingOpen, $expectedStep): array {
            return [
                'label' => $item['label'],
                'expected' => round(max(0, $startingOpen - ($expectedStep * $index)), 2),
                'actual' => round((float) $item['open'], 2),
            ];
        })->all();
    }

    private function formatCurrency(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }

    private function formatPercent(float $value): string
    {
        return number_format($value, 1, ',', '.').'%';
    }
}
