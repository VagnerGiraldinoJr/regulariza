<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarBoasVindasWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    /**
     * Dispara mensagem inicial com acesso ao portal para o cliente.
     */
    public function handle(ZApiService $zApiService): void
    {
        $user = $this->order->user;

        if (! $user || empty($user->whatsapp)) {
            return;
        }

        $mensagem = $zApiService->renderTemplate('boas_vindas', [
            'nome' => $user->name,
            'protocolo' => $this->order->protocolo,
            'link' => route('portal.dashboard'),
        ]);

        $zApiService->enviarMensagem(
            telefone: $user->whatsapp,
            mensagem: $mensagem,
            evento: 'boas_vindas',
            userId: $user->id,
            orderId: $this->order->id
        );
    }
}
