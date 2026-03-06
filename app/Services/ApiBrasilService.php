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

        return [
            'document' => $digits,
            'document_type' => $resolvedType,
            'status' => $response->successful() ? 'success' : 'error',
            'http_status' => $response->status(),
            'endpoint' => $url,
            'request_payload' => [
                'method' => $method,
                'path' => '/'.ltrim($path, '/'),
                'document' => $digits,
                'body' => $payload,
            ],
            'response_payload' => $this->payload($response),
            'error_message' => $response->successful() ? null : $this->errorMessage($response),
            'consultation_key' => $consultationKey,
            'consultation_title' => (string) ($definition['title'] ?? $consultationKey),
            'consultation_category' => (string) ($definition['category'] ?? 'geral'),
        ];
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

    private function errorMessage(Response $response): string
    {
        $body = $response->body();
        $body = mb_substr(trim($body), 0, 600);

        if ($body === '') {
            return 'A API Brasil retornou erro sem detalhes.';
        }

        return $body;
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
