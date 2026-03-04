<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AsaasPixTransferService
{
    public function transferToSeller(User $seller, float $amount, string $description): array
    {
        if (! $this->hasAsaasConfigured()) {
            throw new RuntimeException('Asaas não configurado para transferências.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Valor de transferência inválido.');
        }

        if (empty($seller->pix_key)) {
            throw new RuntimeException('Vendedor sem chave Pix cadastrada.');
        }

        $payload = [
            'value' => round($amount, 2),
            'operationType' => 'PIX',
            'pixAddressKey' => (string) $seller->pix_key,
            'description' => $description,
        ];

        $response = $this->asaasClient()->post('/transfers', $payload);

        if (! $response->successful()) {
            Log::error('Falha na transferência Pix Asaas para vendedor.', [
                'seller_id' => $seller->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível efetuar transferência Pix pelo Asaas.');
        }

        return (array) $response->json();
    }

    protected function hasAsaasConfigured(): bool
    {
        $baseUrl = (string) config('services.asaas.base_url');
        $apiKey = (string) config('services.asaas.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return false;
        }

        if (str_starts_with($apiKey, 'COLE_')) {
            return false;
        }

        return true;
    }

    protected function asaasClient(): PendingRequest
    {
        return Http::baseUrl((string) config('services.asaas.base_url'))
            ->withHeaders([
                'access_token' => (string) config('services.asaas.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson();
    }
}
