<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "creating" event.
     */
    public function creating(Order $order): void
    {
        if (! empty($order->protocolo)) {
            return;
        }

        // Gera o próximo protocolo REG no formato REG-YYYYMMDD-NNNNN.
        $date = now()->format('Ymd');
        $prefix = sprintf('REG-%s-', $date);

        $lastProtocol = Order::withTrashed()
            ->where('protocolo', 'like', $prefix.'%')
            ->latest('id')
            ->value('protocolo');

        $sequence = $lastProtocol ? ((int) substr($lastProtocol, -5)) + 1 : 1;

        $order->protocolo = $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
