<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Order;
use App\Models\SellerCommission;
use App\Models\User;
use App\Services\SellerCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\View\View;

class AnalystPanelController extends Controller
{
    public function dashboard(Request $request): View
    {
        $analyst = $request->user();
        $ordersQuery = $this->ordersQuery($analyst->id);
        $contractsQuery = Contract::query()->where('analyst_id', $analyst->id);

        $stats = [
            'clientes' => (int) User::query()->where('role', 'cliente')->where('referred_by_user_id', $analyst->id)->count(),
            'contratos' => (int) (clone $contractsQuery)->count(),
            'pagos' => (int) (clone $ordersQuery)->where('pagamento_status', 'pago')->count(),
            'comissoes_liberadas' => (float) SellerCommission::query()->where('seller_id', $analyst->id)->where('status', 'available')->sum('commission_amount'),
        ];

        $timeline = (clone $contractsQuery)
            ->with(['user', 'installments'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function (Contract $contract): array {
                $doc = (string) ($contract->user?->cpf_cnpj ?: '-');
                $entry = $contract->installments->firstWhere('installment_number', 0);
                $status = $entry && $entry->status === 'pago'
                    ? 'Aguardando parcelas 30/60/90'
                    : 'Aguardando pagamento da entrada';

                return [
                    'at' => $contract->updated_at,
                    'title' => "Cliente {$doc}",
                    'status' => $contract->status === 'concluido' ? 'Contrato concluído' : $status,
                    'protocol' => $contract->order?->protocolo ?: 'N/D',
                ];
            });

        $referralCode = $analyst->ensureReferralCode();
        $referralLink = route('regularizacao.index', ['indicacao' => $referralCode]);

        return view('analyst/dashboard', compact('stats', 'timeline', 'referralCode', 'referralLink'));
    }

    public function contracts(Request $request): View
    {
        $orders = $this->ordersQuery($request->user()->id)
            ->with(['user', 'lead', 'service'])
            ->latest()
            ->paginate(20);

        return view('analyst/contracts', compact('orders'));
    }

    public function commissions(Request $request): View
    {
        $commissions = SellerCommission::query()
            ->with(['order.user'])
            ->where('seller_id', $request->user()->id)
            ->latest('id')
            ->paginate(20);

        $totals = [
            'pending' => (float) SellerCommission::query()->where('seller_id', $request->user()->id)->whereIn('status', ['pending', 'available'])->sum('commission_amount'),
            'paid' => (float) SellerCommission::query()->where('seller_id', $request->user()->id)->where('status', 'paid')->sum('commission_amount'),
        ];

        return view('analyst/commissions', compact('commissions', 'totals'));
    }

    public function requestPayout(
        Request $request,
        SellerCommission $commission,
        SellerCommissionService $commissionService
    ): RedirectResponse
    {
        $user = $request->user();

        if ((int) $commission->seller_id !== (int) $user->id) {
            abort(403);
        }

        if ($commission->status !== 'available') {
            return back()->withErrors(['payout' => 'Esta comissão ainda não está liberada para saque.']);
        }

        if ($commission->available_at && $commission->available_at->isFuture()) {
            return back()->withErrors(['payout' => 'Esta comissão ainda está no período de retenção de 24 horas.']);
        }

        if (empty($user->pix_key)) {
            return back()->withErrors(['payout' => 'Cadastre sua chave PIX no perfil antes de solicitar o saque.']);
        }

        if ($commission->payout_requested_at) {
            return back()->withErrors(['payout' => 'Saque já solicitado para esta comissão.']);
        }

        $commission->update([
            'payout_requested_at' => now(),
            'notes' => trim((string) $commission->notes.' Solicitação de saque PIX enviada em '.now()->format('d/m/Y H:i').'.'),
        ]);

        try {
            $commissionService->payoutCommissionById((int) $commission->id);

            return back()->with('success', 'Saque PIX processado automaticamente via Asaas.');
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'payout' => 'Solicitação registrada, mas o pagamento automático não foi concluído: '.$exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            return back()->withErrors([
                'payout' => 'Solicitação registrada, mas houve falha no pagamento automático via Asaas.',
            ]);
        }
    }

    public function clients(Request $request): View
    {
        $analyst = $request->user();

        $clients = User::query()
            ->where('role', 'cliente')
            ->where('referred_by_user_id', $analyst->id)
            ->withCount(['orders', 'sacTickets'])
            ->latest('id')
            ->paginate(20);

        $pipeline = [
            'novo' => 0,
            'pesquisa_analise' => 0,
            'aguardando_pagamento' => 0,
            'em_regularizacao' => 0,
            'concluido' => 0,
        ];

        foreach ($clients as $client) {
            $lastOrder = $client->orders()->latest('id')->first();
            if (! $lastOrder) {
                $pipeline['novo']++;
                continue;
            }

            if ($lastOrder->pagamento_status !== 'pago') {
                $pipeline['aguardando_pagamento']++;
            } elseif ($lastOrder->status === 'em_andamento') {
                $pipeline['pesquisa_analise']++;
            } elseif ($lastOrder->status === 'concluido') {
                $pipeline['concluido']++;
            } else {
                $pipeline['em_regularizacao']++;
            }
        }

        return view('analyst/clients', compact('clients', 'pipeline'));
    }

    private function ordersQuery(int $analystId)
    {
        return Order::query()
            ->where(function ($query) use ($analystId) {
                $query->whereHas('lead', fn ($q) => $q->where('referred_by_user_id', $analystId))
                    ->orWhereHas('user', fn ($q) => $q->where('referred_by_user_id', $analystId));
            });
    }

    private function humanStatus(Order $order): string
    {
        if ($order->pagamento_status !== 'pago') {
            return 'Aguardando pagamento da pesquisa';
        }

        return match ($order->status) {
            'pendente' => 'Pesquisa paga | Em triagem',
            'em_andamento' => 'Pesquisa em análise',
            'concluido' => 'Contrato concluído',
            'cancelado' => 'Cancelado',
            default => 'Em andamento',
        };
    }
}
