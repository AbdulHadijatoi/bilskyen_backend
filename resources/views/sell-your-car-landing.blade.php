@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.pages.sell_your_car.landing_meta_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="py-20 md:py-28 text-center bg-muted/40">
        <div class="container mx-auto px-4 md:px-6 max-w-4xl">
            <p class="text-sm font-semibold uppercase tracking-wider text-primary mb-4">
                {{ __('messages.pages.sell_your_car.landing_eyebrow') }}
            </p>
            <h1 class="text-4xl font-bold tracking-tight md:text-6xl">
                {{ __('messages.pages.sell_your_car.landing_hero_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-6 max-w-2xl text-lg md:text-xl">
                {{ __('messages.pages.sell_your_car.landing_hero_description') }}
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ $loginUrl }}" class="inline-flex h-12 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90">
                    {{ __('messages.pages.sell_your_car.landing_cta') }}
                </a>
                <a href="{{ $dealerLandingUrl }}" class="inline-flex h-12 items-center justify-center rounded-md border border-border bg-background px-8 text-sm font-medium hover:bg-muted">
                    {{ __('messages.pages.sell_your_car.landing_dealer_cta') }}
                </a>
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('messages.pages.sell_your_car.landing_how_title') }}</h2>
                <p class="text-muted-foreground mt-3 max-w-2xl mx-auto">{{ __('messages.pages.sell_your_car.landing_how_description') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach([1, 2, 3] as $i)
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary text-lg font-semibold">
                            {{ $i }}
                        </div>
                        <h3 class="text-lg font-semibold">{{ __("messages.pages.sell_your_car.landing_step_{$i}_title") }}</h3>
                        <p class="mt-2 text-sm text-muted-foreground">{{ __("messages.pages.sell_your_car.landing_step_{$i}_description") }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16 md:py-20 bg-muted/30">
        <div class="container mx-auto px-4 md:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('messages.pages.sell_your_car.landing_compare_title') }}</h2>
                <p class="text-muted-foreground mt-3 max-w-2xl mx-auto">{{ __('messages.pages.sell_your_car.landing_compare_description') }}</p>
            </div>
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 max-w-4xl mx-auto">
                <div class="rounded-xl border border-border bg-card p-6">
                    <h3 class="text-lg font-semibold">{{ __('messages.pages.sell_your_car.landing_private_title') }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ __('messages.pages.sell_your_car.landing_private_description') }}</p>
                </div>
                <div class="rounded-xl border border-border bg-card p-6">
                    <h3 class="text-lg font-semibold">{{ __('messages.pages.sell_your_car.landing_dealer_title') }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ __('messages.pages.sell_your_car.landing_dealer_description') }}</p>
                    <a href="{{ $dealerLandingUrl }}" class="mt-4 inline-flex text-sm font-medium text-primary hover:underline">
                        {{ __('messages.pages.sell_your_car.landing_dealer_cta') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-primary text-primary-foreground">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <h2 class="text-3xl font-bold tracking-tight">{{ __('messages.pages.sell_your_car.landing_bottom_cta_title') }}</h2>
            <p class="mt-4 max-w-xl mx-auto opacity-90">{{ __('messages.pages.sell_your_car.landing_bottom_cta_description') }}</p>
            <a href="{{ $loginUrl }}" class="mt-8 inline-flex h-11 items-center justify-center rounded-md bg-background px-8 text-sm font-medium text-foreground hover:bg-background/90">
                {{ __('messages.pages.sell_your_car.landing_cta') }}
            </a>
        </div>
    </section>
</div>
@endsection
