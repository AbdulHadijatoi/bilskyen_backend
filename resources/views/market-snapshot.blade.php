@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.pages.market.heading'))

@section('content')
@php
    $generated = \Carbon\Carbon::parse($snapshot['generated_at']);
    $price = fn ($value) => $value !== null ? number_format((float) $value, 0, ',', '.').' kr.' : '—';
    $days = $snapshot['median_days_on_market'] !== null ? number_format((float) $snapshot['median_days_on_market'], 0, ',', '.') : '—';
@endphp
<div class="container mx-auto px-4 md:px-6 py-8 md:py-12">
    <nav class="mb-6 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="/" class="hover:text-foreground">{{ __('messages.pages.cities.breadcrumb_home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-foreground font-medium">{{ __('messages.pages.market.heading') }}</li>
        </ol>
    </nav>

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-foreground">
            {{ __('messages.pages.market.heading') }}
        </h1>
        <p class="mt-3 text-base text-muted-foreground leading-relaxed">
            {{ __('messages.pages.market.intro', ['count' => number_format((int) $snapshot['listing_count'], 0, ',', '.')]) }}
        </p>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ __('messages.pages.market.generated_at', ['date' => $generated->timezone(config('app.timezone'))->translatedFormat('j. F Y H:i')]) }}
        </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 mb-10">
        <article class="rounded-xl border border-border bg-card p-5">
            <h2 class="text-sm font-medium text-muted-foreground">{{ __('messages.pages.market.median_price_label') }}</h2>
            <p class="mt-2 text-2xl font-semibold text-foreground">{{ $price($snapshot['median_price']) }}</p>
        </article>
        <article class="rounded-xl border border-border bg-card p-5">
            <h2 class="text-sm font-medium text-muted-foreground">{{ __('messages.pages.market.median_dom_label') }}</h2>
            <p class="mt-2 text-2xl font-semibold text-foreground">{{ $days }} {{ __('messages.pages.market.days') }}</p>
        </article>
    </div>

    <section class="mb-10">
        <h2 class="text-xl font-semibold text-foreground mb-4">{{ __('messages.pages.market.by_fuel_heading') }}</h2>
        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('messages.pages.market.fuel_label') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('messages.pages.market.count_label') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('messages.pages.market.median_price_label') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($snapshot['by_fuel'] as $row)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3">{{ number_format((int) $row['count'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $price($row['median_price']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="max-w-3xl">
        <h2 class="text-xl font-semibold text-foreground mb-3">{{ __('messages.pages.market.methodology_heading') }}</h2>
        <p class="text-muted-foreground leading-relaxed">{{ __('messages.pages.market.methodology') }}</p>
        <a href="{{ route('vehicles') }}" class="mt-6 inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            {{ __('messages.pages.footer.browse_vehicles') }}
        </a>
    </section>
</div>
@endsection
