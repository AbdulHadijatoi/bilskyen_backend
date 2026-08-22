@props([
    'vehicle',
    'imgUrl',
    'imgAlt',
    'salesTypeName' => null,
    'trustBadge' => false,
    'priceDroppedRecently' => false,
    'premiumDealerBadge' => false,
    'isBoosted' => false,
    'isFavorited' => false,
])
@php
    $listingLocation = \App\Helpers\FormatHelper::formatListingLocation(
        $vehicle->address ?? $vehicle->seller_address ?? null
    );
    $cardTitle = \App\Helpers\FormatHelper::formatListingCardTitle(
        $vehicle->title,
        $vehicle->variant_name ?? null
    );
    $fuelShort = \App\Helpers\FormatHelper::formatFuelTypeShort($vehicle->fuel_type_name);
    $newListingBadge = \App\Helpers\FormatHelper::newListingBadgeLabel($vehicle->created_at ?? null);
    $imagesCount = $vehicle->relationLoaded('images')
        ? $vehicle->images->count()
        : (int) ($vehicle->images_count ?? 0);
    $mileageValue = $vehicle->mileage ?? $vehicle->km_driven ?? null;
    $chipClass = 'inline-flex items-center rounded-md border border-border px-2 py-1 text-xs';
@endphp
@once
<style>
    .vehicle-listing-price {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.02em;
        color: var(--foreground, #0f172a);
        font-variant-numeric: tabular-nums;
    }
    .vehicle-listing-title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.375;
        color: var(--muted-foreground, #64748b);
    }
    .vehicle-listing-new-badge {
        display: inline-flex;
        align-items: center;
        max-width: calc(100% - 3.75rem);
        border-radius: 0.375rem;
        background: #16a34a;
        color: #fff;
        padding: 0.125rem 0.5rem;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.25;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
    }
    .vehicle-listing-photo-count {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border-radius: 0.375rem;
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        padding: 0.125rem 0.375rem;
        font-size: 10px;
        font-weight: 500;
        backdrop-filter: blur(4px);
    }
</style>
@endonce
<div {{ $attributes->merge(['class' => 'vehicle-item site-card flex flex-col overflow-hidden p-0 cursor-pointer h-full w-full min-w-0']) }}>
    <div class="vehicle-image-container relative aspect-[2/1.5] overflow-hidden bg-muted">
        <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="vehicle-listing-image-link absolute inset-0 z-0 block" tabindex="-1" aria-hidden="true">
            <img
                src="{{ $imgUrl }}"
                alt="{{ $imgAlt }}"
                width="800"
                height="600"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 block h-full w-full object-cover"
            />
        </a>
        <div class="vehicle-listing-overlays pointer-events-none absolute inset-0 z-10 flex flex-col justify-between px-4 py-3">
            <div class="vehicle-listing-overlays-top flex items-start justify-between gap-2">
                <div class="vehicle-listing-overlay-badges flex max-w-[70%] flex-row flex-wrap items-center gap-1">
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
                @if($imagesCount > 0)
                    <span class="vehicle-listing-photo-count inline-flex shrink-0 items-center gap-1 rounded-md bg-black/55 px-1.5 py-0.5 text-[10px] font-medium text-white backdrop-blur-sm" title="{{ __('messages.pages.vehicles.photo_count_label', ['count' => $imagesCount]) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3" aria-hidden="true">
                            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/>
                            <circle cx="12" cy="13" r="3"/>
                        </svg>
                        1/{{ $imagesCount }}
                    </span>
                @endif
            </div>
            <div class="vehicle-listing-overlays-bottom flex items-end justify-between gap-2">
                @if($newListingBadge)
                    <span class="vehicle-listing-new-badge inline-flex max-w-[calc(100%-3.75rem)] items-center rounded-md bg-[#16a34a] px-2 py-0.5 text-[10px] font-semibold text-white shadow-sm">
                        {{ $newListingBadge }}
                    </span>
                @endif
                <button type="button" class="vehicle-listing-favorite pointer-events-auto ml-auto flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring dark:bg-card/90" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle->id }}, event); return false;" aria-label="{{ $isFavorited ? __('messages.forms.remove_from_favorites') : __('messages.forms.add_to_favorites') }}" title="{{ $isFavorited ? __('messages.forms.remove_from_favorites') : __('messages.forms.add_to_favorites') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 {{ $isFavorited ? 'text-red-500 filled' : ($vehicle->dealer_id ? 'text-primary' : 'text-foreground') }} hover:opacity-80 transition-colors heart-icon" data-vehicle-id="{{ $vehicle->id }}" data-dealer-id="{{ $vehicle->dealer_id ?? '' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="vehicle-item-main-link flex flex-1 flex-col min-w-0">
        <div class="vehicle-content-wrapper flex flex-1 flex-col px-3 pt-3 min-h-0">
            <div class="vehicle-listing-header flex shrink-0 flex-col gap-1 text-left">
                <p class="vehicle-listing-price text-2xl font-extrabold tabular-nums tracking-tight">
                    {{ \App\Helpers\FormatHelper::formatCurrency($vehicle->price ?? null) }}
                </p>
                <h3 class="vehicle-listing-title text-sm font-medium leading-snug text-muted-foreground line-clamp-2">
                    {{ $cardTitle }}
                </h3>
            </div>

            @if($mileageValue || $vehicle->engine_power_hp || $vehicle->first_registration_date || $fuelShort !== '' || $vehicle->gear_type_name)
            <div class="vehicle-listing-badges flex flex-1 flex-wrap content-center items-center min-h-[2rem] gap-1 py-2 text-xs font-light">
                @if($mileageValue)
                    <span class="{{ $chipClass }}">{{ number_format((float) $mileageValue) }} km</span>
                @endif
                @if($vehicle->engine_power_hp)
                    <span class="{{ $chipClass }}">{{ number_format((float) $vehicle->engine_power_hp, 0) }} HP</span>
                @endif
                @if($vehicle->first_registration_date)
                    <span class="{{ $chipClass }}">{{ \App\Helpers\FormatHelper::formatMonthYear($vehicle->first_registration_date) }}</span>
                @endif
                @if($fuelShort !== '')
                    <span class="{{ $chipClass }}" title="{{ $vehicle->fuel_type_name }}">{{ $fuelShort }}</span>
                @endif
                @if($vehicle->gear_type_name)
                    <span class="{{ $chipClass }}">{{ $vehicle->gear_type_name }}</span>
                @endif
            </div>
            @endif
        </div>
    </a>

    <div class="vehicle-item-footer mt-auto flex shrink-0 flex-col gap-2 px-3 pb-3" onclick="event.stopPropagation()">
        <div class="vehicle-listing-location min-h-[1.25rem]">
            @if($listingLocation !== '')
                <div class="flex items-center justify-start gap-2 text-xs text-muted-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span class="truncate text-left" title="{{ $listingLocation }}">{{ $listingLocation }}</span>
                </div>
            @endif
        </div>
        <div class="vehicle-actions-section flex w-full flex-row items-center gap-2">
            <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 hover:shadow-md active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border" onclick="event.stopPropagation()">
                {{ __('messages.pages.vehicles.view_details') }}
            </a>
            <button
                type="button"
                onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '{{ $vehicle->slug }}')"
                class="vehicle-card-enquire-btn inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border bg-background shadow-xs transition-all hover:bg-accent hover:text-accent-foreground hover:shadow-sm active:scale-[0.98] dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                aria-label="{{ __('messages.pages.vehicles.enquire') }}"
                title="{{ __('messages.pages.vehicles.enquire') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
