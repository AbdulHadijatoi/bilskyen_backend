@props([
    'vehicles' => null,
    'excludeId' => null,
    'listingPresentation' => null,
    'lgCols' => 4,
])
@php
    $items = $vehicles ?? collect();
    $listingPresentation = $listingPresentation ?? app(\App\Services\VehicleListingPresentationService::class);
    $hasItems = $items->isNotEmpty();
@endphp
<section
    {{ $attributes->merge([
        'id' => 'recently-viewed-rail',
        'class' => 'space-y-4',
        'aria-labelledby' => 'recently-viewed-heading',
    ]) }}
    data-recently-viewed
    data-exclude-id="{{ $excludeId }}"
    @if(! $hasItems) hidden @endif
>
    <h2 id="recently-viewed-heading" class="text-xl font-semibold text-foreground">
        {{ __('messages.pages.vehicles.recently_viewed_title') }}
    </h2>
    <div
        id="recently-viewed-grid"
        class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 {{ (int) $lgCols === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4' }}"
        data-recently-viewed-grid
    >
        @foreach($items as $recentVehicle)
            @php
                $firstImage = $recentVehicle->images->first();
                $imgUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
                $badges = $listingPresentation->badgeFields($recentVehicle);
            @endphp
            <x-vehicle-listing-item
                :vehicle="$recentVehicle"
                :img-url="$imgUrl"
                :img-alt="$recentVehicle->title"
                :sales-type-name="$recentVehicle->salesType?->name"
                :trust-badge="$badges['trust_badge'] ?? false"
                :price-dropped-recently="$badges['price_dropped_recently'] ?? false"
                :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                :is-boosted="$badges['is_boosted'] ?? false"
            />
        @endforeach
    </div>
</section>
