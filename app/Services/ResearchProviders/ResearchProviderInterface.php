<?php

namespace App\Services\ResearchProviders;

interface ResearchProviderInterface
{
    public function supports(string $driver): bool;

    public function consult(array $source, string $documentNumber): array;
}
