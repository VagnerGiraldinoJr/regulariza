<?php

namespace Tests\Feature;

use App\Services\ApiBrasilService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiBrasilTipoFallbackTest extends TestCase
{
    public function test_it_retries_with_tipo_fallback_when_first_request_returns_400(): void
    {
        config()->set('services.apibrasil.base_url', 'https://apibrasil.test');
        config()->set('services.apibrasil.token', 'token-apibrasil');
        config()->set('services.apibrasil.homolog', false);

        Http::fake([
            'https://apibrasil.test/api/v2/consulta/cnpj/credits' => Http::sequence()
                ->push(['error' => true, 'message' => 'tipo inválido'], 400)
                ->push(['error' => false, 'data' => ['resultado' => ['cnpj' => '44959669000180']]], 200),
        ]);

        $service = app(ApiBrasilService::class);
        $result = $service->consultarCatalogo('compliance_complete_pj', '44.959.669/0001-80');

        $this->assertSame('success', $result['status']);
        $this->assertSame(200, $result['http_status']);

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://apibrasil.test/api/v2/consulta/cnpj/credits'
                && ($body['tipo'] ?? null) === 'compliance-complete-pj';
        });
        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://apibrasil.test/api/v2/consulta/cnpj/credits'
                && ($body['tipo'] ?? null) === 'compliance_complete_pj';
        });
    }

    public function test_it_forces_homolog_false_in_production_even_when_setting_is_true(): void
    {
        config()->set('app.env', 'production');
        config()->set('services.apibrasil.base_url', 'https://apibrasil.test');
        config()->set('services.apibrasil.token', 'token-apibrasil');
        config()->set('services.apibrasil.homolog', true);

        Http::fake([
            'https://apibrasil.test/api/v2/consulta/cnpj/credits' => Http::response(['error' => false], 200),
        ]);

        $service = app(ApiBrasilService::class);
        $result = $service->consultarCatalogo('scr_bacen_score_pj', '10.650.534/0001-17');

        $this->assertSame('success', $result['status']);
        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'https://apibrasil.test/api/v2/consulta/cnpj/credits'
                && ($body['homolog'] ?? null) === false;
        });
    }
}
