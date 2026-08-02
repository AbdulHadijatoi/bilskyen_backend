<?php

namespace App\Services;

use App\Exceptions\AiGenerationException;
use App\Models\DmrBodyType;
use App\Models\DmrBrand;
use App\Models\DmrDriveEnergy;
use App\Models\GearType;
use App\Models\MarketplaceCity;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AiSearchParseService
{
    private const CACHE_TTL_SECONDS = 3600;

    /** @var list<string> */
    private const ALLOWED_FILTER_KEYS = [
        'search',
        'brand_id',
        'model_id',
        'fuel_type_id',
        'body_type_id',
        'gear_type_id',
        'price_from',
        'price_to',
        'km_driven_from',
        'km_driven_to',
        'model_year_from',
        'model_year_to',
        'ownership_tax_from',
        'ownership_tax_to',
        'seats_min',
        'city_slug',
        'city',
    ];

    public function __construct(
        private AiService $aiService,
        private VehicleSearchSynonymService $synonymService,
        private LookupService $lookupService,
        private CityIndexService $cityIndexService,
        private SuggestionService $suggestionService,
    ) {}

    /**
     * Parse natural-language vehicle search into structured listing filters.
     *
     * @return array{
     *     filters: array<string, mixed>,
     *     labels: list<array{key: string, label: string}>,
     *     query: string,
     *     expanded_query: string,
     *     provider: ?string,
     *     cached: bool,
     *     fallback: bool
     * }
     */
    public function parse(string $query, string $locale = 'da'): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->emptyResult($query, $query, true);
        }

        $expanded = $this->synonymService->expand($query);
        $cacheKey = 'ai_search_parse:'.md5(mb_strtolower($locale.'|'.$expanded));

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['filters'])) {
            return array_merge($cached, ['cached' => true, 'fallback' => false]);
        }

        try {
            $result = $this->aiService->generateSearchParse(
                context: [
                    'user_query' => $query,
                    'expanded_query' => $expanded,
                    'slang_hints' => $this->synonymHintsForPrompt(),
                    'output_schema' => $this->outputSchemaDescription(),
                ],
                locale: $locale,
            );

            $parsed = $this->decodeAiJson((string) ($result['text'] ?? ''));
            $resolved = $this->resolveToFilters($parsed, $expanded, $locale);

            $payload = [
                'filters' => $resolved['filters'],
                'labels' => $resolved['labels'],
                'query' => $query,
                'expanded_query' => $expanded,
                'provider' => $result['provider'] ?? null,
                'cached' => false,
                'fallback' => false,
            ];

            if ($resolved['filters'] !== []) {
                Cache::put($cacheKey, [
                    'filters' => $payload['filters'],
                    'labels' => $payload['labels'],
                    'query' => $query,
                    'expanded_query' => $expanded,
                    'provider' => $payload['provider'],
                ], self::CACHE_TTL_SECONDS);
            }

            return $payload;
        } catch (AiGenerationException $e) {
            Log::info('ai_search_parse.fallback', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);

            return $this->keywordFallback($query, $expanded);
        } catch (\Throwable $e) {
            Log::warning('ai_search_parse.error', [
                'query' => $query,
                'message' => $e->getMessage(),
            ]);

            return $this->keywordFallback($query, $expanded);
        }
    }

    /**
     * Fast suggest for autocomplete (no AI).
     *
     * @return array{brands: list<array{id:int,name:string}>, models: list<array{id:int,name:string,brand_id:int}>, examples: list<string>}
     */
    public function suggest(string $term, string $locale = 'da', int $limit = 6): array
    {
        $term = trim($term);
        $sessionSeed = null;
        try {
            $sessionSeed = session()->getId();
        } catch (\Throwable) {
            $sessionSeed = null;
        }

        if ($term === '') {
            return [
                'brands' => [],
                'models' => [],
                'examples' => $this->suggestionService->matchingExamples('', $locale, min(4, $limit), $sessionSeed),
            ];
        }

        $expanded = $this->synonymService->expand($term);
        $brands = array_slice($this->lookupService->searchBrandsForListingFilters($expanded), 0, $limit);
        $models = array_slice($this->lookupService->searchModelsForListingFilters($expanded, []), 0, $limit);

        return [
            'brands' => $brands,
            'models' => $models,
            'examples' => $this->suggestionService->matchingExamples($term, $locale, min(3, $limit), $sessionSeed),
        ];
    }

    /**
     * Resolve advisor / parse JSON fields into public listing filters (shared with car advisor).
     *
     * @param  array<string, mixed>  $parsed
     * @return array{filters: array<string, mixed>, labels: list<array{key: string, label: string}>}
     */
    public function resolveAdvisorFilters(array $parsed, string $locale = 'da'): array
    {
        return $this->resolveToFilters($parsed, '', $locale);
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{filters: array<string, mixed>, labels: list<array{key: string, label: string}>}
     */
    private function resolveToFilters(array $parsed, string $expandedQuery, string $locale): array
    {
        $filters = [];
        $labels = [];

        $brandName = $this->stringOrNull($parsed['brand'] ?? null);
        $modelName = $this->stringOrNull($parsed['model'] ?? null);
        $fuelName = $this->stringOrNull($parsed['fuel'] ?? null);
        $bodyName = $this->stringOrNull($parsed['body'] ?? null);
        $gearName = $this->stringOrNull($parsed['gear'] ?? null);
        $cityName = $this->stringOrNull($parsed['city'] ?? null);

        if ($brandName) {
            $brand = $this->resolveBrand($brandName);
            if ($brand) {
                $filters['brand_id'] = [$brand['id']];
                $labels[] = ['key' => 'brand_id', 'label' => $brand['name']];
            }
        }

        if ($modelName) {
            $brandIds = isset($filters['brand_id']) ? array_map('intval', (array) $filters['brand_id']) : [];
            $model = $this->resolveModel($modelName, $brandIds);
            if ($model) {
                $filters['model_id'] = [$model['id']];
                if (! isset($filters['brand_id']) && ! empty($model['brand_id'])) {
                    $filters['brand_id'] = [(int) $model['brand_id']];
                    $brandRow = DmrBrand::query()->find($model['brand_id']);
                    if ($brandRow) {
                        $labels[] = ['key' => 'brand_id', 'label' => (string) $brandRow->name];
                    }
                }
                $labels[] = ['key' => 'model_id', 'label' => $model['name']];
            }
        }

        if ($fuelName) {
            $fuel = $this->resolveNamedLookup($fuelName, DmrDriveEnergy::class);
            if ($fuel) {
                $filters['fuel_type_id'] = [$fuel['id']];
                $labels[] = ['key' => 'fuel_type_id', 'label' => $fuel['name']];
            }
        }

        if ($bodyName) {
            $body = $this->resolveNamedLookup($bodyName, DmrBodyType::class);
            if ($body) {
                $filters['body_type_id'] = [$body['id']];
                $labels[] = ['key' => 'body_type_id', 'label' => $body['name']];
            }
        }

        if ($gearName) {
            $gear = $this->resolveNamedLookup($gearName, GearType::class);
            if ($gear) {
                $filters['gear_type_id'] = [$gear['id']];
                $labels[] = ['key' => 'gear_type_id', 'label' => $gear['name']];
            }
        }

        if ($cityName) {
            $city = $this->resolveCity($cityName);
            if ($city) {
                $filters['city_slug'] = $city->slug;
                $labels[] = ['key' => 'city_slug', 'label' => $city->name];
            }
        }

        foreach ([
            'price_from', 'price_to',
            'km_driven_from', 'km_driven_to',
            'model_year_from', 'model_year_to',
            'ownership_tax_from', 'ownership_tax_to',
            'seats_min',
        ] as $rangeKey) {
            $value = $parsed[$rangeKey] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if (! is_numeric($value)) {
                continue;
            }
            $intVal = (int) $value;
            $filters[$rangeKey] = $intVal;
            $labels[] = ['key' => $rangeKey, 'label' => $this->formatRangeLabel($rangeKey, $intVal, $locale)];
        }

        // Family car heuristic when AI/body didn't map but seats hinted
        if (($parsed['intent'] ?? null) === 'family' && ! isset($filters['seats_min'])) {
            $filters['seats_min'] = 5;
            $labels[] = ['key' => 'seats_min', 'label' => $locale === 'en' ? 'Min 5 seats' : 'Min. 5 sæder'];
        }

        $residual = $this->stringOrNull($parsed['search'] ?? null);
        if ($residual === null && $filters === []) {
            $residual = $expandedQuery;
        }
        if ($residual !== null && $residual !== '') {
            $filters['search'] = $residual;
            $labels[] = ['key' => 'search', 'label' => $residual];
        }

        $aiLabels = $parsed['labels'] ?? null;
        if (is_array($aiLabels) && $aiLabels !== []) {
            $fromAi = [];
            foreach ($aiLabels as $label) {
                if (is_string($label) && trim($label) !== '') {
                    $fromAi[] = ['key' => 'ai', 'label' => trim($label)];
                }
            }
            if ($fromAi !== []) {
                $labels = $fromAi;
            }
        }

        $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTER_KEYS));

        return ['filters' => $filters, 'labels' => array_values($labels)];
    }

    /**
     * Resolve city without creating MarketplaceCity rows.
     */
    private function resolveCity(string $cityName): ?MarketplaceCity
    {
        $slug = $this->cityIndexService->slugForName($cityName);
        $city = $this->cityIndexService->findBySlug($slug);
        if ($city) {
            return $city;
        }

        $needle = mb_strtolower(trim($cityName));
        foreach ($this->cityIndexService->topCities(40) as $candidate) {
            if (mb_strtolower($candidate->name) === $needle
                || str_contains(mb_strtolower($candidate->name), $needle)
                || str_contains($needle, mb_strtolower($candidate->name))) {
                return $candidate;
            }
            $aliases = is_array($candidate->aliases) ? $candidate->aliases : [];
            foreach ($aliases as $alias) {
                if (mb_strtolower((string) $alias) === $needle) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @return array{id:int,name:string}|null
     */
    private function resolveBrand(string $name): ?array
    {
        $hits = $this->lookupService->searchBrandsForListingFilters($name);
        $exact = $this->pickBestNameMatch($name, $hits);
        if ($exact) {
            return $exact;
        }

        $catalog = $this->lookupService->searchBrands($name);
        return $this->pickBestNameMatch($name, $catalog);
    }

    /**
     * @param  array<int, int>  $brandIds
     * @return array{id:int,name:string,brand_id?:int}|null
     */
    private function resolveModel(string $name, array $brandIds): ?array
    {
        $hits = $this->lookupService->searchModelsForListingFilters($name, $brandIds);
        $exact = $this->pickBestNameMatch($name, $hits);
        if ($exact) {
            return $exact;
        }

        $catalog = $this->lookupService->searchModels($name, $brandIds);
        return $this->pickBestNameMatch($name, $catalog);
    }

    /**
     * @param  class-string  $modelClass
     * @return array{id:int,name:string}|null
     */
    private function resolveNamedLookup(string $name, string $modelClass): ?array
    {
        $needle = mb_strtolower(trim($name));
        $rows = $modelClass::query()->select(['id', 'name'])->orderBy('name')->get();
        $best = null;
        $bestScore = 0;

        foreach ($rows as $row) {
            $candidate = mb_strtolower((string) $row->name);
            $score = 0;
            if ($candidate === $needle) {
                $score = 100;
            } elseif (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
                $score = 70;
            } elseif (similar_text($candidate, $needle) / max(mb_strlen($needle), 1) > 0.7) {
                $score = 50;
            }
            // Synonym expansion: Electric matches El, etc.
            $expandedNeedle = mb_strtolower($this->synonymService->expand($name));
            if ($expandedNeedle !== $needle && (str_contains($candidate, $expandedNeedle) || str_contains($expandedNeedle, $candidate))) {
                $score = max($score, 75);
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = ['id' => (int) $row->id, 'name' => (string) $row->name];
            }
        }

        return $bestScore >= 50 ? $best : null;
    }

    /**
     * @param  list<array{id:int,name:string,brand_id?:int}>  $rows
     * @return array{id:int,name:string,brand_id?:int}|null
     */
    private function pickBestNameMatch(string $name, array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        $needle = mb_strtolower(trim($name));
        $best = null;
        $bestScore = 0;

        foreach ($rows as $row) {
            $candidate = mb_strtolower((string) ($row['name'] ?? ''));
            $score = 0;
            if ($candidate === $needle) {
                $score = 100;
            } elseif (str_starts_with($candidate, $needle) || str_starts_with($needle, $candidate)) {
                $score = 80;
            } elseif (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
                $score = 60;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        return $bestScore >= 60 ? $best : ($rows[0] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeAiJson(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Strip markdown fences if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $text, $m)) {
            $text = trim($m[1]);
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try to extract first JSON object
        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @return array{
     *     filters: array<string, mixed>,
     *     labels: list<array{key: string, label: string}>,
     *     query: string,
     *     expanded_query: string,
     *     provider: ?string,
     *     cached: bool,
     *     fallback: bool
     * }
     */
    private function keywordFallback(string $query, string $expanded): array
    {
        $search = $expanded !== '' ? $expanded : $query;

        return [
            'filters' => ['search' => $search],
            'labels' => [['key' => 'search', 'label' => $search]],
            'query' => $query,
            'expanded_query' => $expanded,
            'provider' => null,
            'cached' => false,
            'fallback' => true,
        ];
    }

    /**
     * @return array{
     *     filters: array<string, mixed>,
     *     labels: list<array{key: string, label: string}>,
     *     query: string,
     *     expanded_query: string,
     *     provider: ?string,
     *     cached: bool,
     *     fallback: bool
     * }
     */
    private function emptyResult(string $query, string $expanded, bool $fallback): array
    {
        return [
            'filters' => [],
            'labels' => [],
            'query' => $query,
            'expanded_query' => $expanded,
            'provider' => null,
            'cached' => false,
            'fallback' => $fallback,
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function formatRangeLabel(string $key, int $value, string $locale): string
    {
        $isDa = $locale !== 'en';
        $formatted = number_format($value, 0, ',', '.');

        return match ($key) {
            'price_from' => ($isDa ? 'Fra ' : 'From ').$formatted.' kr',
            'price_to' => ($isDa ? 'Max ' : 'Max ').$formatted.' kr',
            'km_driven_from' => ($isDa ? 'Fra ' : 'From ').$formatted.' km',
            'km_driven_to' => ($isDa ? 'Under ' : 'Under ').$formatted.' km',
            'model_year_from' => ($isDa ? 'Fra ' : 'From ').$value,
            'model_year_to' => ($isDa ? 'Til ' : 'To ').$value,
            'ownership_tax_from' => ($isDa ? 'Ejerafgift fra ' : 'Tax from ').$formatted.' kr',
            'ownership_tax_to' => ($isDa ? 'Ejerafgift max ' : 'Tax max ').$formatted.' kr',
            'seats_min' => ($isDa ? 'Min. ' : 'Min ').$value.($isDa ? ' sæder' : ' seats'),
            default => $key.': '.$formatted,
        };
    }

    private function synonymHintsForPrompt(): string
    {
        $parts = [];
        foreach ($this->synonymService->map() as $from => $to) {
            $parts[] = $from.' → '.$to;
        }

        return implode('; ', array_slice($parts, 0, 40));
    }

    private function outputSchemaDescription(): string
    {
        return 'JSON object with optional keys: brand, model, fuel, body, gear, city, price_from, price_to, km_driven_from, km_driven_to, model_year_from, model_year_to, ownership_tax_from, ownership_tax_to, seats_min, intent (family|commute|null), search (residual keywords), labels (array of short human-readable chip strings). Use null for unused fields. Numbers must be integers in DKK / km / year.';
    }
}
