<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalystDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_whatsapp_share_link_for_referral_code(): void
    {
        $analyst = User::factory()->create([
            'role' => 'analista',
            'referral_code' => 'analista123',
        ]);

        $response = $this->actingAs($analyst)->get(route('analyst.dashboard'));

        $response->assertOk();
        $response->assertSee('Abrir link comercial');
        $response->assertSee('Compartilhar via WhatsApp');
        $response->assertSee('https://wa.me/?text=', false);
        $response->assertSee(rawurlencode(route('regularizacao.index', ['indicacao' => 'analista123'])), false);
    }
}
