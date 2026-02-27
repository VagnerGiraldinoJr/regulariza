<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = $request->user()
            ->orders()
            ->with('service')
            ->latest()
            ->paginate(10);

        return view('portal.dashboard', [
            'orders' => $orders,
        ]);
    }

    public function adminIndex(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['user', 'service'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('pagamento_status'), fn ($query) => $query->where('pagamento_status', $request->string('pagamento_status')))
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', [
            'orders' => $orders,
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

        $seriesMensal = collect(range(5, 0))
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
}
