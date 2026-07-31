<?php

namespace App\Services;

/**
 * Danish/English automotive slang → canonical tokens for AI parse context and LIKE search.
 */
class VehicleSearchSynonymService
{
    /**
     * Longer phrases first so multi-word slang wins over single tokens.
     *
     * @var array<string, string>
     */
    private const MAP = [
        'plug-in hybrid' => 'Plug-in Hybrid',
        'plugin hybrid' => 'Plug-in Hybrid',
        'plug in hybrid' => 'Plug-in Hybrid',
        'ladbar hybrid' => 'Plug-in Hybrid',
        'automatgear' => 'Automatic',
        'automatisk gear' => 'Automatic',
        'manuelt gear' => 'Manual',
        'manuel gear' => 'Manual',
        'stationcar' => 'Estate',
        'station car' => 'Estate',
        'personbil' => 'Passenger car',
        'familiebil' => 'family car',
        'elbiler' => 'Electric',
        'elbil' => 'Electric',
        'elektrisk' => 'Electric',
        'benzin' => 'Petrol',
        'diesel' => 'Diesel',
        'hybrid' => 'Hybrid',
        'automatic' => 'Automatic',
        'automatisk' => 'Automatic',
        'manual' => 'Manual',
        'manuel' => 'Manual',
        'estate' => 'Estate',
        'touring' => 'Estate',
        'wagon' => 'Estate',
        'suv' => 'SUV',
        'crossover' => 'SUV',
        'sedan' => 'Sedan',
        'hatchback' => 'Hatchback',
        'cabrio' => 'Convertible',
        'cabriolet' => 'Convertible',
        'københavn' => 'København',
        'copenhagen' => 'København',
        'århus' => 'Aarhus',
        'aarhus' => 'Aarhus',
        'billig' => 'cheap',
        'billige' => 'cheap',
        'nyere' => 'newer',
        'pendling' => 'commuting',
        'ejerafgift' => 'ownership tax',
        'grøn afgift' => 'ownership tax',
    ];

    /**
     * Expand slang in a free-text query for AI / residual keyword search.
     */
    public function expand(string $query): string
    {
        $normalized = trim($query);
        if ($normalized === '') {
            return '';
        }

        $result = $normalized;
        foreach (self::MAP as $slang => $canonical) {
            $pattern = '/\b'.preg_quote($slang, '/').'\b/iu';
            $result = preg_replace($pattern, $canonical, $result) ?? $result;
        }

        return trim(preg_replace('/\s+/u', ' ', $result) ?? $result);
    }

    /**
     * @return array<string, string>
     */
    public function map(): array
    {
        return self::MAP;
    }

    /**
     * Curated example queries shown under search boxes (locale-aware).
     *
     * @return list<string>
     */
    public function exampleQueries(string $locale = 'da'): array
    {
        if ($locale === 'en') {
            return [
                'Electric car under 200,000',
                'VW Golf diesel',
                'Family car Aarhus',
                'Automatic hybrid 2020 or newer',
            ];
        }

        return [
            'Elbil under 200.000',
            'VW Golf diesel',
            'Familiebil Aarhus',
            'Automatgear hybrid 2020 eller nyere',
        ];
    }
}
