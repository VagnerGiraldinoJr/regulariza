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
        $endpoint = $this->buildEndpoint('send-text');

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
     * Envia imagem com legenda no WhatsApp via Z-API e registra log.
     */
    public function enviarImagem(string $telefone, string $imageUrl, string $caption = '', string $evento = 'lembrete', ?int $userId = null, ?int $orderId = null): array
    {
        $endpoint = $this->buildEndpoint('send-image');

        $response = Http::withHeaders([
            'Client-Token' => (string) config('zapi.client_token'),
        ])->post($endpoint, [
            'phone' => preg_replace('/\D+/', '', $telefone),
            'image' => $imageUrl,
            'caption' => $caption,
        ]);

        $status = $response->successful() ? 'enviado' : 'falhou';
        $payload = $response->json() ?? ['raw' => $response->body()];

        WhatsappLog::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'telefone' => $telefone,
            'evento' => $evento,
            'mensagem' => $caption !== '' ? $caption : '[imagem]',
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

    private function buildEndpoint(string $resource): string
    {
        return sprintf(
            'https://api.z-api.io/instances/%s/token/%s/%s',
            config('zapi.instance'),
            config('zapi.token'),
            $resource
        );
    }
}
