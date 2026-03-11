<?php

namespace App\Services;

use App\Jobs\EnviarBoasVindasWhatsApp;
use App\Jobs\NotificarEquipeInterna;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class PaidOrderReconciliationService
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly SellerCommissionService $sellerCommissionService,
        private readonly LeadUserResolverService $leadUserResolverService
    ) {
    }

    public function reconcile(Order $order, array $payment = [], bool $dispatchNotifications = true): Order
    {
        $order->loadMissing(['lead', 'user']);

        $wasPaid = $order->pagamento_status === 'pago';
        $user = $this->ensureOrderUser($order);

        $paymentId = trim((string) ($payment['id'] ?? ''));
        $invoiceUrl = trim((string) ($payment['invoiceUrl'] ?? ''));

        $updates = [
            'payment_provider' => 'asaas',
            'pagamento_status' => 'pago',
            'status' => $order->status === 'concluido' ? 'concluido' : 'em_andamento',
        ];

        if ($paymentId !== '' && $paymentId !== (string) $order->asaas_payment_id) {
            $updates['asaas_payment_id'] = $paymentId;
        }

        if ($invoiceUrl !== '' && $invoiceUrl !== (string) $order->payment_link_url) {
            $updates['payment_link_url'] = $invoiceUrl;
        }

        if ($order->pago_em === null) {
            $updates['pago_em'] = now();
        }

        if ($user && (int) $order->user_id !== (int) $user->id) {
            $updates['user_id'] = $user->id;
        }

        $dirtyUpdates = [];
        foreach ($updates as $attribute => $value) {
            if ($order->getAttribute($attribute) != $value) {
                $dirtyUpdates[$attribute] = $value;
            }
        }

        if ($dirtyUpdates !== []) {
            $order->update($dirtyUpdates);
        }

        $order->refresh()->loadMissing(['lead', 'user']);

        if ($order->lead && $order->lead->etapa !== 'concluido') {
            $order->lead->update(['etapa' => 'concluido']);
        }

        if ($order->user) {
            $this->ensurePortalAccess($order->user);
        }

        $this->referralService->applyCreditForPaidOrder($order);
        $this->sellerCommissionService->registerResearchCommission($order);

        if ($dispatchNotifications && ! $wasPaid) {
            EnviarBoasVindasWhatsApp::dispatch($order);
            NotificarEquipeInterna::dispatch($order);
        }

        return $order->fresh(['lead', 'user']);
    }

    private function ensureOrderUser(Order $order): ?User
    {
        $user = $order->user;

        if ($user) {
            return $user;
        }

        $lead = $order->lead;
        if (! $lead instanceof Lead) {
            return null;
        }

        return $this->leadUserResolverService->resolve($lead);
    }

    private function ensurePortalAccess(User $user): void
    {
        $updates = [];

        if ($user->role !== 'cliente') {
            $updates['role'] = 'cliente';
        }

        if (! filled($user->portal_token) || $user->portal_token_expires_at?->isPast()) {
            $updates['portal_token'] = Str::random(64);
            $updates['portal_token_expires_at'] = now()->addDays(7);
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }
}
