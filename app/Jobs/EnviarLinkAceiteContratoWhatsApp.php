<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Services\ZApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EnviarLinkAceiteContratoWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contract $contract)
    {
    }

    public function handle(ZApiService $zApiService): void
    {
        $this->contract->loadMissing(['order', 'user']);

        $user = $this->contract->user;
        $acceptanceUrl = $this->contract->acceptanceUrl();

        if (! $user || empty($user->whatsapp) || ! filled($acceptanceUrl)) {
            return;
        }

        try {
            $mensagem = $zApiService->renderTemplate('contrato_aceite', [
                'nome' => $user->name ?: 'cliente',
                'protocolo' => $this->contract->order?->protocolo ?: $this->contract->id,
                'link' => $acceptanceUrl,
            ]);

            $zApiService->enviarMensagem(
                telefone: (string) $user->whatsapp,
                mensagem: $mensagem,
                evento: 'contrato_aceite',
                userId: $user->id,
                orderId: $this->contract->order_id
            );
        } catch (\Throwable $exception) {
            Log::warning('Falha ao enviar link de aceite do contrato por WhatsApp.', [
                'contract_id' => $this->contract->id,
                'order_id' => $this->contract->order_id,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
