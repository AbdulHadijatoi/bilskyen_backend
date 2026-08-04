<?php

namespace App\Services;

/**
 * Danish/English automotive slang → DMR catalog tokens for AI parse context and LIKE search.
 *
 * Canonical targets must match live lookup names (dmr_drive_energies, gear_types, dmr_body_types).
 */
class VehicleSearchSynonymService
{
    /**
     * Longer phrases first so multi-word slang wins over single tokens.
     * Values are DMR / gear catalog names where a structured filter exists.
     *
     * @var array<string, string>
     */
    private const MAP = [
        // Fuel → dmr_drive_energies.name
        'plug-in hybrid' => 'Benzin',
        'plugin hybrid' => 'Benzin',
        'plug in hybrid' => 'Benzin',
        'ladbar hybrid' => 'Benzin',
        'plugin-hybrid' => 'Benzin',
        'elbiler' => 'El',
        'elbil' => 'El',
        'elektrisk' => 'El',
        'electric' => 'El',
        'ev' => 'El',
        'benzin' => 'Benzin',
        'petrol' => 'Benzin',
        'gasoline' => 'Benzin',
        'diesel' => 'Diesel',

        // Gear → gear_types.name
        'automatgear' => 'Automatic',
        'automatisk gear' => 'Automatic',
        'automatisk' => 'Automatic',
        'automatic' => 'Automatic',
        'manuelt gear' => 'Manual',
        'manuel gear' => 'Manual',
        'manuel' => 'Manual',
        'manual' => 'Manual',
        'manuelt' => 'Manual',

        // Body → dmr_body_types.name
        'station car' => 'Stationcar',
        'stationcar' => 'Stationcar',
        'estate' => 'Stationcar',
        'touring' => 'Stationcar',
        'wagon' => 'Stationcar',
        'cabrio' => 'Cabriolet',
        'convertible' => 'Cabriolet',
        'cabriolet' => 'Cabriolet',
        'sedan' => 'Sedan',
        'hatchback' => 'Hatchback',
        'coupe' => 'Coupe',
        'mpv' => 'MPV',

        // Cities
        'københavn' => 'København',
        'copenhagen' => 'København',
        'århus' => 'Aarhus',
        'aarhus' => 'Aarhus',

        // Intent / residual (no direct catalog row)
        'personbil' => 'personbil',
        'familiebil' => 'familiebil',
        'hybrid' => 'hybrid',
        'suv' => 'SUV',
        'crossover' => 'SUV',
        'billig' => 'billig',
        'billige' => 'billig',
        'nyere' => 'nyere',
        'pendling' => 'pendling',
        'ejerafgift' => 'ejerafgift',
        'grøn afgift' => 'ejerafgift',
        'cheap' => 'billig',
        'newer' => 'nyere',
        'commuting' => 'pendling',
        'ownership tax' => 'ejerafgift',
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
     * Canonical catalog (or residual) token for a single term, if known.
     */
    public function canonicalFor(string $term): ?string
    {
        $needle = mb_strtolower(trim($term));
        if ($needle === '') {
            return null;
        }

        foreach (self::MAP as $from => $to) {
            if ($needle === mb_strtolower($from) || $needle === mb_strtolower($to)) {
                return $to;
            }
        }

        return null;
    }

    /**
     * All Danish/English equivalents for a term (for lookup resolution).
     *
     * @return list<string> lowercased unique terms
     */
    public function equivalentTerms(string $term): array
    {
        $needle = mb_strtolower(trim($term));
        if ($needle === '') {
            return [];
        }

        $canonical = $this->canonicalFor($term);
        $groupKey = $canonical !== null ? mb_strtolower($canonical) : $needle;

        $terms = [$needle];
        if ($canonical !== null) {
            $terms[] = mb_strtolower($canonical);
        }

        foreach (self::MAP as $from => $to) {
            $toLower = mb_strtolower($to);
            $fromLower = mb_strtolower($from);
            if ($toLower === $groupKey || $fromLower === $groupKey || $fromLower === $needle || $toLower === $needle) {
                $terms[] = $fromLower;
                $terms[] = $toLower;
            }
        }

        return array_values(array_unique($terms));
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
     * Delegates to SuggestionService (inventory-backed cache + seed fallback).
     *
     * @return list<string>
     */
    public function exampleQueries(string $locale = 'da'): array
    {
        return app(SuggestionService::class)->exampleQueries($locale);
    }
}
