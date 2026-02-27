<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class StripeCheckoutService
{
    /**
     * Cria sessão de checkout no Stripe e retorna a URL para redirecionamento.
     */
    public function createCheckoutSession(Lead $lead, Service $service): string
    {
        $user = $this->resolveUserFromLead($lead);

        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'pendente',
            'valor' => $service->preco,
            'pagamento_status' => 'aguardando',
        ]);

        if (! $this->hasStripeConfigured() && app()->environment('local')) {
            $lead->update(['etapa' => 'pagamento']);

            // Fallback local para validar o fluxo sem credenciais reais do Stripe.
            return route('regularizacao.sucesso').'?order_id='.$order->id.'&mock_checkout=1';
        }

        $response = Http::asForm()
            ->withToken((string) config('services.stripe.secret'))
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('regularizacao.sucesso').'?session_id={CHECKOUT_SESSION_ID}&order_id='.$order->id,
                'cancel_url' => route('regularizacao.cancelado').'?order_id='.$order->id,
                'customer_email' => $user->email,
                'metadata[lead_id]' => (string) $lead->id,
                'metadata[order_id]' => (string) $order->id,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => 'brl',
                'line_items[0][price_data][unit_amount]' => (int) round(((float) $service->preco) * 100),
                'line_items[0][price_data][product_data][name]' => $service->nome,
                'line_items[0][price_data][product_data][description]' => $service->descricao ?? 'Serviço de regularização',
            ]);

        if (! $response->successful()) {
            Log::error('Falha ao criar sessão Stripe Checkout.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Não foi possível criar a sessão do Stripe. Verifique STRIPE_KEY/STRIPE_SECRET no .env.');
        }

        $payload = $response->json();

        $order->update([
            'stripe_checkout_session_id' => $payload['id'] ?? null,
            'stripe_payment_intent_id' => $payload['payment_intent'] ?? null,
        ]);

        $lead->update(['etapa' => 'pagamento']);

        $checkoutUrl = (string) ($payload['url'] ?? '');

        if ($checkoutUrl === '') {
            throw new RuntimeException('Stripe retornou resposta sem URL de checkout.');
        }

        return $checkoutUrl;
    }

    /**
     * Resolve/cria usuário do portal com base no lead antes do pagamento.
     */
    protected function resolveUserFromLead(Lead $lead): User
    {
        $email = $lead->email ?: 'cliente+'.Str::lower(Str::random(12)).'@regulariza.local';

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $lead->nome ?: 'Cliente Regulariza',
                'cpf_cnpj' => $lead->cpf_cnpj,
                'whatsapp' => $lead->whatsapp,
                'role' => 'cliente',
                'password' => Str::password(12),
            ]
        );
    }

    protected function hasStripeConfigured(): bool
    {
        $key = (string) config('services.stripe.key');
        $secret = (string) config('services.stripe.secret');

        if ($key === '' || $secret === '') {
            return false;
        }

        if (str_starts_with($key, 'COLE_') || str_starts_with($secret, 'COLE_')) {
            return false;
        }

        return true;
    }
}
