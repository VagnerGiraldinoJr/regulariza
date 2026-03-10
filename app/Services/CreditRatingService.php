<?php

namespace App\Services;

class CreditRatingService
{
    public function resolveFromScore(int|float|string|null $score): array
    {
        $numeric = $this->normalizeScore($score);

        if ($numeric === null) {
            return [
                'score' => null,
                'classification' => 'NÃO CLASSIFICADO',
                'moodys' => '-',
                'sp' => '-',
                'fitch' => '-',
            ];
        }

        foreach ((array) config('credit_rating.matrix', []) as $row) {
            if ($numeric < (int) ($row['min'] ?? 0) || $numeric > (int) ($row['max'] ?? 0)) {
                continue;
            }

            return [
                'score' => $numeric,
                'classification' => (string) ($row['classification'] ?? 'NÃO CLASSIFICADO'),
                'moodys' => (string) ($row['moodys'] ?? '-'),
                'sp' => (string) ($row['sp'] ?? '-'),
                'fitch' => (string) ($row['fitch'] ?? '-'),
            ];
        }

        return [
            'score' => $numeric,
            'classification' => 'NÃO CLASSIFICADO',
            'moodys' => '-',
            'sp' => '-',
            'fitch' => '-',
        ];
    }

    private function normalizeScore(int|float|string|null $score): ?int
    {
        if (is_int($score)) {
            return $score;
        }

        if (is_float($score)) {
            return (int) round($score);
        }

        if (! is_string($score)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $score);

        return $digits === '' ? null : (int) $digits;
    }
}
