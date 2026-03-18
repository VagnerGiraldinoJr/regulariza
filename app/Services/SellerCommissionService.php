<?php

namespace App\Services;

use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\SellerCommission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SellerCommissionService
{
    public function __construct(private readonly AsaasPixTransferService $pixTransferService) {}

    public function registerResearchCommission(Order $order): void
    {
        if ($order->pagamento_status !== 'pago') {
            return;
        }

        $order->loadMissing(['lead', 'user']);
        $sellerId = $this->resolveOrAssignSellerForOrder($order);

        if (! $sellerId) {
            return;
        }

        $rate = (float) config('services.sales.research_commission_rate', 0.30);
        $baseAmount = (float) $order->valor;
        $commissionAmount = round($baseAmount * $rate, 2);

        if ($commissionAmount <= 0) {
            return;
        }

        $existing = SellerCommission::query()
            ->where('order_id', $order->id)
            ->where('source_type', 'research')
            ->whereNull('source_id')
            ->exists();

        if ($existing) {
            return;
        }

        SellerCommission::query()->create([
            'order_id' => $order->id,
            'seller_id' => $sellerId,
            'source_type' => 'research',
            'source_id' => null,
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
            'available_at' => ($order->pago_em ?: now())->copy()->addHours((int) config('services.sales.commission_hold_hours', 24)),
            'notes' => 'Comissão da pesquisa paga pelo cliente.',
        ]);
    }

    public function registerContractInstallmentCommission(ContractInstallment $installment): void
    {
        if ($installment->status !== 'pago') {
            return;
        }

        $installment->loadMissing(['contract', 'contract.order', 'contract.order.lead', 'contract.order.user']);
        $contract = $installment->contract;
        $order = $contract?->order;

        if (! $contract || ! $order) {
            return;
        }

        $sellerId = (int) ($contract->analyst_id ?: $order->lead?->referred_by_user_id ?: $order->user?->referred_by_user_id ?: 0);

        if ($sellerId <= 0) {
            $sellerId = (int) ($this->resolveOrAssignSellerForOrder($order) ?: 0);
        }

        if ($sellerId <= 0) {
            return;
        }

        $rate = (float) config('services.sales.installment_commission_rate', 0.40);
        $baseAmount = (float) $installment->amount;
        $commissionAmount = round($baseAmount * $rate, 2);

        if ($commissionAmount <= 0) {
            return;
        }

        $existing = SellerCommission::query()
            ->where('order_id', $order->id)
            ->where('source_type', 'contract_installment')
            ->where('source_id', $installment->id)
            ->exists();

        if ($existing) {
            return;
        }

        SellerCommission::query()->create([
            'order_id' => $order->id,
            'seller_id' => $sellerId,
            'source_type' => 'contract_installment',
            'source_id' => $installment->id,
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
            'available_at' => ($installment->paid_at ?: now())->copy()->addHours((int) config('services.sales.commission_hold_hours', 24)),
            'notes' => 'Comissão da parcela do contrato: '.$installment->label,
        ]);
    }

    public function releaseDueCommissions(): int
    {
        return SellerCommission::query()
            ->where('status', 'pending')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now())
            ->update(['status' => 'available']);
    }

    public function payoutAvailableCommissions(): array
    {
        $summary = ['paid' => 0, 'skipped' => 0, 'failed' => 0];

        SellerCommission::query()
            ->with('seller')
            ->where('status', 'available')
            ->whereNotNull('payout_requested_at')
            ->orderBy('id')
            ->chunkById(100, function ($commissions) use (&$summary): void {
                foreach ($commissions as $commission) {
                    try {
                        $this->payoutCommissionById((int) $commission->id);
                        $summary['paid']++;
                    } catch (RuntimeException $exception) {
                        $summary['skipped']++;
                    } catch (\Throwable $exception) {
                        $summary['failed']++;
                    }
                }
            });

        return $summary;
    }

    public function payoutCommissionById(int $commissionId): SellerCommission
    {
        return DB::transaction(function () use ($commissionId): SellerCommission {
            $commission = SellerCommission::query()
                ->with('seller')
                ->lockForUpdate()
                ->find($commissionId);

            if (! $commission) {
                throw new RuntimeException('Comissão não encontrada.');
            }

            if ($commission->status !== 'available') {
                throw new RuntimeException('Comissão não está liberada para pagamento.');
            }

            if (! $commission->payout_requested_at) {
                throw new RuntimeException('Saque não solicitado para esta comissão.');
            }

            $seller = $commission->seller;

            if (! $seller || empty($seller->pix_key)) {
                throw new RuntimeException('Vendedor sem chave PIX cadastrada.');
            }

            $transfer = $this->pixTransferService->transferToSeller(
                seller: $seller,
                amount: (float) $commission->commission_amount,
                description: 'Comissão pedido #'.$commission->order_id
            );

            $commission->update([
                'status' => 'paid',
                'paid_at' => now(),
                'asaas_transfer_id' => (string) ($transfer['id'] ?? null),
                'notes' => trim((string) $commission->notes.' Pagamento PIX automático confirmado via Asaas.'),
            ]);

            return $commission;
        });
    }

    private function resolveOrAssignSellerForOrder(Order $order): ?int
    {
        $sellerId = (int) ($order->lead?->referred_by_user_id ?: $order->user?->referred_by_user_id ?: 0);

        if ($sellerId > 0) {
            return $sellerId;
        }

        $defaultAnalyst = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->where('email', (string) config('services.sales.default_analyst_email'))
            ->first()
            ?? User::query()->whereIn('role', ['analista', 'vendedor'])->orderBy('id')->first();

        if (! $defaultAnalyst) {
            return null;
        }

        if ($order->lead && ! $order->lead->referred_by_user_id) {
            $order->lead->update(['referred_by_user_id' => $defaultAnalyst->id]);
        }

        if ($order->user && ! $order->user->referred_by_user_id && $order->user->id !== $defaultAnalyst->id) {
            $order->user->update(['referred_by_user_id' => $defaultAnalyst->id]);
        }

        return $defaultAnalyst->id;
    }
}
