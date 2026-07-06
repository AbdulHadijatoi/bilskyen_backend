@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.dealer_marketing.landing.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="py-20 md:py-28 text-center bg-muted/40">
        <div class="container mx-auto px-4 md:px-6 max-w-4xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary mb-4">
                {{ $content['landing_eyebrow'] ?? __('messages.dealer_marketing.landing.eyebrow') }}
            </p>
            <h1 class="text-4xl font-bold tracking-tight md:text-6xl">
                {{ $content['landing_hero_title'] ?? __('messages.dealer_marketing.landing.hero_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-6 max-w-2xl text-lg md:text-xl">
                {{ $content['landing_hero_description'] ?? __('messages.dealer_marketing.landing.hero_description') }}
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('for-dealers.pricing') }}" class="inline-flex h-12 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                    {{ __('messages.dealer_marketing.landing.view_pricing') }}
                </a>
                <a href="{{ $panelUrl }}/auth/register" class="inline-flex h-12 items-center justify-center rounded-md border border-border bg-background px-8 text-sm font-medium hover:bg-muted">
                    {{ __('messages.dealer_marketing.nav.create_account') }}
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight">{{ $content['landing_features_title'] ?? __('messages.dealer_marketing.landing.features_title') }}</h2>
                <p class="text-muted-foreground mt-3 max-w-2xl mx-auto">{{ $content['landing_features_description'] ?? __('messages.dealer_marketing.landing.features_description') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                @foreach(range(1, 4) as $i)
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </div>
                        <h3 class="text-lg font-semibold">{{ $content["landing_feature_{$i}_title"] ?? __("messages.dealer_marketing.landing.feature_{$i}_title") }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ $content["landing_feature_{$i}_description"] ?? __("messages.dealer_marketing.landing.feature_{$i}_description") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 bg-primary text-primary-foreground">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight">{{ $content['landing_cta_title'] ?? __('messages.dealer_marketing.landing.cta_title') }}</h2>
            <p class="mt-4 max-w-xl mx-auto opacity-90">{{ $content['landing_cta_description'] ?? __('messages.dealer_marketing.landing.cta_description') }}</p>
            <a href="{{ route('for-dealers.pricing') }}" class="mt-8 inline-flex h-11 items-center justify-center rounded-md bg-background px-8 text-sm font-medium text-foreground hover:bg-background/90">
                {{ __('messages.dealer_marketing.landing.explore_plans') }}
            </a>
        </div>
    </section>
</div>
@endsection
