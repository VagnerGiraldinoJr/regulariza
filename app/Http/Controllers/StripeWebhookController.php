<?php

namespace App\Http\Controllers;

use App\Http\Requests\StripeWebhookRequest;
use App\Jobs\CriarUsuarioPortal;
use App\Jobs\EnviarBoasVindasWhatsApp;
use App\Jobs\NotificarEquipeInterna;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Recebe eventos do Stripe e atualiza pedidos conforme status do pagamento.
     */
    public function __invoke(StripeWebhookRequest $request): JsonResponse
    {
        $payload = $request->getContent();

        if (! $this->isValidSignature($payload, (string) $request->header('Stripe-Signature'))) {
            return response()->json(['message' => 'invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $eventType = $event['type'] ?? null;

        match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event['data']['object'] ?? []),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event['data']['object'] ?? []),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    /**
     * Valida assinatura do Stripe usando o segredo do webhook.
     */
    protected function isValidSignature(string $payload, string $header): bool
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '' || $header === '') {
            return app()->environment('local');
        }

        $parts = collect(explode(',', $header))
            ->mapWithKeys(function (string $part): array {
                $segments = explode('=', $part, 2);

                return count($segments) === 2 ? [$segments[0] => $segments[1]] : [];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! $timestamp || ! $signature) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Processa evento de checkout concluído e dispara jobs internos.
     */
    protected function handleCheckoutCompleted(array $session): void
    {
        $orderId = $session['metadata']['order_id'] ?? null;
        $checkoutId = $session['id'] ?? null;

        $order = Order::query()
            ->when($orderId, fn ($q) => $q->where('id', $orderId))
            ->when(! $orderId && $checkoutId, fn ($q) => $q->where('stripe_checkout_session_id', $checkoutId))
            ->first();

        if (! $order) {
            Log::warning('Pedido não encontrado para checkout.session.completed', ['session' => $session]);

            return;
        }

        $order->update([
            'stripe_checkout_session_id' => $checkoutId,
            'stripe_payment_intent_id' => $session['payment_intent'] ?? $order->stripe_payment_intent_id,
            'pagamento_status' => 'pago',
            'status' => 'em_andamento',
            'pago_em' => now(),
        ]);

        if ($order->lead) {
            $order->lead->update(['etapa' => 'concluido']);
        }

        CriarUsuarioPortal::dispatch($order);
        EnviarBoasVindasWhatsApp::dispatch($order);
        NotificarEquipeInterna::dispatch($order);
    }

    /**
     * Processa falha de pagamento do intent e marca pedido como falho.
     */
    protected function handlePaymentFailed(array $paymentIntent): void
    {
        $intentId = $paymentIntent['id'] ?? null;

        if (! $intentId) {
            return;
        }

        Order::query()
            ->where('stripe_payment_intent_id', $intentId)
            ->update([
                'pagamento_status' => 'falhou',
                'status' => 'cancelado',
            ]);
    }
}
