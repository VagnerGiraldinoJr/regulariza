<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiBrasilService
{
    public function consultarSaldo(): array
    {
        $path = (string) config('services.apibrasil.balance_path', '/api/v2/user');
        $method = strtoupper((string) config('services.apibrasil.balance_method', 'GET'));
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        $response = $this->send($method, $url, []);
        $payload = $this->payload($response);
        $balance = $this->extractBalance($payload);

        return [
            'status' => $response->successful() ? 'success' : 'error',
            'http_status' => $response->status(),
            'endpoint' => $url,
            'balance' => $balance,
            'response_payload' => $payload,
            'error_message' => $response->successful() ? null : $this->errorMessage($response),
        ];
    }

    public function consultarCatalogo(string $consultationKey, string $documento): array
    {
        $catalog = (array) config('apibrasil_catalog.consultations', []);
        $definition = $catalog[$consultationKey] ?? null;

        if (! is_array($definition)) {
            throw new RuntimeException('Consulta não encontrada no catálogo API Brasil.');
        }

        $digits = preg_replace('/\D+/', '', $documento);

        if (! in_array(strlen($digits), [11, 14], true)) {
            throw new RuntimeException('Documento inválido. Informe CPF (11) ou CNPJ (14) com números válidos.');
        }

        $resolvedType = strlen($digits) === 14 ? 'cnpj' : 'cpf';
        $allowedType = (string) ($definition['document_type'] ?? 'both');

        if ($allowedType !== 'both' && $allowedType !== $resolvedType) {
            throw new RuntimeException("A consulta selecionada aceita apenas documento do tipo {$allowedType}.");
        }

        $path = str_replace('{document}', $digits, (string) ($definition['path'] ?? ''));
        $method = strtoupper((string) ($definition['method'] ?? 'GET'));
        $bodyTemplate = (array) ($definition['body'] ?? []);
        $payload = $this->resolveTemplate($bodyTemplate, $digits);
        if (array_key_exists('homolog', $payload)) {
            $payload['homolog'] = (bool) config('services.apibrasil.homolog', false);
        }
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        $response = $this->send($method, $url, $payload);
        $responsePayload = $this->payload($response);
        $attempts = [[
            'body' => $payload,
            'http_status' => $response->status(),
        ]];

        if ($this->shouldRetryWithTipoFallback($method, $payload, $response)) {
            foreach ($this->tipoFallbacks($definition, $payload) as $fallbackTipo) {
                if ($fallbackTipo === (string) $payload['tipo']) {
                    continue;
                }

                $retryPayload = $payload;
                $retryPayload['tipo'] = $fallbackTipo;
                $retryResponse = $this->send($method, $url, $retryPayload);
                $retryResponsePayload = $this->payload($retryResponse);

                $attempts[] = [
                    'body' => $retryPayload,
                    'http_status' => $retryResponse->status(),
                ];

                $payload = $retryPayload;
                $response = $retryResponse;
                $responsePayload = $retryResponsePayload;

                if ($this->isSuccessfulResponse($response, $responsePayload)) {
                    break;
                }

                if ($response->status() !== 400) {
                    break;
                }
            }
        }

        $isSuccess = $this->isSuccessfulResponse($response, $responsePayload);

        return [
            'document' => $digits,
            'document_type' => $resolvedType,
            'status' => $isSuccess ? 'success' : 'error',
            'http_status' => $response->status(),
            'endpoint' => $url,
            'request_payload' => [
                'method' => $method,
                'path' => '/'.ltrim($path, '/'),
                'document' => $digits,
                'body' => $payload,
                'attempts' => $attempts,
            ],
            'response_payload' => $responsePayload,
            'error_message' => $isSuccess ? null : $this->errorMessage($response, $responsePayload),
            'consultation_key' => $consultationKey,
            'consultation_title' => (string) ($definition['title'] ?? $consultationKey),
            'consultation_category' => (string) ($definition['category'] ?? 'geral'),
        ];
    }

    private function shouldRetryWithTipoFallback(string $method, array $payload, Response $response): bool
    {
        return $method === 'POST'
            && array_key_exists('tipo', $payload)
            && $response->status() === 400;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function tipoFallbacks(array $definition, array $payload): array
    {
        $configured = data_get($definition, 'tipo_fallbacks');

        if (is_array($configured)) {
            return collect($configured)
                ->filter(fn ($item) => is_string($item) && trim($item) !== '')
                ->map(fn ($item) => trim((string) $item))
                ->unique()
                ->values()
                ->all();
        }

        $currentTipo = trim((string) ($payload['tipo'] ?? ''));

        if ($currentTipo === '') {
            return [];
        }

        return collect([
            $currentTipo,
            str_replace('-', '_', $currentTipo),
            str_replace('_', '-', $currentTipo),
            str_replace('-pj', '', $currentTipo),
            str_replace('_pj', '', $currentTipo),
        ])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim((string) $item))
            ->unique()
            ->values()
            ->all();
    }

    public function consultarDocumento(string $documento): array
    {
        $digits = preg_replace('/\D+/', '', $documento);

        if (! in_array(strlen($digits), [11, 14], true)) {
            throw new RuntimeException('Documento inválido. Informe CPF (11) ou CNPJ (14) com números válidos.');
        }

        $tipo = strlen($digits) === 14 ? 'cnpj' : 'cpf';
        $path = $this->pathFor($tipo, $digits);
        $method = $this->methodFor($tipo);
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        $response = $this->send($method, $url, ['document' => $digits]);

        return [
            'document' => $digits,
            'document_type' => $tipo,
            'status' => $response->successful() ? 'success' : 'error',
            'http_status' => $response->status(),
            'endpoint' => $url,
            'request_payload' => [
                'method' => $method,
                'path' => '/'.ltrim($path, '/'),
                'document' => $digits,
            ],
            'response_payload' => $this->payload($response),
            'error_message' => $response->successful() ? null : $this->errorMessage($response),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.apibrasil.base_url', 'https://apibrasil.com.br/api'), '/');
    }

    private function pathFor(string $tipo, string $documento): string
    {
        $default = $tipo === 'cnpj' ? '/cnpj/{document}' : '/cpf/{document}';
        $path = (string) config("services.apibrasil.{$tipo}_path", $default);

        return str_replace('{document}', $documento, $path);
    }

    private function methodFor(string $tipo): string
    {
        $method = strtoupper((string) config("services.apibrasil.{$tipo}_method", 'GET'));

        return in_array($method, ['GET', 'POST', 'PUT'], true) ? $method : 'GET';
    }

    private function payload(Response $response): mixed
    {
        $json = $response->json();

        if (is_array($json)) {
            return $json;
        }

        return ['raw' => $response->body()];
    }

    private function errorMessage(Response $response, mixed $payload = null): string
    {
        if (is_array($payload)) {
            foreach (['message', 'error_message', 'error', 'response.message'] as $path) {
                $value = data_get($payload, $path);
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        $body = $response->body();
        $body = mb_substr(trim($body), 0, 600);

        if ($body === '') {
            return 'A API Brasil retornou erro sem detalhes.';
        }

        return $body;
    }

    private function isSuccessfulResponse(Response $response, mixed $payload): bool
    {
        if (! $response->successful()) {
            return false;
        }

        if (! is_array($payload)) {
            return true;
        }

        foreach (['error', 'success'] as $flagKey) {
            $flag = data_get($payload, $flagKey);

            if ($flagKey === 'error' && $this->toNullableBoolean($flag) === true) {
                return false;
            }

            if ($flagKey === 'success' && $this->toNullableBoolean($flag) === false) {
                return false;
            }
        }

        return true;
    }

    private function toNullableBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        if (in_array($normalized, ['1', 'true', 'yes', 'sim'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'nao', 'não'], true)) {
            return false;
        }

        return null;
    }

    private function send(string $method, string $url, array $payload): Response
    {
        $tokenHeader = (string) config('services.apibrasil.token_header', 'Authorization');
        $tokenPrefix = trim((string) config('services.apibrasil.token_prefix', 'Bearer'));
        $token = trim((string) config('services.apibrasil.token', ''));

        if ($token === '') {
            throw new RuntimeException('APIBRASIL_TOKEN não configurado.');
        }

        $headerValue = $tokenPrefix !== '' ? $tokenPrefix.' '.$token : $token;

        $request = Http::timeout((int) config('services.apibrasil.timeout', 20))
            ->acceptJson()
            ->withHeaders([
                $tokenHeader => $headerValue,
                'Content-Type' => 'application/json',
            ]);

        return match ($method) {
            'POST' => $request->post($url, $payload),
            'PUT' => $request->put($url, $payload),
            default => $request->get($url, $payload),
        };
    }

    private function resolveTemplate(array $template, string $document): array
    {
        $json = json_encode($template, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return ['document' => $document];
        }

        $json = str_replace('{document}', $document, $json);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : ['document' => $document];
    }

    private function extractBalance(mixed $payload): ?float
    {
        if (! is_array($payload)) {
            return null;
        }

        $candidates = [
            ['balance'],
            ['saldo'],
            ['credits'],
            ['creditos'],
            ['data', 'balance'],
            ['data', 'saldo'],
            ['data', 'credits'],
            ['user', 'balance'],
            ['wallet', 'balance'],
            ['account', 'balance'],
        ];

        foreach ($candidates as $path) {
            $value = $this->readPath($payload, $path);
            $parsed = $this->parseMoney($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return $this->searchNumericBalance($payload);
    }

    private function readPath(array $payload, array $path): mixed
    {
        $node = $payload;

        foreach ($path as $segment) {
            if (! is_array($node) || ! array_key_exists($segment, $node)) {
                return null;
            }
            $node = $node[$segment];
        }

        return $node;
    }

    private function searchNumericBalance(array $payload): ?float
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $nested = $this->searchNumericBalance($value);
                if ($nested !== null) {
                    return $nested;
                }

                continue;
            }

            if (! is_string($key)) {
                continue;
            }

            $isBalanceKey = str_contains(mb_strtolower($key), 'saldo')
                || str_contains(mb_strtolower($key), 'balance')
                || str_contains(mb_strtolower($key), 'credit');

            if (! $isBalanceKey) {
                continue;
            }

            $parsed = $this->parseMoney($value);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseMoney(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
