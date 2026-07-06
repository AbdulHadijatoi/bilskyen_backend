<?php

namespace App\Services;

use App\Models\Plan;
use App\Support\FeatureDisplay;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class PublicPlansService
{
    private const PLAN_SORT_ORDER = [
        'trial' => 0,
        'basic' => 1,
        'professional' => 2,
        'premium' => 3,
        'basic-payg' => 4,
        'professional-payg' => 5,
        'premium-payg' => 6,
        'enterprise' => 99,
    ];

    private const POPULAR_SLUG = 'professional';

    private const HIGHLIGHT_LIMIT = 8;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPublicPlans(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $dealerRole = Role::where('name', 'dealer')->first();

        $query = Plan::query()
            ->with([
                'features.featureValueType',
                'priceHistory' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>', now());
                    })->orderByDesc('starts_at');
                },
            ])
            ->where('is_active', true);

        if ($dealerRole) {
            $query->whereHas('availability', function ($q) use ($dealerRole) {
                $q->where('allowed_role_id', $dealerRole->id)
                    ->where('is_enabled', true);
            });
        }

        return $query->get()
            ->sortBy(fn (Plan $plan) => self::PLAN_SORT_ORDER[$plan->slug] ?? 50)
            ->values()
            ->map(fn (Plan $plan) => $this->formatPlan($plan, $locale))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatPlan(Plan $plan, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $pricing = $this->resolvePricing($plan);
        $monthly = $pricing['monthly'] ?? null;
        $yearly = $pricing['yearly'] ?? null;

        $yearlySavingsPercent = null;
        if ($monthly && $yearly && $monthly['price'] > 0) {
            $annualFromMonthly = $monthly['price'] * 12;
            if ($annualFromMonthly > $yearly['price']) {
                $yearlySavingsPercent = (int) round((($annualFromMonthly - $yearly['price']) / $annualFromMonthly) * 100);
            }
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'billing_model' => $plan->billing_model ?? 'subscription',
            'trial_days' => $plan->trial_days,
            'is_popular' => $plan->slug === self::POPULAR_SLUG,
            'is_free' => $this->isFreePlan($plan, $pricing),
            'is_enterprise' => $plan->slug === 'enterprise',
            'is_usage_plan' => ($plan->billing_model ?? 'subscription') === 'usage_daily',
            'price_per_listing_per_day' => $plan->price_per_listing_per_day,
            'pricing' => $pricing,
            'yearly_savings_percent' => $yearlySavingsPercent,
            'feature_highlights' => $this->buildFeatureHighlights($plan, $locale),
        ];
    }

    /**
     * @return array<string, array<string, mixed>|null>
     */
    private function resolvePricing(Plan $plan): array
    {
        if (($plan->billing_model ?? 'subscription') === 'usage_daily') {
            return [
                'monthly' => null,
                'yearly' => null,
            ];
        }

        $active = $plan->priceHistory
            ->filter(fn ($row) => ! $row->ends_at || $row->ends_at->isFuture())
            ->values();

        $monthly = $active->firstWhere('billing_cycle', 'monthly');
        $yearly = $active->firstWhere('billing_cycle', 'yearly');

        return [
            'monthly' => $monthly ? $this->formatPriceRow($monthly) : null,
            'yearly' => $yearly ? $this->formatPriceRow($yearly) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPriceRow($row): array
    {
        return [
            'price' => (int) $row->price,
            'currency' => $row->currency,
            'billing_cycle' => $row->billing_cycle,
            'formatted' => $this->formatCents((int) $row->price, (string) $row->currency),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>|null>  $pricing
     */
    private function isFreePlan(Plan $plan, array $pricing): bool
    {
        if (($plan->billing_model ?? 'subscription') === 'usage_daily') {
            return false;
        }

        $monthly = $pricing['monthly']['price'] ?? null;
        $yearly = $pricing['yearly']['price'] ?? null;

        return ($monthly === 0 || $monthly === null)
            && ($yearly === 0 || $yearly === null);
    }

    /**
     * @return array<int, string>
     */
    private function buildFeatureHighlights(Plan $plan, string $locale): array
    {
        $highlights = [];

        foreach ($plan->features as $feature) {
            $value = $feature->pivot->value ?? null;
            $label = FeatureDisplay::formatFeatureValue($feature, $value, $locale);

            if ($label !== null) {
                $highlights[] = $label;
            }

            if (count($highlights) >= self::HIGHLIGHT_LIMIT) {
                break;
            }
        }

        return $highlights;
    }

    public function formatCents(int $cents, string $currency = 'DKK'): string
    {
        $amount = number_format($cents / 100, 2, '.', '');

        return $amount . ' ' . $currency;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public function getDefaultFaqItems(): array
    {
        $items = [];
        $keys = range(1, 8);

        foreach ($keys as $index) {
            $question = __("messages.dealer_marketing.faq.q{$index}");
            $answer = __("messages.dealer_marketing.faq.a{$index}");

            if ($question === "messages.dealer_marketing.faq.q{$index}") {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public function parseFaqJson(?string $json): array
    {
        if (! $json) {
            return [];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($item) => is_array($item) && ! empty(trim((string) ($item['question'] ?? ''))))
            ->map(fn (array $item) => [
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => trim((string) ($item['answer'] ?? '')),
            ])
            ->values()
            ->all();
    }

    public function maxYearlySavingsPercent(Collection|array $plans): ?int
    {
        $values = collect($plans)
            ->pluck('yearly_savings_percent')
            ->filter(fn ($value) => is_int($value) && $value > 0);

        return $values->isEmpty() ? null : $values->max();
    }
}
