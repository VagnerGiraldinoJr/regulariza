<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiBrasilService
{
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
}
