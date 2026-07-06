@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.dealer_marketing.pricing.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="py-16 md:py-20 text-center border-b border-border">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $content['pricing_header_title'] ?? __('messages.dealer_marketing.pricing.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $content['pricing_header_description'] ?? __('messages.dealer_marketing.pricing.subtitle') }}
            </p>

            @if(count($subscriptionPlans) > 0 && ($maxYearlySavingsPercent ?? 0) > 0)
                <div class="mt-8 flex flex-col items-center gap-3">
                    <div class="inline-flex items-center rounded-full border border-border bg-muted p-1" id="billing-toggle">
                        <button type="button" data-cycle="monthly" class="billing-toggle-btn rounded-full px-5 py-2 text-sm font-medium transition-colors bg-background shadow-sm">
                            {{ __('messages.dealer_marketing.pricing.monthly') }}
                        </button>
                        <button type="button" data-cycle="yearly" class="billing-toggle-btn rounded-full px-5 py-2 text-sm font-medium transition-colors text-muted-foreground">
                            {{ __('messages.dealer_marketing.pricing.yearly') }}
                        </button>
                    </div>
                    @if($maxYearlySavingsPercent)
                        <span class="text-xs font-medium text-primary" id="savings-badge">
                            {{ __('messages.dealer_marketing.pricing.save_up_to', ['percent' => $maxYearlySavingsPercent]) }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="py-12 md:py-16">
        <div class="container mx-auto px-4 md:px-6">
            @if(count($subscriptionPlans) > 0)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($subscriptionPlans as $plan)
                        <x-marketing.pricing-card :plan="$plan" :panel-url="$panelUrl" billing-cycle="monthly" />
                    @endforeach

                    @if($showEnterpriseCard)
                        @php
                            $enterprisePlan = [
                                'id' => 0,
                                'name' => $content['enterprise_card_title'] ?? __('messages.dealer_marketing.pricing.enterprise_title'),
                                'description' => $content['enterprise_card_description'] ?? __('messages.dealer_marketing.pricing.enterprise_subtitle'),
                                'is_enterprise' => true,
                                'is_popular' => false,
                                'is_usage_plan' => false,
                                'is_free' => false,
                                'feature_highlights' => array_filter([
                                    __('messages.dealer_marketing.pricing.enterprise_feature_1'),
                                    __('messages.dealer_marketing.pricing.enterprise_feature_2'),
                                    __('messages.dealer_marketing.pricing.enterprise_feature_3'),
                                    __('messages.dealer_marketing.pricing.enterprise_feature_4'),
                                ]),
                                'pricing' => ['monthly' => null, 'yearly' => null],
                            ];
                        @endphp
                        <x-marketing.pricing-card :plan="$enterprisePlan" :panel-url="$panelUrl" />
                    @endif
                </div>
            @endif

            @if(count($paygPlans) > 0)
                <div class="mt-16">
                    <h2 class="text-2xl font-bold tracking-tight text-center mb-8">
                        {{ __('messages.dealer_marketing.pricing.payg_title') }}
                    </h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($paygPlans as $plan)
                            <x-marketing.pricing-card :plan="$plan" :panel-url="$panelUrl" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    <x-marketing.pricing-faq
        :items="$faqItems"
        :title="$content['pricing_faq_title'] ?? null"
    />

    <section class="py-8 border-t border-border">
        <div class="container mx-auto px-4 md:px-6">
            <p class="text-center text-xs text-muted-foreground max-w-3xl mx-auto">
                {{ $content['pricing_footnote'] ?? __('messages.dealer_marketing.pricing.footnote') }}
            </p>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const labels = {
        perMonth: @json(__('messages.dealer_marketing.pricing.per_month')),
        perYear: @json(__('messages.dealer_marketing.pricing.per_year')),
        billedAnnually: @json(__('messages.dealer_marketing.pricing.billed_annually')),
        orPerMonth: @json(__('messages.dealer_marketing.pricing.or_per_month', ['price' => '__PRICE__'])),
        orPerYear: @json(__('messages.dealer_marketing.pricing.or_per_year', ['price' => '__PRICE__'])),
    };

    const toggle = document.getElementById('billing-toggle');
    if (!toggle) return;

    function formatCents(cents, currency) {
        return (cents / 100).toFixed(2) + ' ' + currency;
    }

    function updateCards(cycle) {
        document.querySelectorAll('.billing-toggle-btn').forEach(function (btn) {
            const active = btn.dataset.cycle === cycle;
            btn.classList.toggle('bg-background', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-muted-foreground', !active);
        });

        document.querySelectorAll('.pricing-card[data-plan-id]').forEach(function (card) {
            const monthlyRaw = card.dataset.monthlyPrice;
            const yearlyRaw = card.dataset.yearlyPrice;
            const currency = card.dataset.currency || 'DKK';
            const block = card.querySelector('[data-price-block]');
            if (!block || !monthlyRaw) return;

            const monthly = parseInt(monthlyRaw, 10);
            const yearly = yearlyRaw ? parseInt(yearlyRaw, 10) : null;
            let html = '';

            if (cycle === 'yearly' && yearly) {
                html = '<div class="flex flex-wrap items-end gap-2">';
                if (monthly > 0) {
                    html += '<span class="text-lg text-muted-foreground line-through">' + formatCents(monthly, currency) + '</span>';
                }
                html += '<span class="text-4xl font-bold tracking-tight">' + formatCents(yearly, currency) + '</span>';
                html += '<span class="pb-1 text-sm text-muted-foreground">' + labels.perYear + '</span></div>';
                html += '<p class="mt-1 text-xs text-muted-foreground">' + labels.billedAnnually + '</p>';
                if (monthly > 0) {
                    html += '<p class="mt-1 text-sm text-muted-foreground">' + labels.orPerMonth.replace('__PRICE__', formatCents(monthly, currency)) + '</p>';
                }
            } else {
                html = '<div class="flex flex-wrap items-end gap-2"><span class="text-4xl font-bold tracking-tight">' + formatCents(monthly, currency) + '</span>';
                html += '<span class="pb-1 text-sm text-muted-foreground">' + labels.perMonth + '</span></div>';
                if (yearly) {
                    html += '<p class="mt-1 text-sm text-muted-foreground">' + labels.orPerYear.replace('__PRICE__', formatCents(yearly, currency)) + '</p>';
                }
            }

            block.innerHTML = html;
        });
    }

    toggle.querySelectorAll('.billing-toggle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            updateCards(btn.dataset.cycle);
        });
    });
})();
</script>
@endpush
