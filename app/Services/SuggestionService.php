<?php

namespace App\Services;

use App\Constants\VehicleListStatus;
use App\Models\MarketplaceCity;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Precomputed, inventory-grounded search / advisor suggestion pools.
 * Served from cache with deterministic day+session rotation — no LLM on page load.
 */
class SuggestionService
{
    public const SURFACE_HOME_CHIPS = 'home_chips';

    public const SURFACE_SUGGEST_EXAMPLES = 'suggest_examples';

    public const SURFACE_ADVISOR_PROMPTS = 'advisor_prompts';

    public const SURFACE_LIFESTYLE_CHIPS = 'lifestyle_chips';

    private const CACHE_TTL_SECONDS = 93600; // 26 hours

    private const MIN_INVENTORY_COUNT = 5;

    private const LOCALES = ['da', 'en'];

    private const SURFACES = [
        self::SURFACE_HOME_CHIPS,
        self::SURFACE_SUGGEST_EXAMPLES,
        self::SURFACE_ADVISOR_PROMPTS,
        self::SURFACE_LIFESTYLE_CHIPS,
    ];

    /** @var list<int> */
    private const PRICE_BANDS = [80_000, 150_000, 200_000, 250_000, 300_000];

    public function cacheKey(string $locale, string $surface): string
    {
        return 'suggestions:'.($locale === 'en' ? 'en' : 'da').':'.$surface;
    }

    /**
     * Rebuild all suggestion pools from published inventory + templates.
     */
    public function refresh(): int
    {
        $built = 0;
        foreach (self::LOCALES as $locale) {
            $pool = $this->buildPool($locale);
            foreach (self::SURFACES as $surface) {
                $items = $pool[$surface] ?? [];
                Cache::put($this->cacheKey($locale, $surface), $items, self::CACHE_TTL_SECONDS);
                $built += count($items);
            }
        }

        return $built;
    }

    /**
     * Sample suggestions for a surface. Falls back to seed lists when cache is empty.
     *
     * @return list<array{text: string, type: string, score: float, filters?: array<string, mixed>, label?: string, href?: string, query?: string}>
     */
    public function forSurface(string $surface, string $locale = 'da', int $limit = 4, ?string $sessionSeed = null): array
    {
        $locale = $locale === 'en' ? 'en' : 'da';
        $limit = max(1, min(12, $limit));

        $pool = Cache::get($this->cacheKey($locale, $surface));
        if (! is_array($pool) || $pool === []) {
            $pool = $this->seedPool($locale)[$surface] ?? [];
        }

        if ($pool === []) {
            return [];
        }

        return $this->sampleDeterministic($pool, $limit, $locale, $surface, $sessionSeed);
    }

    /**
     * Short NL example queries for home chips / suggest API (string list).
     *
     * @return list<string>
     */
    public function exampleQueries(string $locale = 'da', ?string $sessionSeed = null, int $limit = 4): array
    {
        $items = $this->forSurface(self::SURFACE_HOME_CHIPS, $locale, $limit, $sessionSeed);

        return array_values(array_map(fn (array $item) => (string) $item['text'], $items));
    }

    /**
     * Longer lifestyle prompts for Find My Perfect Car.
     *
     * @return list<string>
     */
    public function examplePrompts(string $locale = 'da', ?string $sessionSeed = null, int $limit = 4): array
    {
        $items = $this->forSurface(self::SURFACE_ADVISOR_PROMPTS, $locale, $limit, $sessionSeed);

        return array_values(array_map(fn (array $item) => (string) $item['text'], $items));
    }

