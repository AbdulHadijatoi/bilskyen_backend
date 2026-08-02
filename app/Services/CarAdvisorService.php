<?php

namespace App\Services;

use App\Exceptions\AiGenerationException;
use App\Helpers\FormatHelper;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class CarAdvisorService
{
    private const CANDIDATE_LIMIT = 50;

    private const TOP_EXPLAIN = 6;

    private const MIN_CANDIDATES_BEFORE_RELAX = 4;

    public function __construct(
        private AiService $aiService,
        private AiSearchParseService $aiSearchParseService,
        private VehicleService $vehicleService,
        private CarAdvisorScorer $scorer,
        private MarketPricingService $marketPricingService,
        private VehicleSearchSynonymService $synonymService,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{
     *     summary: string,
     *     profile: array<string, mixed>,
     *     filters: array<string, mixed>,
     *     labels: list<array{key: string, label: string}>,
     *     browse_url: string,
     *     recommendations: list<array<string, mixed>>,
     *     candidate_count: int,
     *     relaxed_filters: bool,
     *     provider: ?string,
     *     fallback_explain: bool
     * }
     */
    public function advise(string $message, string $locale = 'da', array $history = []): array
    {
        $message = trim($message);
        $locale = in_array($locale, ['da', 'en'], true) ? $locale : 'da';

        $profileResult = $this->buildProfile($message, $locale, $history);
        $profile = $profileResult['profile'];
        $provider = $profileResult['provider'];

        $filterSource = $this->profileToFilterSource($profile);
        $resolved = $this->aiSearchParseService->resolveAdvisorFilters($filterSource, $locale);
        $filters = $resolved['filters'];
        $labels = $resolved['labels'];

        if (! isset($filters['price_to']) && isset($profile['budget_max']) && is_numeric($profile['budget_max'])) {
            $filters['price_to'] = (int) $profile['budget_max'];
            $labels[] = [
                'key' => 'price_to',
                'label' => $locale === 'en'
                    ? 'Max '.number_format((int) $profile['budget_max'], 0, '.', '.').' kr'
                    : 'Max '.number_format((int) $profile['budget_max'], 0, ',', '.').' kr',
            ];
        }

        $fetchFilters = $this->broadenFilters($filters);
        $relaxed = false;
        $vehicles = $this->fetchCandidates($fetchFilters);

        if ($vehicles->count() < self::MIN_CANDIDATES_BEFORE_RELAX) {
            $relaxedFilters = $this->relaxFilters($fetchFilters);
            if ($relaxedFilters !== $fetchFilters) {
                $relaxedVehicles = $this->fetchCandidates($relaxedFilters);
                if ($relaxedVehicles->count() > $vehicles->count()) {
                    $vehicles = $relaxedVehicles;
                    $fetchFilters = $relaxedFilters;
                    $relaxed = true;
                }
            }
        }

        $scored = [];
        foreach ($vehicles as $vehicle) {
            /** @var Vehicle $vehicle */
            $market = $this->marketPricingService->evaluateVehicle($vehicle);
            $result = $this->scorer->score($vehicle, $profile, $market);
            $scored[] = [
                'vehicle' => $vehicle,
                'score' => $result['score'],
                'match_reasons' => $result['match_reasons'],
                'tradeoffs' => $result['tradeoffs'],
                'components' => $result['components'],
                'market' => $market,
            ];
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, self::TOP_EXPLAIN);

        $explainMap = [];
        $fallbackExplain = false;
        try {
            $explainMap = $this->explainRecommendations($profile, $top, $locale);
            if ($explainMap === []) {
                $fallbackExplain = true;
            }
        } catch (\Throwable $e) {
            Log::info('car_advisor.explain_fallback', ['message' => $e->getMessage()]);
            $fallbackExplain = true;
        }

        $recommendations = [];
        foreach ($top as $row) {
            /** @var Vehicle $vehicle */
            $vehicle = $row['vehicle'];
            $id = (int) $vehicle->id;
            $ai = $explainMap[$id] ?? null;

            $recommendations[] = $this->presentRecommendation(
                vehicle: $vehicle,
                score: $row['score'],
                matchReasons: $row['match_reasons'],
                tradeoffs: $row['tradeoffs'],
                market: $row['market'],
                explanation: is_string($ai['explanation'] ?? null) ? $ai['explanation'] : null,
                ownershipOutlook: is_string($ai['ownership_outlook'] ?? null) ? $ai['ownership_outlook'] : null,
                locale: $locale,
            );
        }

        $summary = is_string($profile['summary'] ?? null) && trim((string) $profile['summary']) !== ''
            ? trim((string) $profile['summary'])
            : ($locale === 'en'
                ? 'Here are the best matches from current listings for your description.'
                : 'Her er de bedste matches fra aktuelle annoncer ud fra din beskrivelse.');

        return [
            'summary' => $summary,
            'profile' => [
                'budget_max' => $profile['budget_max'] ?? null,
                'use_case' => $profile['use_case'] ?? null,
                'needs' => array_values($profile['needs'] ?? []),
                'priorities' => array_values($profile['priorities'] ?? []),
                'summary' => $summary,
            ],
            'filters' => $filters,
            'labels' => array_values($labels),
            'browse_url' => $this->buildBrowseUrl($filters),
            'recommendations' => $recommendations,
            'candidate_count' => count($scored),
            'relaxed_filters' => $relaxed,
            'provider' => $provider,
            'fallback_explain' => $fallbackExplain,
        ];
    }

    /**
     * Curated lifestyle example prompts for the advisor UI.
     *
     * @return list<string>
     */
    public function examplePrompts(string $locale = 'da'): array
    {
        if ($locale === 'en') {
            return [
                'I have 150,000 DKK, mostly city driving, need space for a stroller, want low ownership costs, and like sporty-looking cars.',
                'Family of 4, max 250,000 kr, automatic, diesel or hybrid, room for weekend trips.',
                'Electric commute under 300,000 kr, low annual tax, parking in Copenhagen.',
                'First car under 80,000 kr, reliable and cheap to run, mostly short trips.',
            ];
        }

        return [
            'Jeg har 150.000 kr, kører mest i byen, skal have plads til barnevogn, vil undgå dyre reparationer, og synes om sporty biler.',
            'Familie på 4, max 250.000 kr, automatgear, diesel eller hybrid, plads til weekendture.',
            'Elbil til pendling under 300.000 kr, lav ejerafgift, parkering i København.',
            'Første bil under 80.000 kr, pålidelig og billig i drift, mest korte ture.',
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{profile: array<string, mixed>, provider: ?string}
     */
    private function buildProfile(string $message, string $locale, array $history): array
    {
        $historyLines = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'Assistant' : 'User';
            $historyLines[] = $role.': '.($turn['content'] ?? '');
        }

        try {
            $result = $this->aiService->generateCarAdvisorProfile(
                context: [
                    'user_message' => $message,
                    'conversation_history' => $historyLines !== [] ? implode("\n", $historyLines) : '(none)',
                    'expanded_query' => $this->synonymService->expand($message),
                    'output_schema' => $this->profileSchemaDescription(),
                ],
                locale: $locale,
            );

            $parsed = $this->decodeAiJson((string) ($result['text'] ?? ''));
            $profile = $this->normalizeProfile($parsed, $message);

            return [
                'profile' => $profile,
                'provider' => $result['provider'] ?? null,
            ];
        } catch (AiGenerationException $e) {
            Log::info('car_advisor.profile_fallback', ['message' => $e->getMessage()]);

            return [
                'profile' => $this->heuristicProfile($message, $locale),
                'provider' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('car_advisor.profile_error', ['message' => $e->getMessage()]);

            return [
                'profile' => $this->heuristicProfile($message, $locale),
                'provider' => null,
            ];
        }
    }

    private function profileSchemaDescription(): string
    {
        return 'JSON object with keys: budget_max (int DKK or null), use_case (city|mixed|highway|family|null), '
            .'needs (array of tokens: stroller, space, family, city, low_repair_risk, low_tax, low_ownership_cost, sporty_look, automatic, electric), '
            .'priorities (ordered array of short priority strings), '
            .'summary (1-2 sentence brief in user language), '
            .'brand, model, fuel, body, gear, city (strings or null), '
            .'price_from, price_to, km_driven_from, km_driven_to, model_year_from, model_year_to, ownership_tax_to, seats_min (ints or null), '
            .'intent (family|commute|null), search (residual keywords or null), labels (array of short chips).';
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function normalizeProfile(array $parsed, string $message): array
    {
        $needs = [];
        foreach ($parsed['needs'] ?? [] as $need) {
            if (is_string($need) && trim($need) !== '') {
                $needs[] = mb_strtolower(trim($need));
            }
        }

        $priorities = [];
        foreach ($parsed['priorities'] ?? [] as $priority) {
            if (is_string($priority) && trim($priority) !== '') {
                $priorities[] = trim($priority);
            }
        }

        $budget = $parsed['budget_max'] ?? $parsed['price_to'] ?? null;
        $budgetMax = is_numeric($budget) ? (int) $budget : null;

        $useCase = is_string($parsed['use_case'] ?? null) ? mb_strtolower(trim((string) $parsed['use_case'])) : null;
        if (! in_array($useCase, ['city', 'mixed', 'highway', 'family'], true)) {
            $useCase = ($parsed['intent'] ?? null) === 'family' ? 'family' : 'mixed';
        }

        return [
            'budget_max' => $budgetMax,
            'use_case' => $useCase,
            'needs' => array_values(array_unique($needs)),
            'priorities' => array_values($priorities),
            'summary' => is_string($parsed['summary'] ?? null) ? trim((string) $parsed['summary']) : '',
            'brand' => $parsed['brand'] ?? null,
            'model' => $parsed['model'] ?? null,
            'fuel' => $parsed['fuel'] ?? null,
            'body' => $parsed['body'] ?? null,
            'gear' => $parsed['gear'] ?? null,
            'city' => $parsed['city'] ?? null,
            'price_from' => $parsed['price_from'] ?? null,
            'price_to' => $parsed['price_to'] ?? $budgetMax,
            'km_driven_from' => $parsed['km_driven_from'] ?? null,
            'km_driven_to' => $parsed['km_driven_to'] ?? null,
            'model_year_from' => $parsed['model_year_from'] ?? null,
            'model_year_to' => $parsed['model_year_to'] ?? null,
            'ownership_tax_to' => $parsed['ownership_tax_to'] ?? null,
            'seats_min' => $parsed['seats_min'] ?? null,
            'intent' => $parsed['intent'] ?? null,
            'search' => $parsed['search'] ?? null,
            'labels' => $parsed['labels'] ?? [],
            'raw_message' => $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function heuristicProfile(string $message, string $locale): array
    {
        $lower = mb_strtolower($message);
        $needs = [];
        $useCase = 'mixed';
        $budgetMax = null;

        if (preg_match('/(\d{1,3}(?:[.\s]\d{3})+|\d{4,7})\s*(?:kr|dkk|,-)?/iu', $message, $m)) {
            $digits = (int) preg_replace('/\D+/', '', $m[1]);
            if ($digits >= 10000 && $digits <= 2000000) {
                $budgetMax = $digits;
            }
        }

        if (str_contains($lower, 'barnevogn') || str_contains($lower, 'stroller') || str_contains($lower, 'plads') || str_contains($lower, 'familie')) {
            $needs[] = 'space';
            $needs[] = 'stroller';
            $useCase = 'family';
        }
        if (str_contains($lower, 'byen') || str_contains($lower, 'city') || str_contains($lower, 'københavn') || str_contains($lower, 'copenhagen')) {
            $needs[] = 'city';
            if ($useCase !== 'family') {
                $useCase = 'city';
            }
        }
        if (str_contains($lower, 'reparat') || str_contains($lower, 'repair') || str_contains($lower, 'pålidelig') || str_contains($lower, 'reliable')) {
            $needs[] = 'low_repair_risk';
        }
        if (str_contains($lower, 'ejerafgift') || str_contains($lower, 'ownership') || str_contains($lower, 'billig i drift')) {
            $needs[] = 'low_tax';
            $needs[] = 'low_ownership_cost';
        }
        if (str_contains($lower, 'sporty') || str_contains($lower, 'sport')) {
            $needs[] = 'sporty_look';
        }
        if (str_contains($lower, 'automat')) {
            $needs[] = 'automatic';
        }
        if (str_contains($lower, 'elbil') || str_contains($lower, 'electric')) {
            $needs[] = 'electric';
        }

        return $this->normalizeProfile([
            'budget_max' => $budgetMax,
            'use_case' => $useCase,
            'needs' => $needs,
            'priorities' => $needs,
            'summary' => $locale === 'en'
                ? 'Matched from your description using basic rules while AI was unavailable.'
                : 'Matchet ud fra din beskrivelse med grundregler, mens AI ikke var tilgængelig.',
            'price_to' => $budgetMax,
            'seats_min' => $useCase === 'family' ? 5 : null,
            'intent' => $useCase === 'family' ? 'family' : null,
            'search' => null,
            'fuel' => in_array('electric', $needs, true) ? 'Electric' : null,
            'gear' => in_array('automatic', $needs, true) ? 'Automatic' : null,
        ], $message);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function profileToFilterSource(array $profile): array
    {
        return [
            'brand' => $profile['brand'] ?? null,
            'model' => $profile['model'] ?? null,
            'fuel' => $profile['fuel'] ?? null,
            'body' => $profile['body'] ?? null,
            'gear' => $profile['gear'] ?? null,
            'city' => $profile['city'] ?? null,
            'price_from' => $profile['price_from'] ?? null,
            'price_to' => $profile['price_to'] ?? $profile['budget_max'] ?? null,
            'km_driven_from' => $profile['km_driven_from'] ?? null,
            'km_driven_to' => $profile['km_driven_to'] ?? null,
            'model_year_from' => $profile['model_year_from'] ?? null,
            'model_year_to' => $profile['model_year_to'] ?? null,
            'ownership_tax_to' => $profile['ownership_tax_to'] ?? null,
            'seats_min' => $profile['seats_min'] ?? null,
            'intent' => $profile['intent'] ?? null,
            'search' => $profile['search'] ?? null,
            'labels' => $profile['labels'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function broadenFilters(array $filters): array
    {
        $out = $filters;
        if (isset($out['price_to']) && is_numeric($out['price_to'])) {
            $out['price_to'] = (int) round(((int) $out['price_to']) * 1.2);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function relaxFilters(array $filters): array
    {
        $keep = ['price_from', 'price_to', 'seats_min', 'city_slug', 'city'];
        $relaxed = [];
        foreach ($keep as $key) {
            if (isset($filters[$key])) {
                $relaxed[$key] = $filters[$key];
            }
        }

        return $relaxed !== [] ? $relaxed : $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, Vehicle>
     */
    private function fetchCandidates(array $filters)
    {
        $paginator = $this->vehicleService->getPublicVehiclesWithAdvancedFilters(
            with: ['bodyType'],
            filters: $filters,
            perPage: self::CANDIDATE_LIMIT,
            page: 1,
        );

        return collect($paginator->items());
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array<string, mixed>>  $top
     * @return array<int, array{explanation?: string, ownership_outlook?: string}>
     */
    private function explainRecommendations(array $profile, array $top, string $locale): array
    {
        if ($top === []) {
            return [];
        }

        $payload = [];
        foreach ($top as $row) {
            /** @var Vehicle $vehicle */
            $vehicle = $row['vehicle'];
            $market = $row['market'];
            $payload[] = [
                'id' => (int) $vehicle->id,
                'title' => (string) ($vehicle->title ?? ''),
                'price' => $vehicle->price !== null ? (float) $vehicle->price : null,
                'year' => $vehicle->model_year ?? $vehicle->first_registration_year,
                'km' => $vehicle->km_driven,
                'fuel' => $vehicle->fuelType?->name,
                'body' => $vehicle->bodyType?->name,
                'seats' => $vehicle->seats_max ?? $vehicle->seats_min,
                'ownership_tax' => $vehicle->calculated_ownership_tax ?? $vehicle->annual_tax,
                'match_score' => $row['score'],
                'match_reasons' => $row['match_reasons'],
                'tradeoffs' => $row['tradeoffs'],
                'market_label' => $market['label'] ?? null,
                'market_median' => $market['median_price'] ?? null,
                'market_diff_percent' => $market['diff_percent'] ?? null,
            ];
        }

        $result = $this->aiService->generateCarAdvisorExplain(
            context: [
                'buyer_summary' => $profile['summary'] ?? '',
                'buyer_needs' => implode(', ', $profile['needs'] ?? []),
                'buyer_use_case' => $profile['use_case'] ?? '',
                'budget_max' => $profile['budget_max'] ?? '',
                'candidates_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'rules' => 'Explain ONLY using provided facts and match_reasons/tradeoffs. No recalls or invented known issues. Ownership outlook must use ownership_tax and market_label/median only, phrased as estimate.',
            ],
            locale: $locale,
        );

        $parsed = $this->decodeAiJson((string) ($result['text'] ?? ''));
        $items = $parsed['recommendations'] ?? $parsed['items'] ?? null;
        if (! is_array($items)) {
            return [];
        }

        $map = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['id'])) {
                continue;
            }
            $map[(int) $item['id']] = [
                'explanation' => isset($item['explanation']) && is_string($item['explanation'])
                    ? trim($item['explanation'])
                    : null,
                'ownership_outlook' => isset($item['ownership_outlook']) && is_string($item['ownership_outlook'])
                    ? trim($item['ownership_outlook'])
                    : null,
            ];
        }

        return $map;
    }

    /**
     * @param  list<string>  $matchReasons
     * @param  list<string>  $tradeoffs
     * @param  array<string, mixed>|null  $market
     * @return array<string, mixed>
     */
    private function presentRecommendation(
        Vehicle $vehicle,
        int $score,
        array $matchReasons,
        array $tradeoffs,
        ?array $market,
        ?string $explanation,
        ?string $ownershipOutlook,
        string $locale,
    ): array {
        $image = $vehicle->images->first();
        $imageUrl = $image?->thumbnail_url ?? $image?->image_url ?? null;
        $tax = $vehicle->calculated_ownership_tax ?? $vehicle->annual_tax;

        if ($explanation === null || $explanation === '') {
            $explanation = implode(' · ', array_slice($matchReasons, 0, 3));
        }

        if ($ownershipOutlook === null || $ownershipOutlook === '') {
            $ownershipOutlook = $this->defaultOwnershipOutlook($tax, $market, $locale);
        }

        return [
            'id' => (int) $vehicle->id,
            'slug' => (string) ($vehicle->slug ?? $vehicle->id),
            'title' => (string) ($vehicle->title ?? ''),
            'price' => $vehicle->price !== null ? (float) $vehicle->price : null,
            'price_formatted' => FormatHelper::formatCurrency(
                $vehicle->price !== null ? (float) $vehicle->price : null
            ),
            'year' => $vehicle->model_year ?? $vehicle->first_registration_year,
            'km_driven' => $vehicle->km_driven,
            'fuel' => $vehicle->fuelType?->name,
            'body' => $vehicle->bodyType?->name,
            'gear' => $vehicle->gearType?->name,
            'image_url' => $imageUrl,
            'detail_url' => url('/biler/'.($vehicle->slug ?? $vehicle->id)),
            'enquire_url' => url('/biler/'.($vehicle->slug ?? $vehicle->id).'/enquire'),
            'match_score' => $score,
            'match_percent' => $score,
            'explanation' => $explanation,
            'match_reasons' => $matchReasons,
            'tradeoffs' => $tradeoffs,
            'ownership_tax' => $tax !== null ? (float) $tax : null,
            'ownership_tax_formatted' => $tax !== null
                ? FormatHelper::formatCurrency((float) $tax)
                : null,
            'ownership_outlook' => $ownershipOutlook,
            'fair_price' => $market === null ? null : [
                'label' => $market['label'] ?? null,
                'median_price' => $market['median_price'] ?? null,
                'diff_percent' => $market['diff_percent'] ?? null,
                'label_text' => $this->fairPriceLabelText($market['label'] ?? null, $locale),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $market
     */
    private function defaultOwnershipOutlook(mixed $tax, ?array $market, string $locale): string
    {
        $parts = [];
        if ($tax !== null) {
            $parts[] = $locale === 'en'
                ? 'Annual ownership tax about '.FormatHelper::formatCurrency((float) $tax)
                : 'Årlig ejerafgift ca. '.FormatHelper::formatCurrency((float) $tax);
        }
        if ($market !== null && isset($market['label'])) {
            $parts[] = $this->fairPriceLabelText($market['label'], $locale);
        }

        if ($parts === []) {
            return $locale === 'en'
                ? 'Ownership outlook based on listing facts only (estimate).'
                : 'Driftsudsigter baseret kun på annoncefakta (estimat).';
        }

        $suffix = $locale === 'en' ? ' (estimate)' : ' (estimat)';

        return implode(' · ', $parts).$suffix;
    }

    private function fairPriceLabelText(?string $label, string $locale): string
    {
        return match ($label) {
            'below_market' => $locale === 'en' ? 'Below similar market prices' : 'Under lignende markedspriser',
            'fair_price' => $locale === 'en' ? 'Fair vs similar listings' : 'Fair ift. lignende annoncer',
            'above_market' => $locale === 'en' ? 'Above similar market prices' : 'Over lignende markedspriser',
            default => $locale === 'en' ? 'Market comparison unavailable' : 'Markedssammenligning ikke tilgængelig',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildBrowseUrl(array $filters): string
    {
        $query = [];
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $query[$key][] = $item;
                }
            } else {
                $query[$key] = $value;
            }
        }

        $qs = http_build_query($query);

        return url('/biler').($qs !== '' ? '?'.$qs : '');
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

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
            $text = $m[1];
        } elseif (preg_match('/\{.*\}/s', $text, $m)) {
            $text = $m[0];
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : [];
    }
}
