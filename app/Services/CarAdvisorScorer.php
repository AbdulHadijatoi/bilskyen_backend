<?php

namespace App\Services;

use App\Models\Vehicle;

class CarAdvisorScorer
{
    /**
     * Score a published vehicle against a lifestyle profile (0–100).
     *
     * @param  array{
     *     budget_max?: int|null,
     *     use_case?: string|null,
     *     needs?: list<string>,
     *     priorities?: list<string>
     * }  $profile
     * @param  array<string, mixed>|null  $market  From MarketPricingService::evaluateVehicle
     * @return array{
     *     score: int,
     *     match_reasons: list<string>,
     *     tradeoffs: list<string>,
     *     components: array<string, int>
     * }
     */
    public function score(Vehicle $vehicle, array $profile, ?array $market = null): array
    {
        $needs = array_values(array_filter(array_map(
            fn ($n) => is_string($n) ? mb_strtolower(trim($n)) : '',
            $profile['needs'] ?? []
        )));
        $useCase = mb_strtolower((string) ($profile['use_case'] ?? 'mixed'));
        $budgetMax = isset($profile['budget_max']) && is_numeric($profile['budget_max'])
            ? (int) $profile['budget_max']
            : null;
        $priorities = array_values(array_filter(array_map(
            fn ($p) => is_string($p) ? mb_strtolower(trim($p)) : '',
            $profile['priorities'] ?? []
        )));

        $weights = $this->weightsFor($needs, $useCase, $priorities);

        $components = [
            'budget' => $this->scoreBudget($vehicle, $budgetMax),
            'space' => $this->scoreSpace($vehicle, $needs, $useCase),
            'city' => $this->scoreCityFitness($vehicle, $useCase, $needs),
            'ownership' => $this->scoreOwnership($vehicle, $needs),
            'reliability' => $this->scoreReliabilityProxy($vehicle, $needs),
            'style' => $this->scoreStyle($vehicle, $needs),
            'market' => $this->scoreMarket($market),
        ];

        $weightedSum = 0.0;
        $weightTotal = 0.0;
        foreach ($components as $key => $value) {
            $w = $weights[$key] ?? 0.0;
            if ($w <= 0) {
                continue;
            }
            $weightedSum += $value * $w;
            $weightTotal += $w;
        }

        $score = $weightTotal > 0 ? (int) round($weightedSum / $weightTotal) : 50;
        $score = max(0, min(100, $score));

        [$reasons, $tradeoffs] = $this->buildReasons($vehicle, $profile, $components, $market, $budgetMax);

        return [
            'score' => $score,
            'match_reasons' => $reasons,
            'tradeoffs' => $tradeoffs,
            'components' => $components,
        ];
    }

    /**
     * @param  list<string>  $needs
     * @param  list<string>  $priorities
     * @return array<string, float>
     */
    private function weightsFor(array $needs, string $useCase, array $priorities): array
    {
        $weights = [
            'budget' => 1.4,
            'space' => 1.0,
            'city' => 1.0,
            'ownership' => 1.0,
            'reliability' => 0.9,
            'style' => 0.7,
            'market' => 0.8,
        ];

        if (in_array('stroller', $needs, true)
            || in_array('space', $needs, true)
            || in_array('family', $needs, true)
            || $useCase === 'family') {
            $weights['space'] += 0.6;
        }

        if ($useCase === 'city' || in_array('city', $needs, true)) {
            $weights['city'] += 0.5;
        }

        if (in_array('low_repair_risk', $needs, true) || in_array('reliable', $needs, true)) {
            $weights['reliability'] += 0.5;
        }

        if (in_array('low_tax', $needs, true) || in_array('low_ownership_cost', $needs, true)) {
            $weights['ownership'] += 0.5;
        }

        if (in_array('sporty', $needs, true) || in_array('sporty_look', $needs, true)) {
            $weights['style'] += 0.5;
        }

        foreach ($priorities as $index => $priority) {
            $boost = max(0.15, 0.45 - ($index * 0.08));
            if (str_contains($priority, 'budget') || str_contains($priority, 'price')) {
                $weights['budget'] += $boost;
            } elseif (str_contains($priority, 'space') || str_contains($priority, 'family') || str_contains($priority, 'stroller')) {
                $weights['space'] += $boost;
            } elseif (str_contains($priority, 'city') || str_contains($priority, 'commute')) {
                $weights['city'] += $boost;
            } elseif (str_contains($priority, 'tax') || str_contains($priority, 'ownership') || str_contains($priority, 'cost')) {
                $weights['ownership'] += $boost;
            } elseif (str_contains($priority, 'repair') || str_contains($priority, 'reliab')) {
                $weights['reliability'] += $boost;
            } elseif (str_contains($priority, 'sport') || str_contains($priority, 'style')) {
                $weights['style'] += $boost;
            } elseif (str_contains($priority, 'market') || str_contains($priority, 'value')) {
                $weights['market'] += $boost;
            }
        }

        return $weights;
    }

