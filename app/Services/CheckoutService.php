<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CheckoutService
{
    private const SUPPORTED_BILLING_TYPES = ['PIX', 'BOLETO', 'CREDIT_CARD'];

    public function __construct(
        private readonly LeadUserResolverService $leadUserResolverService
    ) {}

    public function createCheckoutSession(Lead $lead, Service $service, string $billingType = 'PIX'): array
    {
        $user = $this->resolveUserFromLead($lead);

        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'pendente',
            'valor' => $service->preco,
            'payment_provider' => 'asaas',
            'pagamento_status' => 'aguardando',
        ]);

        return $this->createAsaasPayment($order, $lead, $service, $user, $billingType);
    }

    public function createCheckoutSessionForOrder(Order $order, string $billingType = 'PIX'): array
    {
        $order->loadMissing(['user', 'service', 'lead']);

        $service = $order->service;

        if (! $service) {
            throw new RuntimeException('Pedido sem serviço vinculado.');
        }

        return $this->createAsaasPayment($order, $order->lead, $service, $order->user, $billingType);
    }

    public function getCheckoutSessionForOrder(Order $order): ?array
    {
        $order->loadMissing(['user', 'service', 'lead']);

        if ($order->pagamento_status === 'pago') {
            return null;
        }

        if (! $this->hasAsaasConfigured()) {
            return null;
        }

        if (! filled($order->asaas_payment_id)) {
            return null;
        }

        $response = $this->asaasClient()->get('/payments/'.$order->asaas_payment_id);

        if (! $response->successful()) {
            Log::warning('Falha ao consultar cobrança Asaas para o pedido.', [
                'order_id' => $order->id,
                'payment_id' => $order->asaas_payment_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $this->buildPaymentSession($order, $response->json());
    }

    protected function createAsaasPayment(Order $order, ?Lead $lead, Service $service, ?User $user, string $billingType): array
    {
        $billingType = strtoupper(trim($billingType));

        if (! in_array($billingType, self::SUPPORTED_BILLING_TYPES, true)) {
            throw new RuntimeException('Forma de pagamento inválida para a pesquisa.');
        }

        if (! $this->hasAsaasConfigured()) {
            throw new RuntimeException('Asaas não configurado. Atualize a integração antes de gerar a cobrança.');
        }

        $customerId = $this->ensureAsaasCustomer($lead, $user);

        $payload = [
            'customer' => $customerId,
            'billingType' => $billingType,
            'value' => (float) $order->valor,
            'dueDate' => now()->toDateString(),
            'description' => $service->nome,
            'externalReference' => 'order:'.$order->id,
            'callback' => [
                'successUrl' => route('regularizacao.sucesso', ['order_id' => $order->id]),
                'autoRedirect' => false,
            ],
        ];

        $response = $this->asaasClient()->post('/payments', $payload);

        if (! $response->successful()) {
            Log::error('Falha ao criar cobrança no Asaas.', [
                'order_id' => $order->id,
                'billing_type' => $billingType,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível criar a cobrança no Asaas. Verifique a configuração salva no painel administrativo.');
        }

        $payment = $response->json();
        $paymentId = (string) ($payment['id'] ?? '');

        if ($paymentId === '') {
            throw new RuntimeException('Asaas retornou resposta sem id da cobrança.');
        }

        $order->update([
            'payment_provider' => 'asaas',
            'asaas_customer_id' => $customerId,
            'asaas_payment_id' => $paymentId,
            'pagamento_status' => 'aguardando',
        ]);

        if ($lead) {
            $lead->update(['etapa' => 'pagamento']);
        }

        return $this->buildPaymentSession($order->fresh(['service']), $payment, $customerId);
    }

    protected function buildPaymentSession(Order $order, array $payment, ?string $customerId = null): array
    {
        $paymentId = (string) ($payment['id'] ?? $order->asaas_payment_id ?? '');
        $billingType = strtoupper((string) ($payment['billingType'] ?? 'PIX'));
        $invoiceUrl = (string) ($payment['invoiceUrl'] ?? '');
        $bankSlipUrl = (string) ($payment['bankSlipUrl'] ?? '');
        $paymentUrl = match ($billingType) {
            'BOLETO' => $bankSlipUrl !== '' ? $bankSlipUrl : $invoiceUrl,
            default => $invoiceUrl,
        };

        if ($paymentUrl === '') {
            $paymentUrl = route('regularizacao.index', ['order_id' => $order->id]);
        }

        $order->update([
            'payment_provider' => 'asaas',
            'asaas_customer_id' => $customerId ?: $order->asaas_customer_id,
            'asaas_payment_id' => $paymentId !== '' ? $paymentId : $order->asaas_payment_id,
            'payment_link_url' => $paymentUrl,
            'pagamento_status' => $order->pagamento_status === 'pago' ? 'pago' : 'aguardando',
        ]);

        return [
            'order_id' => $order->id,
            'payment_id' => $paymentId,
            'billing_type' => $billingType,
            'payment_url' => $paymentUrl,
            'invoice_url' => $invoiceUrl !== '' ? $invoiceUrl : $paymentUrl,
            'bank_slip_url' => $bankSlipUrl !== '' ? $bankSlipUrl : null,
            'pix' => $billingType === 'PIX' && $paymentId !== '' ? $this->fetchPixQrCode($paymentId) : null,
            'status' => (string) ($payment['status'] ?? $order->pagamento_status),
            'value' => (float) ($payment['value'] ?? $order->valor),
            'description' => (string) ($payment['description'] ?? ($order->service?->nome ?? '')),
            'due_date' => (string) ($payment['dueDate'] ?? now()->toDateString()),
        ];
    }

    protected function fetchPixQrCode(string $paymentId): ?array
    {
        $response = $this->asaasClient()->get('/payments/'.$paymentId.'/pixQrCode');

        if (! $response->successful()) {
            Log::warning('Falha ao consultar QR Code Pix no Asaas.', [
                'payment_id' => $paymentId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $payload = $response->json();

        return [
            'encoded_image' => (string) ($payload['encodedImage'] ?? ''),
            'payload' => (string) ($payload['payload'] ?? ''),
            'expiration_date' => (string) ($payload['expirationDate'] ?? ''),
        ];
    }

    protected function ensureAsaasCustomer(?Lead $lead, ?User $user): string
    {
        $document = preg_replace('/\D+/', '', (string) ($lead?->cpf_cnpj ?: $user?->cpf_cnpj ?: ''));
        $email = (string) ($lead?->email ?: $user?->email ?: '');

        if ($document !== '') {
            $existingByDoc = $this->asaasClient()->get('/customers', ['cpfCnpj' => $document]);

            if ($existingByDoc->successful()) {
                $data = $existingByDoc->json('data');
                if (is_array($data) && isset($data[0]['id'])) {
                    return (string) $data[0]['id'];
                }
            }
        }

        if ($email !== '') {
            $existingByEmail = $this->asaasClient()->get('/customers', ['email' => $email]);

            if ($existingByEmail->successful()) {
                $data = $existingByEmail->json('data');
                if (is_array($data) && isset($data[0]['id'])) {
                    return (string) $data[0]['id'];
                }
            }
        }

        $createPayload = [
            'name' => (string) ($lead?->nome ?: $user?->name ?: 'Cliente Regulariza'),
            'email' => $email !== '' ? $email : null,
            'cpfCnpj' => $document !== '' ? $document : null,
            'mobilePhone' => $this->normalizePhone($lead?->whatsapp ?: $user?->whatsapp ?: ''),
        ];

        $createPayload = array_filter($createPayload, static fn ($value) => $value !== null && $value !== '');
        $response = $this->asaasClient()->post('/customers', $createPayload);

        if (! $response->successful()) {
            Log::error('Falha ao criar cliente no Asaas.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $createPayload,
            ]);

            throw new RuntimeException('Não foi possível cadastrar cliente no Asaas.');
        }

        $customerId = (string) ($response->json('id') ?? '');

        if ($customerId === '') {
            throw new RuntimeException('Asaas retornou resposta sem id do cliente.');
        }

        return $customerId;
    }

    protected function resolveUserFromLead(Lead $lead): User
    {
        return $this->leadUserResolverService->resolve($lead);
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

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }
}
