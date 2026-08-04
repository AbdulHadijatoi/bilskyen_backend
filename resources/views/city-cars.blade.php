@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.pages.cities.cars_heading', ['city' => $city->name]))

@section('content')
@php
    $minFormatted = $city->min_price !== null ? number_format((float) $city->min_price, 0, ',', '.') : '—';
    $maxFormatted = $city->max_price !== null ? number_format((float) $city->max_price, 0, ',', '.') : '—';
    $brandsText = !empty($brandNames) ? implode(', ', $brandNames) : '—';
@endphp
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
            {{ __('messages.pages.cities.cars_heading', ['city' => $city->name]) }}
        </h1>
        <p class="mt-3 text-base text-muted-foreground leading-relaxed">
            @if($city->published_vehicle_count > 0)
                {{ __('messages.pages.cities.cars_intro', [
                    'city' => $city->name,
                    'count' => $city->published_vehicle_count,
                    'min' => $minFormatted,
                    'max' => $maxFormatted,
                    'brands' => $brandsText,
                ]) }}
            @else
                {{ __('messages.pages.cities.cars_intro_simple', ['city' => $city->name]) }}
            @endif
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('vehicles', ['city_slug' => $city->slug]) }}" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                {{ __('messages.pages.cities.see_all_cars', ['city' => $city->name]) }}
            </a>
            @if($city->dealer_count > 0)
                <a href="{{ route('cities.dealers', $city->slug) }}" class="inline-flex h-10 items-center rounded-md border border-input px-4 text-sm font-medium hover:bg-muted">
                    {{ __('messages.pages.cities.see_dealers', ['city' => $city->name]) }}
                </a>
            @endif
        </div>
    </header>

    @if($vehicles->isEmpty())
        <div class="rounded-xl border border-border bg-muted/30 p-8 text-center">
            <p class="text-muted-foreground">{{ __('messages.pages.cities.no_cars', ['city' => $city->name]) }}</p>
            <a href="{{ route('vehicles') }}" class="mt-4 inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">{{ __('messages.pages.footer.browse_vehicles') }}</a>
        </div>
    @else
        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3">
            @foreach($vehicles as $vehicle)
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
                    :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                    :is-boosted="$badges['is_boosted'] ?? false"
                />
            @endforeach
        </div>
    @endif

    @if($dealers->isNotEmpty())
        <section class="mt-12">
            <h2 class="text-xl font-semibold text-foreground mb-4">{{ __('messages.pages.cities.local_dealers', ['city' => $city->name]) }}</h2>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($dealers as $dealer)
                    <li>
                        <a href="/dealer-{{ $dealer->slug }}" class="block rounded-lg border border-border bg-card p-4 hover:border-primary/40 transition-colors">
                            <span class="font-medium text-foreground">{{ $dealer->owner?->name ?? $dealer->slug }}</span>
                            @if($dealer->city || $dealer->postcode)
                                <span class="block text-sm text-muted-foreground mt-1">{{ trim(($dealer->postcode ?? '').' '.($dealer->city ?? '')) }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
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
                    <a href="{{ route('cities.cars', $related->slug) }}" class="inline-flex h-9 items-center rounded-full border border-border px-3 text-sm hover:bg-muted">
                        {{ $related->name }}
                        <span class="ml-1 text-muted-foreground">({{ $related->published_vehicle_count }})</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
