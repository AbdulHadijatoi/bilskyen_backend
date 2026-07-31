@props([
    'cities' => null,
    'limit' => 8,
])
@php
    $cities = $cities ?? app(\App\Services\CityIndexService::class)->topCities((int) $limit);
@endphp
@if($cities->isNotEmpty())
<section {{ $attributes->merge(['class' => 'popular-cities-strip']) }}>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
        <h2 class="text-lg font-semibold text-foreground">{{ __('messages.pages.cities.popular_cities') }}</h2>
        <a href="{{ route('cities.index') }}" class="text-sm font-medium text-primary hover:underline">{{ __('messages.pages.footer.all_cities') }}</a>
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($cities as $city)
            <a href="{{ route('cities.cars', $city->slug) }}" class="inline-flex h-9 items-center rounded-full border border-border bg-card px-3 text-sm hover:border-primary/40 hover:bg-muted transition-colors">
                {{ $city->name }}
                @if($city->published_vehicle_count > 0)
                    <span class="ml-1.5 text-xs text-muted-foreground">{{ $city->published_vehicle_count }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif
