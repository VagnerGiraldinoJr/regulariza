<?php

namespace App\Http\Controllers;

use App\Jobs\CriarUsuarioPortal;
use App\Jobs\EnviarAcessoPortalWhatsApp;
use App\Models\ContractInstallment;
use App\Models\Order;
use App\Services\ContractService;
use App\Services\PaidOrderReconciliationService;
use App\Services\SellerCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    public function __construct(
        private readonly SellerCommissionService $sellerCommissionService,
        private readonly ContractService $contractService,
        private readonly PaidOrderReconciliationService $paidOrderReconciliationService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->isValidWebhookToken($request)) {
            return response()->json(['message' => 'invalid webhook token'], 400);
        }

        $event = strtoupper((string) $request->input('event', ''));
        $payment = $request->input('payment', []);

        if (! is_array($payment)) {
            return response()->json(['received' => true]);
        }

        if (in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)) {
            $this->handlePaymentReceived($payment);
        }

        if (in_array($event, ['PAYMENT_DELETED', 'PAYMENT_OVERDUE', 'PAYMENT_REFUNDED'], true)) {
            $this->handlePaymentFailed($payment, $event);
        }

        return response()->json(['received' => true]);
    }

    protected function isValidWebhookToken(Request $request): bool
    {
        $expected = (string) config('services.asaas.webhook_token');

        if ($expected === '') {
            return app()->environment('local');
        }

        $token = (string) ($request->header('asaas-access-token') ?: $request->header('Asaas-Access-Token'));

        return $token !== '' && hash_equals($expected, $token);
    }

    protected function handlePaymentReceived(array $payment): void
    {
        $installment = $this->resolveInstallmentFromPayment($payment);
        if ($installment) {
            $this->contractService->markInstallmentAsPaid(
                installment: $installment,
                paymentId: (string) ($payment['id'] ?? ''),
                invoiceUrl: (string) ($payment['invoiceUrl'] ?? '')
            );
            $installment = $installment->fresh(['contract', 'order']);
            $this->sellerCommissionService->registerContractInstallmentCommission($installment);

            if (
                $installment->installment_number === 0
                && $installment->status === 'pago'
                && $installment->contract
                && $installment->contract->accepted_at !== null
                && $installment->contract->portal_access_sent_at === null
            ) {
                CriarUsuarioPortal::dispatch($installment->order);
                EnviarAcessoPortalWhatsApp::dispatch($installment->order);
                $installment->contract->update(['portal_access_sent_at' => now()]);
            }

            return;
        }

        $order = $this->resolveOrderFromPayment($payment);

        if (! $order) {
            Log::warning('Pedido não encontrado para webhook Asaas', ['payment' => $payment]);

            return;
        }

        $this->paidOrderReconciliationService->reconcile(
            $order,
            $payment,
            dispatchNotifications: $order->pagamento_status !== 'pago'
        );
    }

    protected function handlePaymentFailed(array $payment, string $event): void
    {
        $installment = $this->resolveInstallmentFromPayment($payment);
        if ($installment) {
            $this->contractService->markInstallmentAsFailed($installment, $event);

            return;
        }

        $order = $this->resolveOrderFromPayment($payment);

        if (! $order) {
            return;
        }

        if ($order->pagamento_status === 'pago') {
            return;
        }

        $order->update([
            'pagamento_status' => 'falhou',
            'status' => 'cancelado',
        ]);
    }

    protected function resolveOrderFromPayment(array $payment): ?Order
    {
        $paymentId = (string) ($payment['id'] ?? '');
        $externalReference = (string) ($payment['externalReference'] ?? '');

        if ($paymentId !== '') {
            $order = Order::query()->where('asaas_payment_id', $paymentId)->first();
            if ($order) {
                return $order;
            }
        }

        if (preg_match('/order:(\d+)/', $externalReference, $matches) === 1) {
            return Order::query()->find((int) $matches[1]);
        }

        return null;
    }

    protected function resolveInstallmentFromPayment(array $payment): ?ContractInstallment
    {
        $paymentId = (string) ($payment['id'] ?? '');
        $externalReference = (string) ($payment['externalReference'] ?? '');

        if ($paymentId !== '') {
            $installment = ContractInstallment::query()->where('asaas_payment_id', $paymentId)->first();
            if ($installment) {
                return $installment;
            }
        }

        if (preg_match('/contract_installment:(\d+)/', $externalReference, $matches) === 1) {
            return ContractInstallment::query()->find((int) $matches[1]);
        }

        return null;
    }
}
