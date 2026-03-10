<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Service;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutServicePaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pix_checkout_returns_qr_code_data(): void
    {
        config()->set('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        config()->set('services.asaas.api_key', 'asaas_test_token');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/customers')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/customers')) {
                return Http::response(['id' => 'cus_pix'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/payments')) {
                return Http::response([
                    'id' => 'pay_pix',
                    'billingType' => 'PIX',
                    'invoiceUrl' => 'https://asaas.local/f/pay_pix',
                    'value' => 200.00,
                    'dueDate' => now()->toDateString(),
                ], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($url, '/payments/pay_pix/pixQrCode')) {
                return Http::response([
                    'encodedImage' => 'base64encodedimage',
                    'payload' => '000201pixcopyandpaste',
                    'expirationDate' => now()->addDay()->toIso8601String(),
                ], 200);
            }

            return Http::response([], 404);
        });

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil',
            'slug' => 'pesquisa-cpf-clean-brasil',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente PIX',
            'email' => 'cliente.pix@example.com',
            'whatsapp' => '11999999999',
            'service_id' => $service->id,
            'etapa' => 'servico',
        ]);

        $checkout = app(CheckoutService::class)->createCheckoutSession($lead, $service, 'PIX');

        $this->assertSame('PIX', $checkout['billing_type']);
        $this->assertSame('https://asaas.local/f/pay_pix', $checkout['payment_url']);
        $this->assertSame('000201pixcopyandpaste', $checkout['pix']['payload']);
        $this->assertSame('base64encodedimage', $checkout['pix']['encoded_image']);
    }

    public function test_boleto_checkout_returns_bank_slip_url(): void
    {
        config()->set('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        config()->set('services.asaas.api_key', 'asaas_test_token');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/customers')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/customers')) {
                return Http::response(['id' => 'cus_boleto'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/payments')) {
                return Http::response([
                    'id' => 'pay_boleto',
                    'billingType' => 'BOLETO',
                    'invoiceUrl' => 'https://asaas.local/f/pay_boleto',
                    'bankSlipUrl' => 'https://asaas.local/b/pay_boleto',
                    'value' => 200.00,
                    'dueDate' => now()->toDateString(),
                ], 200);
            }

            return Http::response([], 404);
        });

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil',
            'slug' => 'pesquisa-cpf-clean-brasil-boleto',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Boleto',
            'email' => 'cliente.boleto@example.com',
            'whatsapp' => '11999999999',
            'service_id' => $service->id,
            'etapa' => 'servico',
        ]);

        $checkout = app(CheckoutService::class)->createCheckoutSession($lead, $service, 'BOLETO');

        $this->assertSame('BOLETO', $checkout['billing_type']);
        $this->assertSame('https://asaas.local/b/pay_boleto', $checkout['payment_url']);
        $this->assertSame('https://asaas.local/b/pay_boleto', $checkout['bank_slip_url']);
    }

    public function test_credit_card_checkout_returns_invoice_url(): void
    {
        config()->set('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        config()->set('services.asaas.api_key', 'asaas_test_token');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/customers')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/customers')) {
                return Http::response(['id' => 'cus_card'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/payments')) {
                return Http::response([
                    'id' => 'pay_card',
                    'billingType' => 'CREDIT_CARD',
                    'invoiceUrl' => 'https://asaas.local/f/pay_card',
                    'value' => 200.00,
                    'dueDate' => now()->toDateString(),
                ], 200);
            }

            return Http::response([], 404);
        });

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil',
            'slug' => 'pesquisa-cpf-clean-brasil-card',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Cartao',
            'email' => 'cliente.card@example.com',
            'whatsapp' => '11999999999',
            'service_id' => $service->id,
            'etapa' => 'servico',
        ]);

        $checkout = app(CheckoutService::class)->createCheckoutSession($lead, $service, 'CREDIT_CARD');

        $this->assertSame('CREDIT_CARD', $checkout['billing_type']);
        $this->assertSame('https://asaas.local/f/pay_card', $checkout['payment_url']);
        $this->assertNull($checkout['pix']);
    }

    public function test_checkout_reuses_existing_user_by_cpf_when_lead_has_no_email(): void
    {
        config()->set('services.asaas.base_url', 'https://sandbox.asaas.com/api/v3');
        config()->set('services.asaas.api_key', 'asaas_test_token');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'GET' && str_contains($url, '/customers')) {
                return Http::response(['data' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/customers')) {
                return Http::response(['id' => 'cus_existing'], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($url, '/payments')) {
                return Http::response([
                    'id' => 'pay_existing',
                    'billingType' => 'PIX',
                    'invoiceUrl' => 'https://asaas.local/f/pay_existing',
                    'value' => 200.00,
                    'dueDate' => now()->toDateString(),
                ], 200);
            }

            if ($request->method() === 'GET' && str_ends_with($url, '/payments/pay_existing/pixQrCode')) {
                return Http::response([
                    'encodedImage' => 'existingimage',
                    'payload' => 'pixpayloadexisting',
                    'expirationDate' => now()->addDay()->toIso8601String(),
                ], 200);
            }

            return Http::response([], 404);
        });

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF Clean Brasil',
            'slug' => 'pesquisa-cpf-clean-brasil-existing-user',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $existingUser = User::factory()->create([
            'role' => 'cliente',
            'name' => 'Cliente Existente',
            'email' => 'cliente.existente@example.com',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11900000000',
        ]);
        $seller = User::factory()->create([
            'role' => 'vendedor',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'nome' => 'Cliente Checkout',
            'email' => null,
            'whatsapp' => '11996190016',
            'service_id' => $service->id,
            'etapa' => 'servico',
            'referred_by_user_id' => $seller->id,
        ]);

        $checkout = app(CheckoutService::class)->createCheckoutSession($lead, $service, 'PIX');

        $this->assertSame('PIX', $checkout['billing_type']);
        $this->assertSame(1, User::query()->where('cpf_cnpj', '36745465825')->count());
        $this->assertDatabaseHas('orders', [
            'id' => $checkout['order_id'],
            'user_id' => $existingUser->id,
            'lead_id' => $lead->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $existingUser->id,
            'email' => 'cliente.existente@example.com',
            'cpf_cnpj' => '36745465825',
        ]);
    }
}
