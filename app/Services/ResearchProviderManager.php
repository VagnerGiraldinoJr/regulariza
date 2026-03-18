<?php

namespace App\Services;

use App\Services\ResearchProviders\ResearchProviderInterface;
use RuntimeException;

class ResearchProviderManager
{
    /**
     * @param  iterable<ResearchProviderInterface>  $providers
     */
    public function __construct(private readonly iterable $providers) {}

    public function consult(array $source, string $documentNumber): array
    {
        $driver = (string) ($source['provider_driver'] ?? '');

        foreach ($this->providers as $provider) {
            if ($provider->supports($driver)) {
                return $provider->consult($source, $documentNumber);
            }
        }

        throw new RuntimeException("Driver de pesquisa não suportado: {$driver}");
    }
}
