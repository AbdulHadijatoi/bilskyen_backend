@extends('layouts.app')

@section('title', __('messages.pages.vehicles.compare_page_title') . ' | Bilskyen')

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="space-y-1">
            <h1 class="text-3xl font-bold text-foreground">{{ __('messages.pages.vehicles.compare_page_title') }}</h1>
            <p class="text-muted-foreground">{{ __('messages.pages.vehicles.compare_tray_title') }}</p>
        </div>
        <a href="{{ route('vehicles') }}" class="text-sm font-medium text-primary hover:underline">
            {{ __('messages.pages.vehicles.browse_vehicles') }}
        </a>
    </div>

    @if(($vehicles ?? collect())->count() >= 2)
    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($vehicles as $compareVehicle)
            @php
                $firstImage = $compareVehicle->images->first();
                $imgUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
                $badges = $listingPresentation->badgeFields($compareVehicle);
            @endphp
            <x-vehicle-listing-item
                :vehicle="$compareVehicle"
                :img-url="$imgUrl"
                :img-alt="$compareVehicle->title"
                :sales-type-name="$compareVehicle->salesType?->name"
                :trust-badge="$badges['trust_badge'] ?? false"
                :price-dropped-recently="$badges['price_dropped_recently'] ?? false"
                :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                :is-boosted="$badges['is_boosted'] ?? false"
            />
        @endforeach
    </div>
    @elseif(($vehicles ?? collect())->isNotEmpty())
    <p class="text-muted-foreground">{{ __('messages.pages.vehicles.compare_need_two') }}</p>
    @else
    <p class="text-muted-foreground">{{ __('messages.pages.vehicles.compare_empty') }}</p>
    @endif
</div>
<x-listing-compare-tray />
<x-compare-helpers />
@endsection
