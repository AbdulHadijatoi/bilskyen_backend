@props([
    'vehicle',
    'imgUrl',
    'imgAlt',
    'salesTypeName' => null,
    'trustBadge' => false,
    'priceDroppedRecently' => false,
    'premiumDealerBadge' => false,
    'isBoosted' => false,
])
<div {{ $attributes->merge(['class' => 'vehicle-item site-card flex flex-col overflow-hidden p-0 cursor-pointer h-full w-full min-w-0']) }}>
    <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="vehicle-item-main-link block flex-1 min-w-0">
        <div class="vehicle-image-container relative aspect-[2/1.5] overflow-hidden p-3 pb-0">
            <img
                src="{{ $imgUrl }}"
                alt="{{ $imgAlt }}"
                class="h-full w-full object-cover rounded-md vehicle-listing-thumb"
            />
            <div class="absolute top-4 left-4 z-10 flex max-w-[75%] flex-row flex-wrap items-center gap-1">
                @if($vehicle->dealer_id)
                    <span class="inline-flex items-center rounded-md bg-primary/90 px-2 py-0.5 text-[10px] font-semibold text-primary-foreground shadow-sm backdrop-blur-sm">
                        {{ __('messages.pages.vehicles.dealer') }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-md bg-amber-600/90 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm backdrop-blur-sm">
                        {{ __('messages.pages.vehicles.private') }}
                    </span>
                @endif
                @if($salesTypeName)
                    <span class="inline-flex items-center rounded-md bg-green-600/60 px-2 py-0.5 text-[10px] font-semibold text-primary-foreground shadow-sm">
                        {{ $salesTypeName }}
                    </span>
                @endif
                @if($premiumDealerBadge)
                    <span class="inline-flex items-center rounded-md bg-violet-600/80 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm">
                        {{ __('messages.pages.vehicles.premium_badge') }}
                    </span>
                @endif
                @if($isBoosted)
                    <span class="inline-flex items-center rounded-md bg-amber-500/90 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm">
                        {{ __('messages.pages.vehicles.boosted_badge') }}
                    </span>
                @endif
                @if($trustBadge)
                    <span class="inline-flex items-center rounded-md bg-emerald-600/80 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm">
                        {{ __('messages.pages.vehicles.detail.trust_verified_badge') }}
                    </span>
                @endif
                @if($priceDroppedRecently)
                    <span class="inline-flex items-center rounded-md bg-rose-600/80 px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm">
                        {{ __('messages.pages.vehicles.detail.price_dropped_badge') }}
                    </span>
                @endif
            </div>
            <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring dark:bg-card/90" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle->id }}, event); return false;" aria-label="{{ __('messages.forms.add_to_favorites') }}" title="{{ __('messages.forms.add_to_favorites') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 {{ $vehicle->dealer_id ? 'text-primary' : 'text-foreground' }} hover:opacity-80 transition-colors heart-icon" data-vehicle-id="{{ $vehicle->id }}" data-dealer-id="{{ $vehicle->dealer_id ?? '' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
            </button>
        </div>

        <div class="vehicle-content-wrapper flex flex-1 flex-col p-3 space-y-1 min-h-[7.5rem]">
            <div class="flex flex-col gap-1">
                <h3 class="flex items-center gap-2 text-xs font-semibold leading-snug line-clamp-2 min-h-[2rem]">
                    {{ \App\Helpers\FormatHelper::formatListingTitle($vehicle->title) }}
                </h3>
                @if($vehicle->variant_name)
                    <p class="text-muted-foreground text-xs font-normal line-clamp-1">
                        {{ $vehicle->variant_name }}
                    </p>
                @else
                    <p class="text-xs font-normal invisible select-none" aria-hidden="true">&nbsp;</p>
                @endif
                <p class="vehicle-listing-price text-lg font-bold">
                    {{ \App\Helpers\FormatHelper::formatCurrency($vehicle->price ?? null) }}
                </p>
            </div>

            <div class="vehicle-listing-badges mt-auto flex min-h-[2rem] flex-wrap content-start gap-1 text-xs font-light">
                @if($vehicle->mileage || $vehicle->km_driven)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->mileage ?? $vehicle->km_driven ?? 0) }} km</span>
                @endif
                @if($vehicle->engine_power_hp)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->engine_power_hp, 0) }} HP</span>
                @endif
                @if($vehicle->first_registration_date)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ \App\Helpers\FormatHelper::formatMonthYear($vehicle->first_registration_date) }}</span>
                @endif
                @if($vehicle->fuel_type_name)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->fuel_type_name }}</span>
                @endif
                @if($vehicle->gear_type_name)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->gear_type_name }}</span>
                @endif
            </div>
        </div>
    </a>

    <div class="vehicle-item-footer mt-auto" onclick="event.stopPropagation()">
        @php
            $listingLocation = \App\Helpers\FormatHelper::formatListingLocation(
                $vehicle->seller_address ?? null,
                $vehicle->seller_postcode ?? null,
                $vehicle->seller_city ?? ($vehicle->city ?? null)
            );
        @endphp
        <div class="px-3 pt-3 pb-2 min-h-[2.25rem]">
            @if($listingLocation !== '')
                <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span class="truncate text-right" title="{{ $listingLocation }}">{{ $listingLocation }}</span>
                </div>
            @endif
        </div>
        <div class="p-3 pt-0">
            <div class="vehicle-actions-section flex w-full flex-col gap-2 sm:flex-row">
                <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="flex-1" onclick="event.stopPropagation()">
                    <button type="button" class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 hover:shadow-md active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                        {{ __('messages.pages.vehicles.view_details') }}
                    </button>
                </a>
                <button
                    type="button"
                    onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '{{ $vehicle->slug }}')"
                    class="flex-1 inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground hover:shadow-sm active:scale-[0.98] dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                >
                    {{ __('messages.pages.vehicles.enquire') }}
                </button>
            </div>
        </div>
    </div>
</div>
