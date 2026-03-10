<?php

namespace Tests\Feature;

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
}
