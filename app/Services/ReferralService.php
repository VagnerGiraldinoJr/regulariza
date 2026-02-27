<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function applyCreditForPaidOrder(Order $order): void
    {
        if ($order->pagamento_status !== 'pago' || $order->referral_credited_at !== null) {
            return;
        }

        $order->loadMissing(['user', 'lead']);

        $referrerId = $order->user?->referred_by_user_id ?? $order->lead?->referred_by_user_id;

        if (! $referrerId || $order->user_id === $referrerId) {
            return;
        }

        $credit = round(((float) $order->valor) * 0.10, 2);

        if ($credit <= 0) {
            return;
        }

        DB::transaction(function () use ($order, $referrerId, $credit): void {
            $freshOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $freshOrder || $freshOrder->referral_credited_at !== null) {
                return;
            }

            $referrer = User::query()->lockForUpdate()->find($referrerId);

            if (! $referrer) {
                return;
            }

            $referrer->update([
                'referral_credits' => (float) $referrer->referral_credits + $credit,
            ]);

            $freshOrder->update([
                'referral_credit_amount' => $credit,
                'referral_credited_at' => now(),
            ]);
        });
    }
}

