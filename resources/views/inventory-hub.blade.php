@extends('layouts.app')

@section('title', $seo['meta_title'] ?? $heading)

@section('content')
<div class="container mx-auto px-4 md:px-6 py-8 md:py-12">
    <nav class="mb-6 text-sm text-muted-foreground" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="/" class="hover:text-foreground">{{ __('messages.pages.cities.breadcrumb_home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('vehicles') }}" class="hover:text-foreground">{{ __('messages.pages.footer.browse_vehicles') }}</a></li>
            <li aria-hidden="true">/</li>
            <li class="text-foreground font-medium">{{ $heading }}</li>
        </ol>
    </nav>

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-foreground">{{ $heading }}</h1>
        <p class="mt-3 text-base text-muted-foreground leading-relaxed">{{ $intro }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ $ctaUrl }}" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                {{ $ctaLabel }}
            </a>
        </div>
    </header>

    @if($vehicles->isEmpty())
        <div class="rounded-xl border border-border bg-muted/30 p-8 text-center">
            <p class="text-muted-foreground">{{ $emptyLabel }}</p>
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
</div>
@endsection
