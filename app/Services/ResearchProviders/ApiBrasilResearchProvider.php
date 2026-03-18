<?php

namespace App\Services\ResearchProviders;

use App\Services\ApiBrasilService;
use RuntimeException;

class ApiBrasilResearchProvider implements ResearchProviderInterface
{
    public function __construct(private readonly ApiBrasilService $apiBrasilService) {}

    public function supports(string $driver): bool
    {
        return $driver === 'apibrasil';
    }

    public function consult(array $source, string $documentNumber): array
    {
        $consultationKey = (string) ($source['consultation_key'] ?? '');

        if ($consultationKey === '') {
            throw new RuntimeException('Fonte de pesquisa sem consultation_key.');
        }

        $result = $this->apiBrasilService->consultarCatalogo($consultationKey, $documentNumber);
        $result['provider'] = (string) ($source['provider'] ?? 'apibrasil');
        $result['provider_label'] = (string) ($source['provider_label'] ?? 'API Brasil');
        $result['provider_driver'] = 'apibrasil';

        return $result;
    }
}
