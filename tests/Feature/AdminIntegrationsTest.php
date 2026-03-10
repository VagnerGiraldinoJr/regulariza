<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_asaas_without_sending_other_integrations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.management.integrations.update'), [
            'integration_group' => 'asaas',
            'asaas_base_url' => 'https://sandbox.asaas.com/api/v3',
            'asaas_api_key' => 'asaas_api_key_test',
            'asaas_webhook_token' => 'webhook_token_test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('https://sandbox.asaas.com/api/v3', SystemSetting::getValue('asaas.base_url'));
        $this->assertSame('asaas_api_key_test', SystemSetting::getValue('asaas.api_key'));
        $this->assertSame('webhook_token_test', SystemSetting::getValue('asaas.webhook_token'));
    }

    public function test_admin_can_update_zapi_without_api_brasil_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.management.integrations.update'), [
            'integration_group' => 'zapi',
            'zapi_instance' => '123456',
            'zapi_token' => 'token_zapi',
            'zapi_client_token' => 'client_token_zapi',
            'cpfclean_whatsapp_number' => '+55 (11) 99999-9999',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('123456', SystemSetting::getValue('zapi.instance'));
        $this->assertSame('token_zapi', SystemSetting::getValue('zapi.token'));
        $this->assertSame('client_token_zapi', SystemSetting::getValue('zapi.client_token'));
        $this->assertSame('5511999999999', SystemSetting::getValue('cpfclean.whatsapp_number'));
    }
}
