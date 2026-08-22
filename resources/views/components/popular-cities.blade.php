@props([
    'cities' => null,
    'limit' => 8,
    'variant' => 'strip',
])
@php
    $cities = $cities ?? app(\App\Services\CityIndexService::class)->topCities((int) $limit);
    $isSidebar = $variant === 'sidebar';
@endphp
@if($cities->isNotEmpty())
@if($isSidebar)
<div {{ $attributes->merge(['class' => 'filter-card bg-white shrink-0 listing-popular-cities']) }}>
    <div class="filter-section">
        <p class="filter-section-title">{{ __('messages.pages.cities.popular_cities') }}</p>
        <div class="filter-pill-row">
            @foreach($cities as $city)
                <a href="/biler-i/{{ $city->slug }}" class="filter-pill">
                    {{ $city->name }}
                    @if($city->published_vehicle_count > 0)
                        <span class="facet-count">{{ $city->published_vehicle_count }}</span>
                    @endif
                </a>
            @endforeach
        </div>
        <a href="/byer" class="mt-2 inline-block text-xs font-medium text-primary hover:underline">{{ __('messages.pages.footer.all_cities') }}</a>
    </div>
</div>
@else
<section {{ $attributes->merge(['class' => 'popular-cities-strip']) }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
        <h2 class="text-lg font-semibold text-foreground">{{ __('messages.pages.cities.popular_cities') }}</h2>
        <a href="/byer" class="text-sm font-medium text-primary hover:underline">{{ __('messages.pages.footer.all_cities') }}</a>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($cities as $city)
            <a href="/biler-i/{{ $city->slug }}" class="inline-flex h-9 items-center rounded-full border border-border bg-card px-3 text-sm hover:border-primary/40 hover:bg-muted transition-colors">
                {{ $city->name }}
                @if($city->published_vehicle_count > 0)
                    <span class="ml-1.5 text-xs text-muted-foreground">{{ $city->published_vehicle_count }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif
@endif
