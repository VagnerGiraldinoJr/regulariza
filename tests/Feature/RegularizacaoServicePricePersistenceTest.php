<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegularizacaoServicePricePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_regularizacao_page_preserves_existing_service_price(): void
    {
        $service = Service::query()->create([
            'nome' => 'pesquisa CPF CLEAN BRASIL',
            'slug' => 'cpf-clean-brasil',
            'descricao' => 'Diagnóstico consultivo do CPF ou CNPJ com análise especializada e plano de direcionamento.',
            'icone' => 'cpf clean',
            'preco' => 25.50,
            'ativo' => true,
        ]);

        $response = $this->get(route('regularizacao.index'));

        $response->assertOk();

        $service->refresh();

        $this->assertSame('25.50', number_format((float) $service->preco, 2, '.', ''));
    }

    public function test_regularizacao_page_exposes_cpron_service_and_selection_guidance(): void
    {
        $component = Livewire::test('regularizacao-wizard');

        $services = collect($component->get('services'));

        $this->assertTrue($services->contains(fn (array $service): bool => $service['slug'] === 'cpron-cartorio'));

        $component
            ->set('etapa', 2)
            ->assertSee('Pesquisa CPRON - Cartório')
            ->assertSee('Clique em um dos serviços para continuar.')
            ->assertSee('Clique para selecionar');
    }
}
