<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegularizacaoWizardPaidOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_order_opens_wizard_on_success_step_even_on_regularizacao_route(): void
    {
        Route::get('/portal-test', static fn () => 'ok')->name('portal.welcome');

        $service = Service::query()->create([
            'nome' => 'Regularização PF',
            'slug' => 'regularizacao-pf-paid-order',
            'preco' => 299.90,
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'cliente',
            'cpf_cnpj' => '36745465825',
            'whatsapp' => '11996190016',
        ]);

        $lead = Lead::query()->create([
            'cpf_cnpj' => '36745465825',
            'tipo_documento' => 'cpf',
            'whatsapp' => '11996190016',
        ]);

        $order = Order::query()->create([
            'protocolo' => 'PED-PAID-001',
            'user_id' => $user->id,
            'service_id' => $service->id,
            'lead_id' => $lead->id,
            'status' => 'em_andamento',
            'valor' => 299.90,
            'pagamento_status' => 'pago',
            'pago_em' => now(),
        ]);

        $response = $this->get(route('regularizacao.index', ['order_id' => $order->id]));

        $response->assertOk();
        $response->assertSee('Pagamento confirmado');
        $response->assertSee('PED-PAID-001');
    }
}
