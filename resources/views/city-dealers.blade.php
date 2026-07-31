@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.pages.cities.dealers_heading', ['city' => $city->name]))

@section('content')
<div class="container mx-auto px-4 md:px-6 py-8 md:py-12">
    <nav class="mb-6 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="/" class="hover:text-foreground">{{ __('messages.pages.cities.breadcrumb_home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('cities.index') }}" class="hover:text-foreground">{{ __('messages.pages.cities.index_heading') }}</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-foreground font-medium">{{ $city->name }}</li>
        </ol>
    </nav>

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-foreground">
            {{ __('messages.pages.cities.dealers_heading', ['city' => $city->name]) }}
        </h1>
        <p class="mt-3 text-base text-muted-foreground leading-relaxed">
            {{ __('messages.pages.cities.dealers_intro', [
                'city' => $city->name,
                'count' => $city->dealer_count,
            ]) }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('cities.cars', $city->slug) }}" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                {{ __('messages.pages.cities.see_cars', ['city' => $city->name]) }}
            </a>
            <a href="/vehicles?city_slug={{ urlencode($city->slug) }}" class="inline-flex h-10 items-center rounded-md border border-input px-4 text-sm font-medium hover:bg-muted">
                {{ __('messages.pages.cities.see_all_cars', ['city' => $city->name]) }}
            </a>
        </div>
    </header>

    @if($dealers->isEmpty())
        <div class="rounded-xl border border-border bg-muted/30 p-8 text-center">
            <p class="text-muted-foreground">{{ __('messages.pages.cities.no_dealers', ['city' => $city->name]) }}</p>
            <a href="{{ route('cities.cars', $city->slug) }}" class="mt-4 inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">
                {{ __('messages.pages.cities.see_cars', ['city' => $city->name]) }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($dealers as $dealer)
                @php
                    $name = $dealer->owner?->name ?? $dealer->slug;
                    $publishedCount = $dealer->vehicles?->count() ?? 0;
                @endphp
                <article class="rounded-xl border border-border bg-card p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-foreground">
                        <a href="/dealer-{{ $dealer->slug }}" class="hover:text-primary">{{ $name }}</a>
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ trim(($dealer->address ?? '').', '.($dealer->postcode ?? '').' '.($dealer->city ?? ''), ' ,') }}
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ __('messages.pages.cities.vehicles_count', ['count' => $publishedCount]) }}
                    </p>
                    <a href="/dealer-{{ $dealer->slug }}" class="mt-4 inline-flex h-9 items-center rounded-md border border-input px-3 text-xs font-medium hover:bg-muted">
                        {{ __('messages.pages.vehicles.view_details') }}
                    </a>
                </article>
            @endforeach
        </div>
    @endif

    @if($vehiclesPreview->isNotEmpty())
        <section class="mt-12">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 class="text-xl font-semibold text-foreground">{{ __('messages.pages.cities.cars_nearby', ['city' => $city->name]) }}</h2>
                <a href="{{ route('cities.cars', $city->slug) }}" class="text-sm font-medium text-primary hover:underline">{{ __('messages.pages.cities.see_cars', ['city' => $city->name]) }}</a>
            </div>
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                @foreach($vehiclesPreview as $vehicle)
                    @php
                        $firstImage = $vehicle->images->first();
                        $imgUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
                        $badges = $listingPresentation->badgeFields($vehicle);
                    @endphp
                    <x-vehicle-listing-item
                        :vehicle="$vehicle"
                        :img-url="$imgUrl"
                        :img-alt="$vehicle->title"
                        :sales-type-name="$vehicle->salesType?->name"
                        :trust-badge="$badges['trust_badge'] ?? false"
                        :price-dropped-recently="$badges['price_dropped_recently'] ?? false"
                        :fair-price-label="$badges['fair_price_label'] ?? null"
                        :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                        :is-boosted="$badges['is_boosted'] ?? false"
                    />
                @endforeach
            </div>
        </section>
    @endif

    @if(!empty($seo['faq_json']))
        <section class="mt-12 max-w-3xl">
            <h2 class="text-xl font-semibold text-foreground mb-4">{{ __('messages.pages.cities.faq_heading') }}</h2>
            <div class="space-y-3">
                @foreach($seo['faq_json'] as $faq)
                    <details class="rounded-lg border border-border bg-card p-4">
                        <summary class="cursor-pointer font-medium text-foreground">{{ $faq['question'] }}</summary>
                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    @if($relatedCities->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-xl font-semibold text-foreground mb-4">{{ __('messages.pages.cities.related_cities') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($relatedCities as $related)
                    <a href="{{ route('cities.dealers', $related->slug) }}" class="inline-flex h-9 items-center rounded-full border border-border px-3 text-sm hover:bg-muted">
                        {{ $related->name }}
                        <span class="ml-1 text-muted-foreground">({{ $related->dealer_count }})</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