    private function scoreBudget(Vehicle $vehicle, ?int $budgetMax): int
    {
        $price = $vehicle->price !== null ? (float) $vehicle->price : null;
        if ($budgetMax === null || $budgetMax <= 0) {
            return 70;
        }
        if ($price === null || $price <= 0) {
            return 40;
        }
        if ($price <= $budgetMax) {
            $ratio = $price / $budgetMax;
            // Prefer using most of the budget without going over
            if ($ratio >= 0.55) {
                return 100;
            }
            if ($ratio >= 0.35) {
                return 88;
            }

            return 72;
        }

        $over = ($price - $budgetMax) / $budgetMax;
        if ($over <= 0.05) {
            return 78;
        }
        if ($over <= 0.12) {
            return 58;
        }
        if ($over <= 0.25) {
            return 35;
        }

        return 10;
    }

    /**
     * @param  list<string>  $needs
     */
    private function scoreSpace(Vehicle $vehicle, array $needs, string $useCase): int
    {
        $wantsSpace = in_array('stroller', $needs, true)
            || in_array('space', $needs, true)
            || in_array('family', $needs, true)
            || $useCase === 'family';

        if (! $wantsSpace) {
            return 70;
        }

        $seats = max((int) ($vehicle->seats_max ?? 0), (int) ($vehicle->seats_min ?? 0));
        $body = mb_strtolower((string) ($vehicle->bodyType?->name ?? ''));
        $score = 45;

        if ($seats >= 7) {
            $score += 35;
        } elseif ($seats >= 5) {
            $score += 25;
        } elseif ($seats >= 4) {
            $score += 10;
        }

        if (str_contains($body, 'estate') || str_contains($body, 'station') || str_contains($body, 'mpv')
            || str_contains($body, 'suv') || str_contains($body, 'crossover') || str_contains($body, 'van')) {
            $score += 25;
        } elseif (str_contains($body, 'hatch')) {
            $score += 8;
        } elseif (str_contains($body, 'coupe') || str_contains($body, 'cabrio') || str_contains($body, 'convertible')) {
            $score -= 20;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  list<string>  $needs
     */
    private function scoreCityFitness(Vehicle $vehicle, string $useCase, array $needs): int
    {
        $cityFocus = $useCase === 'city' || in_array('city', $needs, true);
        if (! $cityFocus) {
            return 70;
        }

        $score = 55;
        $fuel = mb_strtolower((string) ($vehicle->fuelType?->name ?? ''));
        $body = mb_strtolower((string) ($vehicle->bodyType?->name ?? ''));
        $hp = (int) ($vehicle->engine_power_hp ?? 0);

        if (str_contains($fuel, 'electric') || str_contains($fuel, 'el') || str_contains($fuel, 'hybrid')) {
            $score += 25;
        } elseif (str_contains($fuel, 'petrol') || str_contains($fuel, 'benzin')) {
            $score += 10;
        } elseif (str_contains($fuel, 'diesel')) {
            $score -= 5;
        }

        if (str_contains($body, 'hatch') || str_contains($body, 'mini') || str_contains($body, 'city')) {
            $score += 15;
        } elseif (str_contains($body, 'suv') || str_contains($body, 'van') || str_contains($body, 'pickup')) {
            $score -= 8;
        }

        if ($vehicle->km_per_liter !== null && (float) $vehicle->km_per_liter >= 18) {
            $score += 10;
        }
        if ($vehicle->range_km !== null && (int) $vehicle->range_km >= 250) {
            $score += 8;
        }
        if ($hp > 250) {
            $score -= 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  list<string>  $needs
     */
    private function scoreOwnership(Vehicle $vehicle, array $needs): int
    {
        $cares = in_array('low_tax', $needs, true)
            || in_array('low_ownership_cost', $needs, true)
            || in_array('cheap_ownership', $needs, true);

        $tax = $vehicle->calculated_ownership_tax ?? $vehicle->annual_tax;
        if ($tax === null) {
            return $cares ? 50 : 65;
        }

        $tax = (float) $tax;
        if ($tax <= 2000) {
            $base = 100;
        } elseif ($tax <= 4000) {
            $base = 85;
        } elseif ($tax <= 7000) {
            $base = 65;
        } elseif ($tax <= 12000) {
            $base = 40;
        } else {
            $base = 20;
        }

        return $cares ? $base : (int) round(($base + 70) / 2);
    }

    /**
     * Age + km proxy only — no invented fault lists.
     *
     * @param  list<string>  $needs
     */
    private function scoreReliabilityProxy(Vehicle $vehicle, array $needs): int
    {
        $cares = in_array('low_repair_risk', $needs, true) || in_array('reliable', $needs, true);
        $year = (int) ($vehicle->model_year ?? $vehicle->first_registration_year ?? 0);
        $km = $vehicle->km_driven !== null ? (int) $vehicle->km_driven : null;
        $currentYear = (int) date('Y');

        $score = 60;
        if ($year > 0) {
            $age = max(0, $currentYear - $year);
            if ($age <= 3) {
                $score = 95;
            } elseif ($age <= 6) {
                $score = 82;
            } elseif ($age <= 10) {
                $score = 68;
            } elseif ($age <= 14) {
                $score = 50;
            } else {
                $score = 32;
            }
        }

        if ($km !== null) {
            if ($km <= 40000) {
                $score += 8;
            } elseif ($km <= 100000) {
                $score += 2;
            } elseif ($km <= 180000) {
                $score -= 8;
            } else {
                $score -= 18;
            }
        }

        $score = max(0, min(100, $score));

        return $cares ? $score : (int) round(($score + 70) / 2);
    }

    /**
     * @param  list<string>  $needs
     */
    private function scoreStyle(Vehicle $vehicle, array $needs): int
    {
        $wantsSporty = in_array('sporty', $needs, true) || in_array('sporty_look', $needs, true);
        if (! $wantsSporty) {
            return 70;
        }

        $score = 40;
        $body = mb_strtolower((string) ($vehicle->bodyType?->name ?? ''));
        $title = mb_strtolower((string) ($vehicle->title ?? ''));
        $hp = (int) ($vehicle->engine_power_hp ?? 0);

        if (str_contains($body, 'coupe') || str_contains($body, 'cabrio') || str_contains($body, 'sport')) {
            $score += 35;
        } elseif (str_contains($body, 'hatch') || str_contains($body, 'sedan') || str_contains($body, 'saloon')) {
            $score += 18;
        } elseif (str_contains($body, 'suv') || str_contains($body, 'crossover')) {
            $score += 12;
        }

        foreach (['gti', 'gt', 'rs', 'amg', 'm sport', 'st', 'r-line', 's-line', 'fr'] as $token) {
            if (str_contains($title, $token)) {
                $score += 20;
                break;
            }
        }

        if ($hp >= 180) {
            $score += 20;
        } elseif ($hp >= 140) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * @param  array<string, mixed>|null  $market
     */
    private function scoreMarket(?array $market): int
    {
        if ($market === null) {
            return 60;
        }

        return match ($market['label'] ?? '') {
            'below_market' => 95,
            'fair_price' => 80,
            'above_market' => 45,
            default => 60,
        };
    }

    /**
     * @param  array{
     *     budget_max?: int|null,
     *     use_case?: string|null,
     *     needs?: list<string>,
     *     priorities?: list<string>
     * }  $profile
     * @param  array<string, int>  $components
     * @param  array<string, mixed>|null  $market
     * @return array{0: list<string>, 1: list<string>}
     */
    private function buildReasons(
        Vehicle $vehicle,
        array $profile,
        array $components,
        ?array $market,
        ?int $budgetMax,
    ): array {
        $reasons = [];
        $tradeoffs = [];
        $localeHint = app()->getLocale() === 'en' ? 'en' : 'da';

        if (($components['budget'] ?? 0) >= 72 && $budgetMax) {
            $reasons[] = $localeHint === 'en'
                ? 'Fits within your budget'
                : 'Passer inden for dit budget';
        } elseif ($budgetMax && $vehicle->price !== null && (float) $vehicle->price > $budgetMax) {
            $tradeoffs[] = $localeHint === 'en'
                ? 'Priced above your stated budget'
                : 'Prisen ligger over dit angivne budget';
        }

        if (($components['space'] ?? 0) >= 70) {
            $reasons[] = $localeHint === 'en'
                ? 'Good space for family / stroller needs'
                : 'God plads til familie / barnevogn';
        } elseif (($components['space'] ?? 0) < 45 && $this->needsSpace($profile)) {
            $tradeoffs[] = $localeHint === 'en'
                ? 'Limited cabin / cargo space for your needs'
                : 'Begrænset kabine-/bagagerumsplads til dine behov';
        }

        if (($components['city'] ?? 0) >= 70 && ($profile['use_case'] ?? '') === 'city') {
            $reasons[] = $localeHint === 'en'
                ? 'Well suited to city driving'
                : 'Velegnet til bykørsel';
        }

        $tax = $vehicle->calculated_ownership_tax ?? $vehicle->annual_tax;
        if ($tax !== null && ($components['ownership'] ?? 0) >= 75) {
            $reasons[] = $localeHint === 'en'
                ? 'Relatively low ownership tax'
                : 'Forholdsvis lav ejerafgift';
        } elseif ($tax !== null && ($components['ownership'] ?? 0) < 40) {
            $tradeoffs[] = $localeHint === 'en'
                ? 'Higher annual ownership tax'
                : 'Højere årlig ejerafgift';
        }

        if (($components['reliability'] ?? 0) >= 75) {
            $reasons[] = $localeHint === 'en'
                ? 'Younger / lower-mileage profile (repair-risk proxy)'
                : 'Yngre / lavere kilometerstand (proxy for reparationsrisiko)';
        } elseif (($components['reliability'] ?? 0) < 45) {
            $tradeoffs[] = $localeHint === 'en'
                ? 'Older or high-mileage listing (higher repair-risk proxy)'
                : 'Ældre eller høj kilometerstand (højere reparationsrisiko-proxy)';
        }

        if (($components['style'] ?? 0) >= 70 && $this->needsSporty($profile)) {
            $reasons[] = $localeHint === 'en'
                ? 'Sportier look / power profile'
                : 'Sportligere look / effektprofil';
        }

        if ($market !== null) {
            $label = $market['label'] ?? '';
            if ($label === 'below_market') {
                $reasons[] = $localeHint === 'en'
                    ? 'Asking price below similar market cohort'
                    : 'Udbudspris under lignende markedskohorte';
            } elseif ($label === 'fair_price') {
                $reasons[] = $localeHint === 'en'
                    ? 'Fair price vs similar listings'
                    : 'Fair pris ift. lignende annoncer';
            } elseif ($label === 'above_market') {
                $tradeoffs[] = $localeHint === 'en'
                    ? 'Asking price above similar market cohort'
                    : 'Udbudspris over lignende markedskohorte';
            }
        }

        return [
            array_values(array_unique(array_slice($reasons, 0, 5))),
            array_values(array_unique(array_slice($tradeoffs, 0, 4))),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function needsSpace(array $profile): bool
    {
        $needs = array_map(fn ($n) => is_string($n) ? mb_strtolower($n) : '', $profile['needs'] ?? []);

        return in_array('stroller', $needs, true)
            || in_array('space', $needs, true)
            || in_array('family', $needs, true)
            || ($profile['use_case'] ?? '') === 'family';
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function needsSporty(array $profile): bool
    {
        $needs = array_map(fn ($n) => is_string($n) ? mb_strtolower($n) : '', $profile['needs'] ?? []);

        return in_array('sporty', $needs, true) || in_array('sporty_look', $needs, true);
    }
}
