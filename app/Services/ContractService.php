<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ContractService
{
    public function createForOrder(
        Order $order,
        float $debtAmount,
        float $feeAmount,
        float $entryPercentage = 50.0,
        ?UploadedFile $documentFile = null
    ): Contract {
        $order->loadMissing(['user', 'lead']);

        if ((float) $feeAmount <= 0) {
            throw new RuntimeException('O valor de honorários deve ser maior que zero.');
        }

        if ((float) $entryPercentage <= 0 || (float) $entryPercentage >= 100) {
            throw new RuntimeException('Percentual de entrada inválido.');
        }

        return DB::transaction(function () use ($order, $debtAmount, $feeAmount, $entryPercentage, $documentFile): Contract {
            $existing = Contract::query()->where('order_id', $order->id)->first();
            if ($existing) {
                throw new RuntimeException('Já existe contrato vinculado a este pedido.');
            }

            $analystId = $this->resolveAnalystId($order);
            $entryAmount = round($feeAmount * ($entryPercentage / 100), 2);
            $entryPercentageLabel = rtrim(rtrim(number_format($entryPercentage, 2, '.', ''), '0'), '.');
            $remaining = round($feeAmount - $entryAmount, 2);
            $baseInstallment = floor(($remaining / 3) * 100) / 100;
            $amounts = [$baseInstallment, $baseInstallment, round($remaining - ($baseInstallment * 2), 2)];

            $contract = Contract::query()->create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'analyst_id' => $analystId,
                'debt_amount' => round($debtAmount, 2),
                'fee_amount' => round($feeAmount, 2),
                'entry_percentage' => round($entryPercentage, 2),
                'entry_amount' => $entryAmount,
                'installments_count' => 3,
                'status' => 'aguardando_aceite',
                'payment_provider' => 'asaas',
                'asaas_customer_id' => null,
                'document_path' => $documentFile?->store('contracts'),
                'acceptance_token' => Str::random(64),
                'sent_for_acceptance_at' => now(),
                'acceptance_expires_at' => now()->addDays(7),
            ]);

            $entry = ContractInstallment::query()->create([
                'contract_id' => $contract->id,
                'order_id' => $order->id,
                'installment_number' => 0,
                'label' => "Entrada ({$entryPercentageLabel}%)",
                'amount' => $entryAmount,
                'due_date' => now()->toDateString(),
                'billing_type' => 'PIX',
                'payment_provider' => 'asaas',
                'status' => 'aguardando_aceite',
            ]);

            foreach ($amounts as $index => $amount) {
                $installmentNumber = $index + 1;
                $days = $installmentNumber * 30;

                $installment = ContractInstallment::query()->create([
                    'contract_id' => $contract->id,
                    'order_id' => $order->id,
                    'installment_number' => $installmentNumber,
                    'label' => sprintf('Parcela %d/3', $installmentNumber),
                    'amount' => $amount,
                    'due_date' => now()->addDays($days)->toDateString(),
                    'billing_type' => 'BOLETO',
                    'payment_provider' => 'asaas',
                    'status' => 'aguardando_aceite',
                ]);
            }

            return $contract->fresh(['installments', 'user', 'analyst', 'order']);
        });
    }

    public function accept(Contract $contract, string $acceptedName, ?string $acceptedIp = null, ?string $acceptedUserAgent = null): Contract
    {
        return DB::transaction(function () use ($contract, $acceptedName, $acceptedIp, $acceptedUserAgent): Contract {
            $contract->loadMissing(['installments', 'user', 'analyst', 'order']);

            if ($contract->status === 'cancelado') {
                throw new RuntimeException('Não é possível aceitar um contrato cancelado.');
            }

            if ($contract->accepted_at === null && $contract->acceptance_expires_at?->isPast()) {
                throw new RuntimeException('Este link de aceite expirou. Solicite um novo envio do contrato.');
            }

            if ($contract->accepted_at === null) {
                $contract->update([
                    'accepted_at' => now(),
                    'accepted_name' => trim($acceptedName),
                    'accepted_ip' => $acceptedIp ?: $contract->accepted_ip,
                    'accepted_user_agent' => $acceptedUserAgent ?: $contract->accepted_user_agent,
                    'accepted_hash' => $this->buildAcceptanceHash($contract, trim($acceptedName)),
                ]);
            }

            $this->syncContractStatus($contract->fresh(['installments', 'user', 'analyst', 'order']));

            return $contract->fresh(['installments', 'user', 'analyst', 'order']);
        });
    }

    public function releaseChargesAfterAcceptance(Contract $contract): array
    {
        $contract->loadMissing(['installments', 'user']);

        $pendingInstallments = $contract->installments
            ->filter(fn (ContractInstallment $installment) => $installment->status === 'aguardando_aceite' && $installment->paid_at === null)
            ->values();

        if ($pendingInstallments->isEmpty()) {
            return ['released' => 0, 'failed' => 0];
        }

        ContractInstallment::query()
            ->whereIn('id', $pendingInstallments->pluck('id'))
            ->update(['status' => 'aguardando_pagamento']);

        if (! $this->hasAsaasConfigured()) {
            return ['released' => (int) $pendingInstallments->count(), 'failed' => 0];
        }

        $released = 0;
        $failed = 0;

        foreach ($pendingInstallments as $installment) {
            try {
                $this->issueInstallmentCharge($contract, $installment->fresh());
                $released++;
            } catch (RuntimeException $exception) {
                $failed++;

                Log::warning('Aceite registrado, mas a cobrança da parcela não foi emitida.', [
                    'contract_id' => $contract->id,
                    'installment_id' => $installment->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return ['released' => $released, 'failed' => $failed];
    }

    public function markInstallmentAsPaid(ContractInstallment $installment, ?string $paymentId = null, ?string $invoiceUrl = null): void
    {
        if ($installment->status === 'pago') {
            return;
        }

        $installment->update([
            'asaas_payment_id' => $paymentId ?: $installment->asaas_payment_id,
            'payment_link_url' => $invoiceUrl ?: $installment->payment_link_url,
            'status' => 'pago',
            'paid_at' => now(),
        ]);

        $contract = $installment->contract()->with('installments')->first();
        if (! $contract) {
            return;
        }

        $this->syncContractStatus($contract);
    }

    public function markInstallmentAsFailed(ContractInstallment $installment, string $status): void
    {
        $map = [
            'PAYMENT_OVERDUE' => 'vencido',
            'PAYMENT_REFUNDED' => 'reembolsado',
            'PAYMENT_DELETED' => 'cancelado',
        ];

        $installment->update(['status' => $map[$status] ?? 'falhou']);
    }

    public function issueInstallmentCharge(Contract $contract, ContractInstallment $installment): void
    {
        if (! $this->hasAsaasConfigured()) {
            return;
        }

        $customerId = $contract->asaas_customer_id ?: $this->ensureAsaasCustomer($contract->user);
        if (! $contract->asaas_customer_id) {
            $contract->update(['asaas_customer_id' => $customerId]);
        }

        $payload = [
            'customer' => $customerId,
            'billingType' => $installment->billing_type,
            'value' => (float) $installment->amount,
            'dueDate' => (string) $installment->due_date->toDateString(),
            'description' => 'Contrato #'.$contract->id.' - '.$installment->label,
            'externalReference' => 'contract_installment:'.$installment->id,
        ];

        $response = $this->asaasClient()->post('/payments', $payload);

        if (! $response->successful()) {
            Log::error('Falha ao criar cobrança de parcela no Asaas.', [
                'contract_id' => $contract->id,
                'installment_id' => $installment->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível gerar cobrança da parcela no Asaas.');
        }

        $payment = $response->json();
        $installment->update([
            'asaas_payment_id' => (string) ($payment['id'] ?? ''),
            'payment_link_url' => (string) ($payment['invoiceUrl'] ?? ''),
            'status' => 'aguardando_pagamento',
        ]);
    }

    private function resolveAnalystId(Order $order): ?int
    {
        $sellerId = (int) ($order->lead?->referred_by_user_id ?: $order->user?->referred_by_user_id ?: 0);
        if ($sellerId > 0) {
            $seller = User::query()->find($sellerId);
            if ($seller && in_array($seller->role, ['analista', 'vendedor'], true)) {
                return $seller->id;
            }
        }

        $defaultAnalyst = User::query()
            ->whereIn('role', ['analista', 'vendedor'])
            ->where('email', (string) config('services.sales.default_analyst_email'))
            ->first()
            ?? User::query()->whereIn('role', ['analista', 'vendedor'])->orderBy('id')->first();

        return $defaultAnalyst?->id;
    }

    private function syncContractStatus(Contract $contract): void
    {
        $contract->loadMissing('installments');

        if ($contract->status === 'cancelado') {
            return;
        }

        $entryPaid = $contract->installments->firstWhere('installment_number', 0)?->status === 'pago';
        $remainingPaid = $contract->installments
            ->where('installment_number', '>', 0)
            ->every(fn (ContractInstallment $item) => $item->status === 'pago');

        $status = 'aguardando_aceite';

        if ($contract->accepted_at !== null) {
            $status = 'aguardando_entrada';
        }

        if ($contract->accepted_at !== null && $entryPaid) {
            $status = 'ativo';
        }

        if ($contract->accepted_at !== null && $entryPaid && $remainingPaid) {
            $status = 'concluido';
        }

        $updates = [
            'status' => $status,
            'entry_paid_at' => $entryPaid
                ? ($contract->entry_paid_at ?: now())
                : null,
            'activated_at' => $status === 'ativo' || $status === 'concluido'
                ? ($contract->activated_at ?: now())
                : null,
            'completed_at' => $status === 'concluido'
                ? ($contract->completed_at ?: now())
                : null,
        ];

        $contract->update($updates);
    }

    private function buildAcceptanceHash(Contract $contract, string $acceptedName): string
    {
        $payload = [
            'contract_id' => $contract->id,
            'order_id' => $contract->order_id,
            'document_path' => $contract->document_path,
            'fee_amount' => (string) $contract->fee_amount,
            'entry_amount' => (string) $contract->entry_amount,
            'entry_percentage' => (string) $contract->entry_percentage,
            'installments_count' => (int) $contract->installments_count,
            'accepted_name' => $acceptedName,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function ensureAsaasCustomer(User $user): string
    {
        $document = preg_replace('/\D+/', '', (string) ($user->cpf_cnpj ?? ''));
        $email = $this->resolveAsaasCustomerEmail($user->email);

        if ($document !== '') {
            $existing = $this->asaasClient()->get('/customers', ['cpfCnpj' => $document]);
            if ($existing->successful()) {
                $data = $existing->json('data');
                if (is_array($data) && isset($data[0]['id'])) {
                    return (string) $data[0]['id'];
                }
            }
        }

        if ($email !== '') {
            $existing = $this->asaasClient()->get('/customers', ['email' => $email]);
            if ($existing->successful()) {
                $data = $existing->json('data');
                if (is_array($data) && isset($data[0]['id'])) {
                    return (string) $data[0]['id'];
                }
            }
        }

        $payload = array_filter([
            'name' => (string) ($user->name ?: 'Cliente CPF Clean'),
            'email' => $email ?: null,
            'cpfCnpj' => $document ?: null,
            'mobilePhone' => preg_replace('/\D+/', '', (string) ($user->whatsapp ?? '')),
        ], static fn ($value) => $value !== null && $value !== '');

        $response = $this->asaasClient()->post('/customers', $payload);
        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível cadastrar cliente no Asaas para o contrato.');
        }

        return (string) $response->json('id');
    }

    private function resolveAsaasCustomerEmail(?string $email): string
    {
        $normalized = mb_strtolower(trim((string) $email));

        if (
            $normalized === ''
            || $normalized === mb_strtolower(trim((string) config('services.cpfclean.default_customer_email', 'contato@cpfclean.com.br')))
            || str_starts_with($normalized, 'cliente+')
            || str_ends_with($normalized, '@regulariza.local')
        ) {
            return mb_strtolower(trim((string) config('services.cpfclean.default_customer_email', 'contato@cpfclean.com.br')));
        }

        return $normalized;
    }

    private function hasAsaasConfigured(): bool
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

    private function asaasClient(): PendingRequest
    {
        return Http::baseUrl((string) config('services.asaas.base_url'))
            ->withHeaders([
                'access_token' => (string) config('services.asaas.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->acceptJson();
    }
}
