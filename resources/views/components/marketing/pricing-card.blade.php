@props([
    'plan',
    'panelUrl',
    'billingCycle' => 'monthly',
])

@php
    $isPopular = $plan['is_popular'] ?? false;
    $isUsage = $plan['is_usage_plan'] ?? false;
    $isFree = $plan['is_free'] ?? false;
    $isEnterprise = $plan['is_enterprise'] ?? false;
    $monthly = $plan['pricing']['monthly'] ?? null;
    $yearly = $plan['pricing']['yearly'] ?? null;

    if ($isUsage) {
        $displayPrice = isset($plan['price_per_listing_per_day'])
            ? number_format(($plan['price_per_listing_per_day'] ?? 0) / 100, 2, '.', '') . ' DKK'
            : null;
        $priceSuffix = __('messages.dealer_marketing.pricing.per_listing_per_day');
        $secondaryPrice = null;
    } elseif ($billingCycle === 'yearly' && $yearly) {
        $displayPrice = $yearly['formatted'] ?? null;
        $priceSuffix = __('messages.dealer_marketing.pricing.per_year');
        $monthlyEquivalent = $monthly ? number_format($monthly['price'] / 100, 2, '.', '') . ' DKK' : null;
        $secondaryPrice = $monthlyEquivalent
            ? __('messages.dealer_marketing.pricing.or_per_month', ['price' => $monthlyEquivalent])
            : null;
        $strikethrough = $monthly && $monthly['price'] > 0
            ? number_format($monthly['price'] / 100, 2, '.', '') . ' DKK'
            : null;
    } elseif ($monthly) {
        $displayPrice = $monthly['formatted'] ?? null;
        $priceSuffix = __('messages.dealer_marketing.pricing.per_month');
        $yearlyEquivalent = $yearly ? number_format($yearly['price'] / 100, 2, '.', '') . ' DKK' : null;
        $secondaryPrice = $yearlyEquivalent
            ? __('messages.dealer_marketing.pricing.or_per_year', ['price' => $yearlyEquivalent])
            : null;
        $strikethrough = null;
    } elseif ($yearly) {
        $displayPrice = $yearly['formatted'] ?? null;
        $priceSuffix = __('messages.dealer_marketing.pricing.per_year');
        $secondaryPrice = null;
        $strikethrough = null;
    } else {
        $displayPrice = $isFree ? __('messages.dealer_marketing.pricing.free') : null;
        $priceSuffix = $isFree ? '' : '';
        $secondaryPrice = null;
        $strikethrough = null;
    }

    if ($isEnterprise) {
        $ctaUrl = route('for-dealers.contact', ['subject' => 'enterprise']);
        $ctaLabel = __('messages.dealer_marketing.pricing.contact_sales');
    } elseif ($isFree) {
        $ctaUrl = $panelUrl . '/auth/register?plan=' . $plan['id'];
        $ctaLabel = __('messages.dealer_marketing.pricing.sign_up');
    } else {
        $ctaUrl = $panelUrl . '/auth/register?plan=' . $plan['id'];
        $ctaLabel = __('messages.dealer_marketing.pricing.get_started');
    }
@endphp

<div @class([
    'pricing-card relative flex h-full flex-col rounded-xl border bg-card p-6 shadow-sm transition-shadow hover:shadow-md',
    'border-primary ring-2 ring-primary/20' => $isPopular,
    'border-border' => ! $isPopular,
])
    data-plan-id="{{ $plan['id'] }}"
    @if(!$isUsage && $monthly)
        data-monthly-price="{{ $monthly['price'] }}"
        data-yearly-price="{{ $yearly['price'] ?? '' }}"
        data-currency="{{ $monthly['currency'] ?? 'DKK' }}"
    @endif
>
    @if($isPopular)
        <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground">
            {{ __('messages.dealer_marketing.pricing.most_popular') }}
        </div>
    @endif

    <div class="mb-6">
        <h3 class="text-xl font-bold tracking-tight">{{ $plan['name'] }}</h3>
        @if(!empty($plan['description']))
            <p class="mt-1 text-sm text-muted-foreground">{{ $plan['description'] }}</p>
        @endif
    </div>

    <div class="mb-6 min-h-[4.5rem]" data-price-block>
        @if($displayPrice)
            <div class="flex flex-wrap items-end gap-2">
                @if(!empty($strikethrough) && $billingCycle === 'yearly')
                    <span class="text-lg text-muted-foreground line-through">{{ $strikethrough }}</span>
                @endif
                <span class="text-4xl font-bold tracking-tight">{{ $displayPrice }}</span>
                @if($priceSuffix)
                    <span class="pb-1 text-sm text-muted-foreground">{{ $priceSuffix }}</span>
                @endif
            </div>
            @if($billingCycle === 'yearly' && !$isUsage)
                <p class="mt-1 text-xs text-muted-foreground">{{ __('messages.dealer_marketing.pricing.billed_annually') }}</p>
            @endif
            @if($secondaryPrice)
                <p class="mt-1 text-sm text-muted-foreground">{{ $secondaryPrice }}</p>
            @endif
        @else
            <span class="text-4xl font-bold tracking-tight">{{ __('messages.dealer_marketing.pricing.custom') }}</span>
        @endif

        @if(!empty($plan['trial_days']) && (int) $plan['trial_days'] > 0)
            <span class="mt-2 inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                {{ __('messages.dealer_marketing.pricing.trial_days', ['count' => $plan['trial_days']]) }}
            </span>
        @endif
    </div>

    @if(!empty($plan['feature_highlights']))
        <ul class="mb-8 flex-1 space-y-3">
            @foreach($plan['feature_highlights'] as $feature)
                <li class="flex items-start gap-2 text-sm">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-primary" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    <span>{{ $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <a href="{{ $ctaUrl }}"
       class="inline-flex h-11 w-full items-center justify-center rounded-md bg-primary text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
        {{ $ctaLabel }}
    </a>
</div>