    /**
     * Lifestyle chips for the home search card.
     *
     * @return list<array{label: string, href: string, query: string}>
     */
    public function lifestyleChips(string $locale = 'da', ?string $sessionSeed = null, int $limit = 2): array
    {
        $items = $this->forSurface(self::SURFACE_LIFESTYLE_CHIPS, $locale, $limit, $sessionSeed);
        $out = [];
        foreach ($items as $item) {
            $query = (string) ($item['query'] ?? $item['text'] ?? '');
            $label = (string) ($item['label'] ?? $item['text'] ?? '');
            if ($label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'query' => $query,
                'href' => route('find-perfect-car', array_filter(['q' => $query !== '' ? $query : null])),
            ];
        }

        return $out;
    }

    /**
     * Prefix-filter the suggest_examples pool (no AI).
     *
     * @return list<string>
     */
    public function matchingExamples(string $term, string $locale = 'da', int $limit = 3, ?string $sessionSeed = null): array
    {
        $locale = $locale === 'en' ? 'en' : 'da';
        $pool = Cache::get($this->cacheKey($locale, self::SURFACE_SUGGEST_EXAMPLES));
        if (! is_array($pool) || $pool === []) {
            $pool = $this->seedPool($locale)[self::SURFACE_SUGGEST_EXAMPLES] ?? [];
        }

        $term = trim($term);
        if ($term === '') {
            return array_values(array_map(
                fn (array $item) => (string) $item['text'],
                $this->sampleDeterministic($pool, $limit, $locale, self::SURFACE_SUGGEST_EXAMPLES, $sessionSeed)
            ));
        }

        $needle = mb_strtolower($term);
        $matching = array_values(array_filter(
            $pool,
            fn (array $item) => Str::contains(mb_strtolower((string) ($item['text'] ?? '')), $needle)
        ));

        if ($matching === []) {
            return array_values(array_map(
                fn (array $item) => (string) $item['text'],
                $this->sampleDeterministic($pool, $limit, $locale, self::SURFACE_SUGGEST_EXAMPLES, $sessionSeed)
            ));
        }

        usort($matching, fn (array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return array_values(array_map(
            fn (array $item) => (string) $item['text'],
            array_slice($matching, 0, $limit)
        ));
    }

    /**
     * @return array<string, list<array{text: string, type: string, score: float, filters?: array<string, mixed>, label?: string, query?: string}>>
     */
    private function buildPool(string $locale): array
    {
        $seeds = $this->seedPool($locale);
        $inventory = $this->mineInventory($locale);

        $home = $this->mergeUniqueByText(array_merge($inventory['short'] ?? [], $seeds[self::SURFACE_HOME_CHIPS]));
        $suggest = $this->mergeUniqueByText(array_merge($inventory['short'] ?? [], $seeds[self::SURFACE_SUGGEST_EXAMPLES]));
        $advisor = $this->mergeUniqueByText(array_merge($inventory['advisor'] ?? [], $seeds[self::SURFACE_ADVISOR_PROMPTS]));
        $lifestyle = $this->mergeUniqueByText(array_merge($inventory['lifestyle'] ?? [], $seeds[self::SURFACE_LIFESTYLE_CHIPS]));

        return [
            self::SURFACE_HOME_CHIPS => $home,
            self::SURFACE_SUGGEST_EXAMPLES => $suggest,
            self::SURFACE_ADVISOR_PROMPTS => $advisor,
            self::SURFACE_LIFESTYLE_CHIPS => $lifestyle,
        ];
    }

    /**
     * @return array{short: list<array>, advisor: list<array>, lifestyle: list<array>}
     */
    private function mineInventory(string $locale): array
    {
        $short = [];
        $advisor = [];
        $lifestyle = [];

        try {
            $base = Vehicle::query()
                ->where('list_status_id', VehicleListStatus::PUBLISHED);

            $priceStats = (clone $base)
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->selectRaw('COUNT(*) as cnt, AVG(price) as avg_price')
                ->first();

            $avgPrice = (int) round((float) ($priceStats->avg_price ?? 200_000));
            $budgetP50 = $this->roundBudget($avgPrice);
            $budgetP25 = $this->roundBudget((int) round($avgPrice * 0.7));

            // Brand + model combos
            $brandModels = (clone $base)
                ->whereNotNull('vehicles.brand_id')
                ->whereNotNull('vehicles.model_id')
                ->join('dmr_brands', 'dmr_brands.id', '=', 'vehicles.brand_id')
                ->join('dmr_models', 'dmr_models.id', '=', 'vehicles.model_id')
                ->whereNull('dmr_brands.deleted_at')
                ->whereNull('dmr_models.deleted_at')
                ->select(
                    'vehicles.brand_id',
                    'vehicles.model_id',
                    'dmr_brands.name as brand_name',
                    'dmr_models.name as model_name',
                    DB::raw('COUNT(*) as cnt')
                )
                ->groupBy('vehicles.brand_id', 'vehicles.model_id', 'dmr_brands.name', 'dmr_models.name')
                ->having('cnt', '>=', self::MIN_INVENTORY_COUNT)
                ->orderByDesc('cnt')
                ->limit(12)
                ->get();

            foreach ($brandModels as $row) {
                $brand = trim((string) $row->brand_name);
                $model = trim((string) $row->model_name);
                if ($brand === '' || $model === '') {
                    continue;
                }
                $cnt = (int) $row->cnt;
                $text = $locale === 'en'
                    ? "{$brand} {$model}"
                    : "{$brand} {$model}";
                $short[] = $this->item($text, 'inventory', 50 + min(40, $cnt), [
                    'brand_id' => [(int) $row->brand_id],
                    'model_id' => [(int) $row->model_id],
                ]);
            }

            // Fuel × price bands
            $fuels = (clone $base)
                ->whereNotNull('vehicles.fuel_type_id')
                ->join('dmr_drive_energies', 'dmr_drive_energies.id', '=', 'vehicles.fuel_type_id')
                ->whereNull('dmr_drive_energies.deleted_at')
                ->select(
                    'vehicles.fuel_type_id',
                    'dmr_drive_energies.name as fuel_name',
                    DB::raw('COUNT(*) as cnt')
                )
                ->groupBy('vehicles.fuel_type_id', 'dmr_drive_energies.name')
                ->having('cnt', '>=', self::MIN_INVENTORY_COUNT)
                ->orderByDesc('cnt')
                ->limit(8)
                ->get();

            foreach ($fuels as $fuelRow) {
                $fuelName = trim((string) $fuelRow->fuel_name);
                if ($fuelName === '') {
                    continue;
                }
                $fuelId = (int) $fuelRow->fuel_type_id;
                $fuelLabel = $this->localizeFuelLabel($fuelName, $locale);

                foreach (self::PRICE_BANDS as $band) {
                    $count = (clone $base)
                        ->where('fuel_type_id', $fuelId)
                        ->whereNotNull('price')
                        ->where('price', '>', 0)
                        ->where('price', '<=', $band)
                        ->count();

                    if ($count < self::MIN_INVENTORY_COUNT) {
                        continue;
                    }

                    $text = $locale === 'en'
                        ? "{$fuelLabel} under ".$this->formatPriceEn($band)
                        : "{$fuelLabel} under ".$this->formatPriceDa($band);

                    $short[] = $this->item($text, 'inventory', 40 + min(30, $count), [
                        'fuel_type_id' => [$fuelId],
                        'price_to' => $band,
                    ]);
                }
            }

            // Top cities from marketplace index
            $cities = MarketplaceCity::query()
                ->where('published_vehicle_count', '>=', self::MIN_INVENTORY_COUNT)
                ->orderByDesc('published_vehicle_count')
                ->limit(6)
                ->get(['name', 'slug', 'published_vehicle_count']);

            foreach ($cities as $city) {
                $name = trim((string) $city->name);
                if ($name === '') {
                    continue;
                }
                $cnt = (int) $city->published_vehicle_count;
                $text = $locale === 'en'
                    ? "Family car {$name}"
                    : "Familiebil {$name}";
                $short[] = $this->item($text, 'inventory', 35 + min(25, $cnt), [
                    'city_slug' => $city->slug,
                ]);
            }

            // Advisor / lifestyle from budgets
            if ($locale === 'en') {
                $advisor[] = $this->item(
                    "Family of 4, max {$this->formatPriceEn($budgetP50)}, automatic, diesel or hybrid, room for weekend trips.",
                    'template',
                    60,
                    ['price_to' => $budgetP50]
                );
                $advisor[] = $this->item(
                    "Electric commute under {$this->formatPriceEn($budgetP50)}, low annual tax, parking in Copenhagen.",
                    'template',
                    55,
                    ['price_to' => $budgetP50]
                );
                $advisor[] = $this->item(
                    "I have {$this->formatPriceEn($budgetP25)}, mostly city driving, need space for a stroller, want low ownership costs.",
                    'template',
                    50,
                    ['price_to' => $budgetP25]
                );
                $advisor[] = $this->item(
                    "First car under {$this->formatPriceEn(min(80_000, $budgetP25))}, reliable and cheap to run, mostly short trips.",
                    'template',
                    45,
                    ['price_to' => min(80_000, $budgetP25)]
                );

                $lifestyle[] = $this->item(
                    'Family car, weekend trips',
                    'lifestyle',
                    70,
                    [],
                    'Family car, weekend trips',
                    "Family of 4, max {$this->formatPriceEn($budgetP50)}, room for weekend trips."
                );
                $lifestyle[] = $this->item(
                    'First car, low insurance',
                    'lifestyle',
                    65,
                    [],
                    'First car, low insurance',
                    "First car under {$this->formatPriceEn(min(80_000, $budgetP25))}, reliable and cheap to insure."
                );
                $lifestyle[] = $this->item(
                    'City commute, electric',
                    'lifestyle',
                    60,
                    [],
                    'City commute, electric',
                    "Electric commute under {$this->formatPriceEn($budgetP50)}, mostly city driving."
                );
            } else {
                $advisor[] = $this->item(
                    "Familie på 4, max {$this->formatPriceDa($budgetP50)}, automatgear, diesel eller hybrid, plads til weekendture.",
                    'template',
                    60,
                    ['price_to' => $budgetP50]
                );
                $advisor[] = $this->item(
                    "Elbil til pendling under {$this->formatPriceDa($budgetP50)}, lav ejerafgift, parkering i København.",
                    'template',
                    55,
                    ['price_to' => $budgetP50]
                );
                $advisor[] = $this->item(
                    "Jeg har {$this->formatPriceDa($budgetP25)}, kører mest i byen, skal have plads til barnevogn, vil undgå dyre reparationer.",
                    'template',
                    50,
                    ['price_to' => $budgetP25]
                );
                $advisor[] = $this->item(
                    "Første bil under {$this->formatPriceDa(min(80_000, $budgetP25))}, pålidelig og billig i drift, mest korte ture.",
                    'template',
                    45,
                    ['price_to' => min(80_000, $budgetP25)]
                );

                $lifestyle[] = $this->item(
                    'Familiabil, weekendture',
                    'lifestyle',
                    70,
                    [],
                    'Familiabil, weekendture',
                    "Familie på 4, max {$this->formatPriceDa($budgetP50)}, plads til weekendture."
                );
                $lifestyle[] = $this->item(
                    'Første bil, lav forsikring',
                    'lifestyle',
                    65,
                    [],
                    'Første bil, lav forsikring',
                    "Første bil under {$this->formatPriceDa(min(80_000, $budgetP25))}, pålidelig og billig i forsikring."
                );
                $lifestyle[] = $this->item(
                    'Bypendling, elbil',
                    'lifestyle',
                    60,
                    [],
                    'Bypendling, elbil',
                    "Elbil til pendling under {$this->formatPriceDa($budgetP50)}, mest bykørsel."
                );
            }
        } catch (\Throwable $e) {
            Log::warning('SuggestionService inventory mining failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'short' => $short,
            'advisor' => $advisor,
            'lifestyle' => $lifestyle,
        ];
    }

    /**
     * @return array<string, list<array{text: string, type: string, score: float, filters?: array<string, mixed>, label?: string, query?: string}>>
     */
    private function seedPool(string $locale): array
    {
        if ($locale === 'en') {
            $short = [
                $this->item('Electric car under 200,000', 'template', 100),
                $this->item('VW Golf diesel', 'template', 95),
                $this->item('Family car Aarhus', 'template', 90),
                $this->item('Automatic hybrid 2020 or newer', 'template', 85),
            ];
            $advisor = [
                $this->item('I have 150,000 DKK, mostly city driving, need space for a stroller, want low ownership costs, and like sporty-looking cars.', 'template', 100),
                $this->item('Family of 4, max 250,000 kr, automatic, diesel or hybrid, room for weekend trips.', 'template', 95),
                $this->item('Electric commute under 300,000 kr, low annual tax, parking in Copenhagen.', 'template', 90),
                $this->item('First car under 80,000 kr, reliable and cheap to run, mostly short trips.', 'template', 85),
            ];
            $lifestyle = [
                $this->item('Family car, weekend trips', 'lifestyle', 100, [], 'Family car, weekend trips', 'Family of 4, room for weekend trips, reliable and practical.'),
                $this->item('First car, low insurance', 'lifestyle', 95, [], 'First car, low insurance', 'First car, reliable and cheap to insure, mostly short trips.'),
            ];
        } else {
            $short = [
                $this->item('Elbil under 200.000', 'template', 100),
                $this->item('VW Golf diesel', 'template', 95),
                $this->item('Familiebil Aarhus', 'template', 90),
                $this->item('Automatgear hybrid 2020 eller nyere', 'template', 85),
            ];
            $advisor = [
                $this->item('Jeg har 150.000 kr, kører mest i byen, skal have plads til barnevogn, vil undgå dyre reparationer, og synes om sporty biler.', 'template', 100),
                $this->item('Familie på 4, max 250.000 kr, automatgear, diesel eller hybrid, plads til weekendture.', 'template', 95),
                $this->item('Elbil til pendling under 300.000 kr, lav ejerafgift, parkering i København.', 'template', 90),
                $this->item('Første bil under 80.000 kr, pålidelig og billig i drift, mest korte ture.', 'template', 85),
            ];
            $lifestyle = [
                $this->item('Familiabil, weekendture', 'lifestyle', 100, [], 'Familiabil, weekendture', 'Familie på 4, plads til weekendture, pålidelig og praktisk.'),
                $this->item('Første bil, lav forsikring', 'lifestyle', 95, [], 'Første bil, lav forsikring', 'Første bil, pålidelig og billig i forsikring, mest korte ture.'),
            ];
        }

        return [
            self::SURFACE_HOME_CHIPS => $short,
            self::SURFACE_SUGGEST_EXAMPLES => $short,
            self::SURFACE_ADVISOR_PROMPTS => $advisor,
            self::SURFACE_LIFESTYLE_CHIPS => $lifestyle,
        ];
    }

    /**
     * @param  list<array{text: string, type: string, score: float}>  $pool
     * @return list<array{text: string, type: string, score: float}>
     */
    private function sampleDeterministic(array $pool, int $limit, string $locale, string $surface, ?string $sessionSeed): array
    {
        if (count($pool) <= $limit) {
            return array_values($pool);
        }

        $seed = hash('sha256', date('Y-m-d').'|'.($sessionSeed ?? 'anon').'|'.$locale.'|'.$surface);
        $indexed = [];
        foreach ($pool as $i => $item) {
            $weight = (float) ($item['score'] ?? 1);
            $hash = hash('sha256', $seed.'|'.$i.'|'.($item['text'] ?? ''));
            // Higher score → more likely to sort early; hash breaks ties stably.
            $indexed[] = [
                'item' => $item,
                'key' => sprintf('%015.4f|%s', $weight * 1000 + (hexdec(substr($hash, 0, 6)) % 1000), $hash),
            ];
        }

        usort($indexed, fn (array $a, array $b) => strcmp($b['key'], $a['key']));

        $picked = [];
        $seenTypes = [];
        foreach ($indexed as $row) {
            $item = $row['item'];
            $text = mb_strtolower((string) ($item['text'] ?? ''));
            // Light diversity: avoid too many near-identical prefixes
            $prefix = mb_substr($text, 0, 12);
            if (isset($seenTypes[$prefix]) && count($picked) >= 2) {
                continue;
            }
            $seenTypes[$prefix] = true;
            $picked[] = $item;
            if (count($picked) >= $limit) {
                break;
            }
        }

        // If diversity skipped too many, fill from remaining
        if (count($picked) < $limit) {
            foreach ($indexed as $row) {
                $item = $row['item'];
                $already = false;
                foreach ($picked as $p) {
                    if (($p['text'] ?? '') === ($item['text'] ?? '')) {
                        $already = true;
                        break;
                    }
                }
                if ($already) {
                    continue;
                }
                $picked[] = $item;
                if (count($picked) >= $limit) {
                    break;
                }
            }
        }

        return $picked;
    }

    /**
     * @param  list<array{text: string, type: string, score: float}>  $items
     * @return list<array{text: string, type: string, score: float}>
     */
    private function mergeUniqueByText(array $items): array
    {
        $seen = [];
        $out = [];
        foreach ($items as $item) {
            $key = mb_strtolower(trim((string) ($item['text'] ?? '')));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $item;
        }

        usort($out, fn (array $a, array $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{text: string, type: string, score: float, filters?: array<string, mixed>, label?: string, query?: string}
     */
    private function item(
        string $text,
        string $type,
        float $score,
        array $filters = [],
        ?string $label = null,
        ?string $query = null,
    ): array {
        $row = [
            'text' => $text,
            'type' => $type,
            'score' => $score,
        ];
        if ($filters !== []) {
            $row['filters'] = $filters;
        }
        if ($label !== null) {
            $row['label'] = $label;
        }
        if ($query !== null) {
            $row['query'] = $query;
        }

        return $row;
    }

    private function localizeFuelLabel(string $fuelName, string $locale): string
    {
        $lower = mb_strtolower($fuelName);
        $mapEn = [
            'el' => 'Electric',
            'electric' => 'Electric',
            'elektrisk' => 'Electric',
            'diesel' => 'Diesel',
            'benzin' => 'Petrol',
            'petrol' => 'Petrol',
            'hybrid' => 'Hybrid',
            'plugin' => 'Plug-in hybrid',
            'plug-in' => 'Plug-in hybrid',
            'ladbar' => 'Plug-in hybrid',
        ];
        $mapDa = [
            'el' => 'Elbil',
            'electric' => 'Elbil',
            'elektrisk' => 'Elbil',
            'diesel' => 'Diesel',
            'benzin' => 'Benzin',
            'petrol' => 'Benzin',
            'hybrid' => 'Hybrid',
            'plugin' => 'Plugin-hybrid',
            'plug-in' => 'Plugin-hybrid',
            'ladbar' => 'Plugin-hybrid',
        ];
        $map = $locale === 'en' ? $mapEn : $mapDa;
        foreach ($map as $needle => $label) {
            if (Str::contains($lower, $needle)) {
                return $label;
            }
        }

        return $fuelName;
    }

    private function formatPriceDa(int $price): string
    {
        return number_format($price, 0, ',', '.').' kr';
    }

    private function formatPriceEn(int $price): string
    {
        return number_format($price, 0, '.', ',').' DKK';
    }

    private function roundBudget(int $price): int
    {
        $price = max(50_000, min(500_000, $price));

        return (int) (round($price / 10_000) * 10_000);
    }
}
