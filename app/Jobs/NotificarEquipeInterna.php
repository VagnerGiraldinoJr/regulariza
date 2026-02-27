<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotificarEquipeInterna implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    /**
     * Notifica equipe interna sobre novo pedido pago.
     */
    public function handle(): void
    {
        $recipients = User::query()
            ->whereIn('role', ['admin', 'atendente'])
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        Log::info('Novo pedido pago para equipe interna.', [
            'order_id' => $this->order->id,
            'protocolo' => $this->order->protocolo,
            'recipients' => $recipients,
        ]);
    }
}
