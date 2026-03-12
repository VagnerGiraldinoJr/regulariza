<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_portal_invite_logs_client_in_and_consumes_token(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'portal_token' => 'portal-invite-token-123',
            'portal_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->get(route('portal.invite', $user->portal_token));

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNull($user->portal_token);
        $this->assertNull($user->portal_token_expires_at);
    }

    public function test_expired_portal_invite_returns_gone(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'portal_token' => 'portal-invite-token-expired',
            'portal_token_expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('portal.invite', $user->portal_token));

        $response->assertStatus(410);
        $this->assertGuest();
    }

    public function test_client_with_provisional_email_is_redirected_to_profile_before_using_portal(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente+abc123@cpfclean.com.br',
        ]);

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-portal-provisional-email',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PORTAL-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'fee_amount' => 299.90,
            'entry_amount' => 149.95,
            'status' => 'ativo',
            'activated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('portal.dashboard'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('profile_attention');
    }

    public function test_client_can_access_portal_after_contract_creation_before_entry_payment(): void
    {
        $user = User::factory()->create([
            'role' => 'cliente',
            'email' => 'cliente.portal.liberado@example.com',
            'portal_token' => 'portal-contrato-imediato-123',
            'portal_token_expires_at' => now()->addDay(),
        ]);

        $service = Service::query()->create([
            'nome' => 'Pesquisa CPF CLEAN BRASIL',
            'slug' => 'pesquisa-cpf-clean-brasil-portal-imediato',
            'preco' => 200.00,
            'ativo' => true,
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PORTAL-IMEDIATO-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'em_andamento',
            'valor' => 200.00,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        Contract::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'fee_amount' => 299.90,
            'entry_amount' => 149.95,
            'status' => 'aguardando_aceite',
            'portal_access_sent_at' => now(),
            'acceptance_token' => 'token-portal-imediato-aceite',
        ]);

        $response = $this->get(route('portal.invite', $user->portal_token));

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($user);

        $dashboardResponse = $this->actingAs($user)->get(route('portal.dashboard'));

        $dashboardResponse->assertOk();
    }
}
