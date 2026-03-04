<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly ReferralService $referralService,
        private readonly SellerCommissionService $sellerCommissionService
    ) {}

    public function createCheckoutSession(Lead $lead, Service $service): string
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

        return $this->createAsaasPixPayment($order, $lead, $service, $user);
    }

    public function createCheckoutSessionForOrder(Order $order): string
    {
        $order->loadMissing(['user', 'service', 'lead']);

        $service = $order->service;

        if (! $service) {
            throw new RuntimeException('Pedido sem serviço vinculado.');
        }

        return $this->createAsaasPixPayment($order, $order->lead, $service, $order->user);
    }

    protected function createAsaasPixPayment(Order $order, ?Lead $lead, Service $service, ?User $user): string
    {
        if (! $this->hasAsaasConfigured()) {
            return $this->approveLocally($order, $lead, isResend: true);
        }

        $customerId = $this->ensureAsaasCustomer($lead, $user);

        $payload = [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $order->valor,
            'dueDate' => now()->toDateString(),
            'description' => $service->nome,
            'externalReference' => 'order:'.$order->id,
        ];

        $response = $this->asaasClient()->post('/payments', $payload);

        if (! $response->successful()) {
            Log::error('Falha ao criar cobrança Pix no Asaas.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível criar a cobrança Pix no Asaas. Verifique ASAAS_API_KEY no .env.');
        }

        $payment = $response->json();
        $paymentId = (string) ($payment['id'] ?? '');
        $invoiceUrl = (string) ($payment['invoiceUrl'] ?? '');

        if ($paymentId === '') {
            throw new RuntimeException('Asaas retornou resposta sem id da cobrança.');
        }

        if ($invoiceUrl === '') {
            $invoiceUrl = route('regularizacao.index').'?order_id='.$order->id;
        }

        $order->update([
            'payment_provider' => 'asaas',
            'asaas_customer_id' => $customerId,
            'asaas_payment_id' => $paymentId,
            'payment_link_url' => $invoiceUrl,
            'pagamento_status' => 'aguardando',
        ]);

        if ($lead) {
            $lead->update(['etapa' => 'pagamento']);
        }

        return $invoiceUrl;
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

    protected function approveLocally(Order $order, ?Lead $lead, bool $isResend = false): string
    {
        $order->update([
            'pagamento_status' => 'pago',
            'status' => 'em_andamento',
            'pago_em' => now(),
        ]);

        if ($lead) {
            $lead->update(['etapa' => 'concluido']);
        }

        $this->referralService->applyCreditForPaidOrder($order);
        $this->sellerCommissionService->registerResearchCommission($order);

        $suffix = $isResend ? '&resend=1' : '';

        return route('regularizacao.sucesso').'?order_id='.$order->id.'&mock_checkout=1'.$suffix;
    }

    protected function resolveUserFromLead(Lead $lead): User
    {
        $email = $lead->email ?: 'cliente+'.Str::lower(Str::random(12)).'@regulariza.local';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $lead->nome ?: 'Cliente Regulariza',
                'cpf_cnpj' => $lead->cpf_cnpj,
                'whatsapp' => $lead->whatsapp,
                'referred_by_user_id' => $lead->referred_by_user_id,
                'role' => 'cliente',
                'password' => Str::password(12),
            ]
        );

        if (! $user->referred_by_user_id && $lead->referred_by_user_id && $user->id !== $lead->referred_by_user_id) {
            $user->update(['referred_by_user_id' => $lead->referred_by_user_id]);
        }

        return $user;
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
