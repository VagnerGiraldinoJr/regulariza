<?php

namespace Tests\Feature;

use App\Models\Service;
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

    public function test_admin_can_update_public_service_catalog_prices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.management.integrations.update'), [
            'integration_group' => 'regularizacao_service',
            'service_prices' => [
                'cpf-clean-brasil' => '25.50',
                'cpron-cartorio' => '35.90',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $service = Service::query()->where('slug', 'cpf-clean-brasil')->first();
        $cpronService = Service::query()->where('slug', 'cpron-cartorio')->first();

        $this->assertNotNull($service);
        $this->assertSame('pesquisa CPF CLEAN BRASIL', $service->nome);
        $this->assertSame('25.50', number_format((float) $service->preco, 2, '.', ''));
        $this->assertTrue((bool) $service->ativo);

        $this->assertNotNull($cpronService);
        $this->assertSame('Pesquisa CPRON - Cartório', $cpronService->nome);
        $this->assertSame('35.90', number_format((float) $cpronService->preco, 2, '.', ''));
        $this->assertTrue((bool) $cpronService->ativo);
    }
}
