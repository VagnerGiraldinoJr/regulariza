<?php

namespace Tests\Feature;

use App\Jobs\EnviarAcessoPortalWhatsApp;
use App\Mail\PortalAccessMail;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\LeadUserResolverService;
use App\Services\ZApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PortalAccessNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_access_job_sends_whatsapp_and_email_with_credentials(): void
    {
        config()->set('zapi.instance', 'instance-test');
        config()->set('zapi.token', 'token-test');
        config()->set('zapi.client_token', 'client-token-test');

        Http::fake([
            'https://api.z-api.io/*' => Http::response(['ok' => true], 200),
        ]);

        Mail::fake();

        $user = User::factory()->create([
            'role' => 'cliente',
            'name' => 'Cliente Portal',
            'email' => 'cliente.portal@example.com',
            'whatsapp' => '11999999999',
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF CLEAN BRASIL',
            'slug' => 'pesquisa-cpf-clean-brasil-portal-acesso',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PORTAL-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 200.00,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $job = new EnviarAcessoPortalWhatsApp($order);
        $job->handle(app(ZApiService::class), app(LeadUserResolverService::class));

        $user->refresh();

        $this->assertNotNull($user->portal_token);
        $this->assertNotNull($user->portal_token_expires_at);

        Http::assertSentCount(1);

        Mail::assertSent(PortalAccessMail::class, function (PortalAccessMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
