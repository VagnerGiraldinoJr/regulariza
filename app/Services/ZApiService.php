<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;

class ZApiService
{
    /**
     * Envia mensagem no WhatsApp via Z-API e registra log.
     */
    public function enviarMensagem(string $telefone, string $mensagem, string $evento = 'lembrete', ?int $userId = null, ?int $orderId = null): array
    {
        $endpoint = sprintf(
            'https://api.z-api.io/instances/%s/token/%s/send-text',
            config('zapi.instance'),
            config('zapi.token')
        );

        $response = Http::withHeaders([
            'Client-Token' => (string) config('zapi.client_token'),
        ])->post($endpoint, [
            'phone' => preg_replace('/\D+/', '', $telefone),
            'message' => $mensagem,
        ]);

        $status = $response->successful() ? 'enviado' : 'falhou';
        $payload = $response->json() ?? ['raw' => $response->body()];

        WhatsappLog::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'telefone' => $telefone,
            'evento' => $evento,
            'mensagem' => $mensagem,
            'status' => $status,
            'zapi_response' => $payload,
            'enviado_em' => now(),
        ]);

        return [
            'status' => $status,
            'response' => $payload,
        ];
    }

    /**
     * Renderiza template de mensagem com placeholders.
     */
    public function renderTemplate(string $key, array $replacements = []): string
    {
        $template = (string) config('zapi.templates.'.$key, '');

        foreach ($replacements as $placeholder => $value) {
            $template = str_replace('{'.$placeholder.'}', (string) $value, $template);
        }

        return $template;
    }
}
