@extends('layouts.app')

@section('title', ($dealer->owner?->name ?? __('messages.pages.dealer_page.dealer_label')).' – '.__('messages.pages.dealer_page.meta_title_cars'))

@php
    use App\Helpers\FormatHelper;
    use App\Helpers\DealerDisplayHelper;
@endphp

@section('content')
@php
    $themePrimary = $dealer->theme_primary_color ?? null;
    $themeSecondary = $dealer->theme_secondary_color ?? null;
    $publicCvr = FormatHelper::isValidPublicCvr($dealer->cvr) ? $dealer->cvr : null;
    $dealerAddressLine = DealerDisplayHelper::formatDealerAddressLine($dealer);
    $dealerOwnerName = $dealer->owner?->name ? trim($dealer->owner->name) : null;
@endphp
@if($themePrimary || $themeSecondary)
<style>
    :root {
        @if($themePrimary) --primary: {{ $themePrimary }}; @endif
        @if($themeSecondary) --secondary: {{ $themeSecondary }}; @endif
    }
</style>
@endif
<div class="bg-muted min-h-screen">
    <div class="container mx-auto space-y-4 py-6">
        <!-- Top Section: Dealer Info + Filters (Full Row) -->
        <div class="rounded-lg bg-card p-6">
            <!-- Dealer Information -->
            <div class="flex flex-col md:flex-row gap-6 items-start md:items-center mb-6">
                <!-- Dealer Logo -->
                @if($dealer->logo_url)
                <div class="flex-shrink-0">
                    <img src="{{ $dealer->logo_url }}{{ str_contains($dealer->logo_url, '?') ? '&' : '?' }}t={{ $dealer->updated_at?->timestamp ?? time() }}" alt="{{ $dealer->owner?->name ?? __('messages.pages.vehicles.dealer') }}" class="h-32 w-auto object-contain rounded-md">
                </div>
                @endif
                
                <!-- Dealer Details -->
                <div class="flex-1 space-y-2">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-3xl font-bold text-foreground">{{ $dealer->owner?->name ?? __('messages.pages.dealer_page.dealer_label') }}</h1>
                            @if(DealerDisplayHelper::isDealerVerified($dealer))
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                                {{ __('messages.pages.dealer_page.verified_dealer') }}
                            </span>
                            @endif
                        </div>
                        @if(!empty($reviewSummary['rating']))
                        <div class="text-sm text-muted-foreground mt-1">
                            ★ {{ number_format($reviewSummary['rating'], 1) }}
                            @if(!empty($reviewSummary['review_count']))
                                ({{ $reviewSummary['review_count'] }} {{ __('messages.pages.dealer_page.reviews') }})
                            @endif
                            @if(!empty($reviewSummary['review_url']))
                                · <a href="{{ $reviewSummary['review_url'] }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ __('messages.pages.dealer_page.view_reviews') }}</a>
                            @endif
                        </div>
                        @elseif(!empty($reviewSummary['review_url']))
                        <a href="{{ $reviewSummary['review_url'] }}" target="_blank" rel="noopener" class="text-sm text-primary hover:underline mt-1 inline-block">{{ __('messages.pages.dealer_page.view_reviews') }}</a>
                        @endif
                        <button 
                            type="button"
                            onclick="openDealerEnquiryDialog()"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            {{ __('messages.pages.dealer_page.send_enquiry') }}
                        </button>
                    </div>
                    @if($publicCvr || $dealerAddressLine || ($dealer->owner && $dealer->owner->phone) || ($dealer->owner && $dealer->owner->email))
                    <div class="divide-y divide-border [&>*]:py-2 [&>*:first-child]:pt-0 [&>*:last-child]:pb-0">
                        @if($publicCvr)
                        <p class="text-muted-foreground">CVR: {{ $publicCvr }}</p>
                        @endif
                        @if($dealerAddressLine)
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span>{{ $dealerAddressLine }}</span>
                        </div>
                        @endif
                        @if($dealer->owner && $dealer->owner->phone)
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                            <span>{{ $dealer->owner->phone }}</span>
                        </div>
                        @endif
                        @if($dealer->owner && $dealer->owner->email)
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            <span>{{ $dealer->owner->email }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                    @php
                        $dealerCity = app(\App\Services\CityIndexService::class)->resolveCityForDealer($dealer);
                    @endphp
                    @if($dealerCity)
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('cities.cars', $dealerCity->slug) }}" class="inline-flex h-8 items-center rounded-md border border-input px-3 text-xs font-medium hover:bg-muted">
                            {{ __('messages.pages.cities.see_cars', ['city' => $dealerCity->name]) }}
                        </a>
                        <a href="{{ route('cities.dealers', $dealerCity->slug) }}" class="inline-flex h-8 items-center rounded-md border border-input px-3 text-xs font-medium hover:bg-muted">
                            {{ __('messages.pages.cities.see_dealers', ['city' => $dealerCity->name]) }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Search/Filter Section -->
            <div class="border-t border-border pt-4">
            @if(!$hasVehicles)
            <div class="rounded-lg border border-dashed border-border bg-muted/40 px-4 py-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2 h-6 w-6 text-muted-foreground">
                    <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                </svg>
                <p class="text-sm font-medium text-foreground">{{ __('messages.pages.dealer_page.no_vehicles_found') }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('messages.pages.dealer_page.no_vehicles_description') }}</p>
            </div>
            @else
            <form id="search-form" class="flex flex-col sm:flex-row gap-3 items-end">
                <!-- Search Input -->
                <div class="relative flex-1 w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="search-input"
                        value="{{ request()->query('search', '') }}"
                        placeholder="{{ __('messages.pages.dealer_page.search_placeholder') }}"
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                </div>

                <!-- Brand Filter -->
                <div class="w-full sm:w-auto sm:min-w-[150px]">
                    <select 
                        name="brand_id"
                        id="brand-select"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="">{{ __('messages.pages.dealer_page.all_brands') }}</option>
                        @foreach($filterOptions['brands'] as $brand)
                        <option value="{{ $brand->id }}" @if(isset($currentFilters['brand_id']) && $currentFilters['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Model Filter -->
                <div class="w-full sm:w-auto sm:min-w-[150px]">
                    <select 
                        name="model_id" 
                        id="model-select"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(!isset($currentFilters['brand_id']) || empty($currentFilters['brand_id'])) disabled @endif
                    >
                        <option value="">@if(!isset($currentFilters['brand_id']) || empty($currentFilters['brand_id'])) {{ __('messages.pages.dealer_page.model') }} @else {{ __('messages.pages.dealer_page.all_models') }} @endif</option>
                        @foreach($filterOptions['models'] as $model)
                        <option value="{{ $model->id }}" data-brand-id="{{ $model->brand_id }}" @if(isset($currentFilters['model_id']) && $currentFilters['model_id'] == $model->id) selected @endif>{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div class="w-full sm:w-auto sm:min-w-[150px]">
                    <select 
                        name="sort"
                        id="sort-select"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option value="standard" @if(!isset($currentFilters['sort']) || $currentFilters['sort'] == 'standard') selected @endif>{{ __('messages.pages.dealer_page.newest_first') }}</option>
                        <option value="price_asc" @if(isset($currentFilters['sort']) && $currentFilters['sort'] == 'price_asc') selected @endif>{{ __('messages.pages.dealer_page.price_lowest_first') }}</option>
                        <option value="price_desc" @if(isset($currentFilters['sort']) && $currentFilters['sort'] == 'price_desc') selected @endif>{{ __('messages.pages.dealer_page.price_highest_first') }}</option>
                        <option value="date_desc" @if(isset($currentFilters['sort']) && $currentFilters['sort'] == 'date_desc') selected @endif>{{ __('messages.pages.dealer_page.date_newest_first') }}</option>
                        <option value="year_desc" @if(isset($currentFilters['sort']) && $currentFilters['sort'] == 'year_desc') selected @endif>{{ __('messages.pages.dealer_page.year_newest_first') }}</option>
                    </select>
                </div>

                <button 
                    type="submit"
                    id="dealer-apply-filters"
                    class="inline-flex h-10 w-full sm:w-auto items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 whitespace-nowrap disabled:opacity-50 disabled:pointer-events-none"
                >
                    {{ __('messages.pages.dealer_page.apply_filters') }}
                </button>
            </form>
            @endif
            </div>
        </div>

        <!-- Vehicle Listings Section (Full Width) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-md text-foreground">{{ __('messages.pages.dealer_page.our_vehicles') }}</h2>
                <div class="flex items-center gap-4">
                    <span class="text-muted-foreground text-sm">{{ $vehicles->total() }} {{ __('messages.pages.dealer_page.vehicles_count') }}</span>
                    <!-- View Toggle Buttons -->
                    <div class="hidden sm:inline-flex items-center gap-1 p-1 rounded-full bg-gray-150">
                        <label class="view-toggle-label inline-flex items-center px-3 py-1 rounded-full text-xs cursor-pointer transition-all view-card-label bg-white text-foreground font-semibold">
                            <input 
                                type="radio" 
                                name="view-toggle" 
                                value="card"
                                class="sr-only peer view-toggle-radio"
                                checked
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <rect width="7" height="7" x="3" y="3" rx="1"></rect>
                                <rect width="7" height="7" x="14" y="3" rx="1"></rect>
                                <rect width="7" height="7" x="3" y="14" rx="1"></rect>
                                <rect width="7" height="7" x="14" y="14" rx="1"></rect>
                            </svg>
                        </label>
                        <label class="view-toggle-label inline-flex items-center px-3 py-1 rounded-full text-xs cursor-pointer transition-all view-list-label bg-gray-150 text-muted-foreground hover:text-foreground">
                            <input 
                                type="radio" 
                                name="view-toggle" 
                                value="list"
                                class="sr-only peer view-toggle-radio"
                            >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <line x1="8" x2="21" y1="6" y2="6"></line>
                                <line x1="8" x2="21" y1="12" y2="12"></line>
                                <line x1="8" x2="21" y1="18" y2="18"></line>
                                <line x1="3" x2="3.01" y1="6" y2="6"></line>
                                <line x1="3" x2="3.01" y1="12" y2="12"></line>
                                <line x1="3" x2="3.01" y1="18" y2="18"></line>
                            </svg>
                        </label>
                    </div>
                </div>
            </div>

        <!-- Vehicle Grid/List -->
        <div id="vehicle-container" class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-view="card">
                @forelse($vehicles as $vehicle)
                <div class="flex flex-col rounded-2xl bg-card overflow-hidden p-0 cursor-pointer h-full shadow-sm">
                    <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="flex flex-1 flex-col min-w-0">
                        <!-- Vehicle Image -->
                        <div class="vehicle-image-container relative aspect-[2/1.5] overflow-hidden">
                            @php
                                $dealerCardImage = $vehicle->images->first();
                                $dealerCardSrc = $dealerCardImage?->thumbnail_url ?? $dealerCardImage?->image_url;
                                if (is_string($dealerCardSrc) && str_contains($dealerCardSrc, 'placeholder-vehicle')) {
                                    $dealerCardSrc = null;
                                }
                            @endphp
                            @if($dealerCardSrc)
                            <img
                                src="{{ $dealerCardSrc }}"
                                alt="{{ trim(($vehicle->brand_name ?? '') . ' ' . ($vehicle->model_name ?? '')) }}"
                                width="800"
                                height="600"
                                loading="lazy"
                                decoding="async"
                                class="block h-full w-full object-cover"
                            />
                            @else
                            <div class="h-full w-full bg-muted" aria-hidden="true"></div>
                            @endif
                            <!-- Badges - Top Left -->
                            <div class="absolute top-4 left-4 z-10 flex flex-row flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                    {{ __('messages.pages.vehicles.dealer') }}
                                </span>
                                @if($vehicle->salesType)
                                <span class="inline-flex items-center rounded-md bg-green-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                    {{ $vehicle->salesType->name }}
                                </span>
                                @endif
                            </div>
                            <!-- Heart Icon - Top Right -->
                            <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle->id }}, event); return false;" aria-label="{{ __('messages.forms.add_to_favorites') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600 hover:text-red-500 transition-colors heart-icon" data-vehicle-id="{{ $vehicle->id }}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Vehicle Details -->
                        @php
                            $dealerListingLocation = \App\Helpers\FormatHelper::formatListingLocation(
                                $vehicle->address ?? null,
                                $vehicle->postcode ?? null,
                                $vehicle->city ?? null
                            );
                            $dealerVariant = $vehicle->variant_name ?? $vehicle->version ?? null;
                        @endphp
                        <div class="vehicle-content-wrapper flex flex-1 flex-col px-3 pt-3 min-h-0">
                            <div class="vehicle-listing-header flex shrink-0 flex-col gap-1 text-left">
                                <h3 class="text-sm font-semibold leading-snug truncate line-clamp-1">
                                    {{ \App\Helpers\FormatHelper::formatListingTitle($vehicle->title) }}
                                </h3>
                                @if($dealerVariant)
                                <p class="text-muted-foreground text-xs font-normal line-clamp-1">
                                    {{ $dealerVariant }}
                                </p>
                                @endif
                                <p class="vehicle-listing-price text-lg font-bold">
                                    {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                                </p>
                            </div>

                            <div class="vehicle-listing-badges flex flex-1 flex-wrap content-center items-center min-h-[2rem] gap-1 py-2 text-xs font-light">
                                @if($vehicle->km_driven !== null)
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors">{{ number_format((int) $vehicle->km_driven) }} km</span>
                                @endif
                                @if($vehicle->engine_power_hp)
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors">{{ number_format($vehicle->engine_power_hp, 0) }} HP</span>
                                @endif
                                @if($vehicle->first_registration_date)
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors">{{ \App\Helpers\FormatHelper::formatMonthYear($vehicle->first_registration_date) }}</span>
                                @endif
                                @if($vehicle->fuel_type_name)
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors">{{ $vehicle->fuel_type_name }}</span>
                                @endif
                                @if($vehicle->gear_type_name)
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors">{{ $vehicle->gear_type_name }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    
                    <!-- Card Footer -->
                    <div class="vehicle-item-footer mt-auto flex shrink-0 flex-col gap-2 px-3 pb-3" onclick="event.stopPropagation()">
                        <div class="vehicle-listing-location min-h-[1.25rem]">
                            @if($dealerListingLocation !== '')
                            <div class="flex items-center justify-start gap-2 text-xs text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="truncate text-left" title="{{ $dealerListingLocation }}">{{ $dealerListingLocation }}</span>
                            </div>
                            @endif
                        </div>
                        <div class="vehicle-actions-section flex w-full flex-row items-center gap-2">
                            <a href="{{ route('vehicle.detail', $vehicle->slug) }}" class="min-w-0 flex-[2]">
                                <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 hover:shadow-md">
                                    {{ __('messages.pages.vehicles.view_details') }}
                                </button>
                            </a>
                            <button class="flex-1 inline-flex h-9 w-full min-w-0 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground" onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '{{ $vehicle->slug }}');">
                                {{ __('messages.pages.vehicles.enquire') }}
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-muted-foreground">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">{{ __('messages.pages.dealer_page.no_vehicles_found') }}</h3>
                        <p class="text-muted-foreground mt-1">
                            {{ __('messages.pages.dealer_page.no_vehicles_description') }}
                        </p>
                    </div>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($vehicles->hasPages())
            <div class="flex items-center justify-center gap-2">
                @if($vehicles->onFirstPage())
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    {{ __('messages.common.previous') }}
                </button>
                @else
                <a href="{{ $vehicles->previousPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    {{ __('messages.common.previous') }}
                </a>
                @endif

                <span class="px-4 py-2 text-sm text-muted-foreground">
                    Page {{ $vehicles->currentPage() }} of {{ $vehicles->lastPage() }}
                </span>

                @if($vehicles->hasMorePages())
                <a href="{{ $vehicles->nextPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    {{ __('messages.common.next') }}
                </a>
                @else
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    {{ __('messages.common.next') }}
                </button>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Dealer Enquiry Dialog -->
<div id="dealer-enquiry-dialog" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="dealer-enquiry-dialog-title">
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        onclick="closeDealerEnquiryDialog()"
        aria-hidden="true"
    ></div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-background rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <div class="flex-1">
                    <h2 id="dealer-enquiry-dialog-title" class="text-xl font-semibold text-foreground">
                        {{ __('messages.pages.dealer_page.dealer_enquiry_title') }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('messages.pages.dealer_page.dealer_enquiry_description', ['dealer' => $dealer->owner?->name ?? __('messages.pages.dealer_page.dealer_label')]) }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeDealerEnquiryDialog()"
                    class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="{{ __('messages.dialogs.close_dialog') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Scrollable Content -->
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <!-- Dealer Information Card -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <h3 class="text-foreground text-sm font-semibold mb-3">{{ __('messages.pages.dealer_page.dealer_information') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 divide-y sm:divide-y-0 sm:gap-3 divide-border">
                        @if($dealerOwnerName)
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.pages.dealer_page.dealer_label') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $dealerOwnerName }}</p>
                        </div>
                        @endif
                        @if($publicCvr)
                        <div>
                            <span class="text-xs text-muted-foreground">CVR</span>
                            <p class="text-foreground font-medium text-sm">{{ $publicCvr }}</p>
                        </div>
                        @endif
                        @if($dealerAddressLine)
                        <div class="sm:col-span-2">
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.address') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $dealerAddressLine }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-foreground text-sm font-semibold mb-4">{{ __('messages.pages.dealer_page.your_details') }}</h3>
                    <form id="dealer-enquiry-form" class="space-y-4">
                        @csrf
                        <input type="hidden" name="dealer_slug" value="{{ $dealer->slug }}">

                        <!-- Error Display Container -->
                        <div id="dealer-form-errors" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                            <ul id="dealer-error-list" class="list-disc list-inside text-sm text-red-800"></ul>
                        </div>

                        <!-- Success Message -->
                        <div id="dealer-success-message" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3 mb-4">
                            <p class="text-sm text-green-800"></p>
                        </div>

                        <div class="space-y-2">
                            <label for="dealer-enquiry-name" class="text-sm font-medium leading-none">
                                {{ __('messages.pages.dealer_page.full_name') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="dealer-enquiry-name" 
                                name="name" 
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.name') }}"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="dealer-enquiry-email" class="text-sm font-medium leading-none">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="dealer-enquiry-email" 
                                name="email" 
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_email') }}"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="dealer-enquiry-phone" class="text-sm font-medium leading-none">
                                {{ __('messages.pages.dealer_page.phone_number') }}
                            </label>
                            <input 
                                type="tel" 
                                id="dealer-enquiry-phone" 
                                name="phone" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.pages.dealer_page.phone_optional') }}"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="dealer-enquiry-message" class="text-sm font-medium leading-none">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="dealer-enquiry-message" 
                                name="message" 
                                required
                                rows="5"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.pages.dealer_page.tell_us_enquiry') }}"
                            ></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button 
                                type="submit" 
                                id="dealer-submit-btn"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <span id="dealer-submit-text">{{ __('messages.pages.dealer_page.send_enquiry') }}</span>
                                <svg id="dealer-submit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            <button 
                                type="button"
                                onclick="closeDealerEnquiryDialog()"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                {{ __('messages.common.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

        const vehicleDetailUrl = (slug) => @json(rtrim(route('vehicle.detail', ['vehicle' => '__SLUG__']), '/')).replace('__SLUG__', encodeURIComponent(slug));
        const favoritesCheckUrl = (id) => @json(rtrim(route('favorites.check', ['vehicleId' => '__ID__']), '/')).replace('__ID__', encodeURIComponent(id));
        const favoritesDestroyUrl = (id) => @json(rtrim(route('favorites.destroy', ['vehicleId' => '__ID__']), '/')).replace('__ID__', encodeURIComponent(id));
        const favoritesStoreUrl = @json(route('favorites.store'));
        const vehiclePathPrefix = @json(rtrim((string) parse_url(route('vehicles'), PHP_URL_PATH), '/') . '/');
document.addEventListener('DOMContentLoaded', function() {
    // Dealer Enquiry Dialog Functions
    window.openDealerEnquiryDialog = function() {
        const dialog = document.getElementById('dealer-enquiry-dialog');
        if (dialog) {
            dialog.classList.remove('hidden');
            dialog.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            // Focus on first input
            const firstInput = dialog.querySelector('input[type="text"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    };

    window.closeDealerEnquiryDialog = function() {
        const dialog = document.getElementById('dealer-enquiry-dialog');
        if (dialog) {
            dialog.classList.add('hidden');
            dialog.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    };

    // Handle ESC key to close dialog
    const dialog = document.getElementById('dealer-enquiry-dialog');
    if (dialog) {
        dialog.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDealerEnquiryDialog();
            }
        });
    }

    // Dealer Enquiry Form
    const enquiryForm = document.getElementById('dealer-enquiry-form');
    if (enquiryForm) {
        enquiryForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('dealer-submit-btn');
            const submitText = document.getElementById('dealer-submit-text');
            const submitSpinner = document.getElementById('dealer-submit-spinner');
            const errorContainer = document.getElementById('dealer-form-errors');
            const errorList = document.getElementById('dealer-error-list');
            const successMessage = document.getElementById('dealer-success-message');

            // Hide previous messages
            if (errorContainer) errorContainer.classList.add('hidden');
            if (successMessage) successMessage.classList.add('hidden');
            if (errorList) errorList.innerHTML = '';

            // Disable submit button
            if (submitBtn) submitBtn.disabled = true;
            if (submitText) submitText.textContent = '{{ __('messages.pages.dealer_page.submitting') }}';
            if (submitSpinner) submitSpinner.classList.remove('hidden');

            // Get form data
            const formData = new FormData(enquiryForm);
            const bot = typeof window.bilskyenBotFields === 'function' ? await window.bilskyenBotFields() : {};
            const data = {
                name: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                message: formData.get('message'),
                ...bot,
            };

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const dealerSlug = formData.get('dealer_slug');

            try {
                const response = await fetch(`/dealer-${dealerSlug}/enquire`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok) {
                    // Show validation errors
                    if (result.errors && errorList) {
                        const errors = result.errors;
                        for (const field in errors) {
                            const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                            fieldErrors.forEach(error => {
                                const li = document.createElement('li');
                                li.textContent = error;
                                errorList.appendChild(li);
                            });
                        }
                        if (errorContainer) errorContainer.classList.remove('hidden');
                    } else {
                        let errorMsg = result.message || '{{ __('messages.pages.dealer_page.failed_to_submit_enquiry') }}';
                        if (typeof errorMsg === 'string' && errorMsg.startsWith('messages.')) {
                            errorMsg = '{{ __('messages.pages.dealer_page.failed_to_submit_enquiry') }}';
                        }
                        if (window.showSnackbar) {
                            window.showSnackbar(errorMsg, 'error');
                        } else if (errorList) {
                            errorList.innerHTML = `<li>${errorMsg}</li>`;
                            if (errorContainer) errorContainer.classList.remove('hidden');
                        }
                    }
                } else {
                    // Success — resolve message; never show raw translation keys
                    const defaultSuccessMsg = '{{ __('messages.pages.dealer_page.enquiry_submitted_successfully') }}';
                    let successMsg = result.message || defaultSuccessMsg;
                    if (typeof successMsg === 'string' && successMsg.startsWith('messages.')) {
                        successMsg = defaultSuccessMsg;
                    }
                    if (successMessage) {
                        successMessage.querySelector('p').textContent = successMsg;
                        successMessage.classList.remove('hidden');
                    }
                    
                    // Reset form
                    enquiryForm.reset();
                    
                    // Show snackbar
                    if (window.showSnackbar) {
                        window.showSnackbar(successMsg, 'success');
                    }

                    // Close dialog after 2 seconds
                    setTimeout(() => {
                        closeDealerEnquiryDialog();
                    }, 2000);
                }
            } catch (error) {
                console.error('Error submitting enquiry:', error);
                const errorMsg = '{{ __('messages.dialogs.error_occurred') }}';
                if (window.showSnackbar) {
                    window.showSnackbar(errorMsg, 'error');
                } else if (errorList) {
                    errorList.innerHTML = `<li>${errorMsg}</li>`;
                    if (errorContainer) errorContainer.classList.remove('hidden');
                }
            } finally {
                // Re-enable submit button
                if (submitBtn) submitBtn.disabled = false;
                if (submitText) submitText.textContent = '{{ __('messages.pages.dealer_page.send_enquiry') }}';
                if (submitSpinner) submitSpinner.classList.add('hidden');
            }
        });
    }

    // Search form - filter models by brand
    const brandSelect = document.getElementById('brand-select');
    const modelSelect = document.getElementById('model-select');
    
    if (brandSelect && modelSelect) {
        function updateModelDropdown() {
            const selectedBrandId = brandSelect.value;
            const modelOptions = modelSelect.querySelectorAll('option[data-brand-id]');
            const defaultOption = modelSelect.querySelector('option[value=""]');
            
            if (!selectedBrandId || selectedBrandId === '') {
                // No brand selected - disable model dropdown
                modelSelect.disabled = true;
                if (defaultOption) {
                    defaultOption.textContent = '{{ __('messages.pages.dealer_page.model') }}';
                }
                modelSelect.value = '';
                // Hide all model options
                modelOptions.forEach(option => {
                    option.style.display = 'none';
                });
            } else {
                // Brand selected - enable model dropdown
                modelSelect.disabled = false;
                if (defaultOption) {
                    defaultOption.textContent = '{{ __('messages.pages.dealer_page.all_models') }}';
                }
                
                // Show/hide model options based on brand
                modelOptions.forEach(option => {
                    const optionBrandId = option.getAttribute('data-brand-id');
                    if (optionBrandId === selectedBrandId) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Reset model selection if it doesn't match the brand
                if (modelSelect.value) {
                    const selectedModel = modelSelect.options[modelSelect.selectedIndex];
                    if (selectedModel && selectedModel.getAttribute('data-brand-id') !== selectedBrandId) {
                        modelSelect.value = '';
                    }
                }
            }
        }
        
        // Initialize on page load
        updateModelDropdown();
        
        // Update on brand change
        brandSelect.addEventListener('change', updateModelDropdown);
    }

    // Search form submission
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(searchForm);
            const params = new URLSearchParams();
            
            formData.forEach((value, key) => {
                if (value) {
                    params.append(key, value);
                }
            });
            
            window.location.href = `{{ route('dealer.show', $dealer->slug) }}?${params.toString()}`;
        });
    }

    // Toggle favorite function (from layouts/app.blade.php)
    if (typeof window.toggleFavorite === 'undefined') {
        window.toggleFavorite = async function(vehicleId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const heartIcon = event.currentTarget.querySelector('.heart-icon') || event.currentTarget;
            const path = heartIcon.querySelector('path');
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch(favoritesCheckUrl(vehicleId), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.status === 401) {
                    if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.favorites.login_to_save_favorites') }}', 'error');
                    }
                    setTimeout(() => {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                    return false;
                }

                const data = await response.json();
                const isFavorite = data.is_favorite || false;

                if (isFavorite) {
                    // Remove from favorites
                    const deleteResponse = await fetch(favoritesDestroyUrl(vehicleId), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (deleteResponse.ok) {
                        heartIcon.classList.remove('filled', 'text-red-500');
                        heartIcon.classList.add('text-blue-600');
                        if (path) path.removeAttribute('fill');
                        if (window.showSnackbar) {
                            window.showSnackbar('{{ __('messages.favorites.removed_from_favorites') }}', 'success');
                        }
                    }
                } else {
                    // Add to favorites
                    const addResponse = await fetch(favoritesStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ vehicle_id: vehicleId }),
                        credentials: 'same-origin'
                    });

                    if (addResponse.ok) {
                        const data = await addResponse.json().catch(() => ({}));
                        heartIcon.classList.add('filled');
                        heartIcon.classList.remove('text-blue-600', 'text-orange-600');
                        heartIcon.classList.add('text-red-500');
                        if (path) path.setAttribute('fill', 'currentColor');
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.messages.saved_to_favorites') }}', 'success');
                        }
                    } else {
                        if (addResponse.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('{{ __('messages.favorites.login_to_save_favorites') }}', 'error');
                            }
                            setTimeout(() => {
                                window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                            }, 1500);
                            return false;
                        }
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
                }
            }
            
            return false;
        };
    }
});
</script>

@push('styles')
<style>
    /* List view styles - Compact design matching card view styles */
    #vehicle-container[data-view="list"] {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        grid-template-columns: 1fr;
    }
    
    #vehicle-container[data-view="list"] .vehicle-item {
        display: flex;
        flex-direction: row;
        border: 1px solid hsl(var(--border));
        overflow: hidden;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    #vehicle-container[data-view="list"] .vehicle-dealer-label {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        z-index: 20;
        width: fit-content;
        pointer-events: none;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container {
        flex-shrink: 0;
        width: 200px;
        min-width: 200px;
        height: 150px;
        overflow: hidden;
        background-color: hsl(var(--muted) / 0.3);
        display: block;
        position: relative;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    #vehicle-container[data-view="list"] .vehicle-item > a {
        display: flex;
        flex-direction: row;
        flex: 1;
        min-width: 0;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        gap: 0;
        position: relative;
        min-height: 0;
    }
    
    #vehicle-container[data-view="list"] .vehicle-listing-header {
        flex-shrink: 0;
    }
    
    #vehicle-container[data-view="list"] .vehicle-listing-badges {
        flex: 1;
        align-items: center;
        align-content: center;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper h3 {
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.3;
        margin: 0;
        min-height: 0;
        color: hsl(var(--foreground));
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper .vehicle-listing-price {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
        color: hsl(var(--primary));
    }
    
    #vehicle-container[data-view="list"] .vehicle-item-footer {
        margin-top: auto;
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 0.5rem;
        padding: 1rem;
        min-width: min-content;
        width: auto;
        max-width: none;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.5rem;
        align-items: stretch;
        width: max-content;
        max-width: none;
    }

    #vehicle-container[data-view="list"] .vehicle-actions-section > a,
    #vehicle-container[data-view="list"] .vehicle-actions-section > .vehicle-card-enquire-btn {
        flex: 0 0 auto;
        width: auto;
        min-width: auto;
        max-width: none;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section button,
    #vehicle-container[data-view="list"] .vehicle-actions-section a button {
        height: 2.25rem;
        width: auto;
        min-width: auto;
        max-width: none;
        padding: 0 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: visible;
        box-sizing: border-box;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container .absolute {
        top: 0.5rem;
        right: 0.5rem;
        z-index: 10;
    }
    
    #vehicle-container[data-view="list"] .vehicle-image-container .absolute.top-2.left-2 {
        top: 0.5rem;
        left: 0.5rem;
    }
    
    /* Tablet and up */
    @media (min-width: 768px) {
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 240px;
            min-width: 240px;
            height: 180px;
        }
    }
    
    /* Large screens */
    @media (min-width: 1024px) {
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 280px;
            min-width: 280px;
            height: 200px;
        }
    }
    
    /* Mobile optimizations */
    @media (max-width: 640px) {
        #vehicle-container[data-view="list"] {
            gap: 0.75rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item {
            flex-direction: column;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item > a {
            flex-direction: column;
        }
        
        #vehicle-container[data-view="list"] .vehicle-image-container {
            width: 100%;
            min-width: 100%;
            height: 200px;
        }
        
        #vehicle-container[data-view="list"] .vehicle-content-wrapper {
            padding: 1rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-item-footer {
            padding: 0 1rem 1rem;
            min-width: 0;
            width: 100%;
            flex: 1 1 auto;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section {
            flex-direction: row;
            flex-wrap: wrap;
            width: 100%;
            max-width: 100%;
        }

        #vehicle-container[data-view="list"] .vehicle-actions-section > a,
        #vehicle-container[data-view="list"] .vehicle-actions-section > .vehicle-card-enquire-btn {
            flex: 1 1 calc(50% - 0.25rem);
            min-width: 0;
            max-width: 100%;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section button,
        #vehicle-container[data-view="list"] .vehicle-actions-section a button {
            width: 100%;
        }
    }
    
    /* View Toggle filter pill-style tabs */
    .view-toggle-label {
        border: none !important;
    }
    
    .view-toggle-label.bg-white {
        background-color: rgb(255 255 255) !important;
        color: hsl(var(--foreground)) !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
        
    .view-toggle-label.bg-gray-150 {
        background-color: rgb(236 237 240) !important;
        color: hsl(var(--muted-foreground)) !important;
    }
    
    .view-toggle-label.bg-gray-150:hover {
        color: hsl(var(--foreground)) !important;
    }
        
    .bg-gray-150 {
        background-color: rgb(236 237 240) !important;
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    const vehicleContainer = document.getElementById('vehicle-container');
    const viewToggleRadios = document.querySelectorAll('input[name="view-toggle"]');
    
    // View state
    let currentView = localStorage.getItem('dealerVehicleView') || 'card';
    
    // Check if device is mobile (screen width <= 640px)
    function isMobile() {
        return window.innerWidth <= 640;
    }
    
    // Force card view on mobile
    if (isMobile()) {
        currentView = 'card';
        localStorage.setItem('dealerVehicleView', 'card');
    }
    
    // Format currency helper
    function formatCurrency(amount) {
        if (amount === null || amount === undefined) {
            return 'N/A';
        }
        return new Intl.NumberFormat('da-DK', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(amount) + ' kr.';
    }

    const listingLocale = document.documentElement.lang || '{{ app()->getLocale() }}' || 'da';

    function formatListingTitle(title) {
        if (!title) return '';
        return String(title).toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function formatMonthYear(dateStr) {
        if (!dateStr) return '';
        if (typeof dateStr === 'string' && dateStr.match(/^[A-Z][a-z]{2} \d{4}$/)) {
            return dateStr;
        }
        try {
            return new Date(dateStr).toLocaleDateString(listingLocale, { month: 'short', year: 'numeric' });
        } catch (e) {
            return dateStr;
        }
    }

    function formatListingLocation(vehicle) {
        const parts = [];
        const pc = vehicle.postcode || vehicle.seller_postcode;
        const city = vehicle.city || vehicle.seller_city;
        const addr = vehicle.address || vehicle.seller_address;
        if (pc) parts.push(String(pc).trim());
        if (city) parts.push(String(city).trim());
        else if (addr) parts.push(String(addr).trim());
        return parts.filter(Boolean).join(' ');
    }
    
    // Render single vehicle list item
    function renderVehicleListItem(vehicle) {
        const rawImage = vehicle.thumbnail_url || vehicle.image_url || '';
        const imageUrl = rawImage.includes('placeholder-vehicle') ? '' : rawImage;
        const titleText = formatListingTitle(vehicle.title || '');
        const locationText = formatListingLocation(vehicle);
        
        // Build badges
        const badges = [];
        const km = vehicle.km_driven != null && vehicle.km_driven !== '' ? vehicle.km_driven : (vehicle.mileage != null && vehicle.mileage !== '' ? vehicle.mileage : null);
        if (km != null) {
            badges.push(`${new Intl.NumberFormat('da-DK').format(Number(km))} km`);
        }
        if (vehicle.engine_power_hp) {
            badges.push(`${Math.round(vehicle.engine_power_hp)} HP`);
        }
        if (vehicle.first_registration_date) {
            badges.push(formatMonthYear(vehicle.first_registration_date));
        }
        if (vehicle.fuel_type_name) {
            badges.push(vehicle.fuel_type_name);
        }
        if (vehicle.gear_type_name) {
            badges.push(vehicle.gear_type_name);
        }
        
        return `
            <div class="vehicle-item relative bg-card rounded-lg overflow-hidden">
                <a href="${vehicleDetailUrl(vehicle.slug)}" class="flex flex-1 flex-col min-w-0">
                    <!-- Vehicle Image -->
                    <div class="vehicle-image-container relative aspect-[2/1.5] overflow-hidden">
                        ${imageUrl ? `<img src="${imageUrl}" alt="${titleText}" width="800" height="600" loading="lazy" decoding="async" class="block h-full w-full object-cover" />` : `<div class="h-full w-full bg-muted" aria-hidden="true"></div>`}
                        
                        <!-- Heart Icon - Top Right -->
                        <button type="button" class="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(${vehicle.id}, event); return false;" aria-label="{{ __('messages.forms.add_to_favorites') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-600 hover:text-red-500 transition-colors heart-icon" data-vehicle-id="${vehicle.id}">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Vehicle Content -->
                    <div class="vehicle-content-wrapper flex flex-1 flex-col px-3 pt-3 min-h-0">
                        <div class="vehicle-listing-header flex shrink-0 flex-col gap-1 text-left">
                            <h3 class="text-sm font-semibold leading-snug truncate line-clamp-1">
                                ${titleText}
                            </h3>
                            ${vehicle.variant_name || vehicle.version ? `
                            <p class="text-muted-foreground text-xs font-normal line-clamp-1">${vehicle.variant_name || vehicle.version}</p>
                            ` : ''}
                            <p class="vehicle-listing-price text-lg font-bold">
                                ${formatCurrency(vehicle.price)}
                            </p>
                        </div>
                        <div class="vehicle-listing-badges flex flex-1 flex-wrap content-center items-center min-h-[2rem] gap-1 py-2 text-xs font-light">
                            ${badges.map(badge => `<span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${badge}</span>`).join('')}
                        </div>
                    </div>
                </a>
                
                <!-- Card Footer -->
                <div class="vehicle-item-footer mt-auto flex shrink-0 flex-col gap-2 px-3 pb-3" onclick="event.stopPropagation()">
                    <div class="vehicle-listing-location min-h-[1.25rem]">
                        ${locationText ? `
                        <div class="flex items-center justify-start gap-2 text-xs text-muted-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <span class="truncate text-left" title="${locationText}">${locationText}</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="vehicle-actions-section flex w-full flex-row items-center gap-2">
                        <a href="${vehicleDetailUrl(vehicle.slug)}" class="min-w-0 flex-[2]" onclick="event.stopPropagation()">
                            <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 hover:shadow-md disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                {{ __('messages.pages.vehicles.view_details') }}
                            </button>
                        </a>
                        <button 
                            type="button"
                            onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '${vehicle.slug}')"
                            class="flex-1 inline-flex h-9 w-full min-w-0 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                        >
                            {{ __('messages.pages.vehicles.enquire') }}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Store original card HTML for restoration
    const originalCardHTML = new Map();
    
    // Convert existing cards to list view
    function convertCardsToList() {
        if (!vehicleContainer || currentView !== 'list') return;
        
        const cards = vehicleContainer.querySelectorAll('.flex.flex-col.rounded-2xl');
        cards.forEach(card => {
            // Check if already converted
            if (card.classList.contains('vehicle-item')) return;
            
            // Store original HTML before conversion (use slug as key; link now contains slug)
            const link = card.querySelector('a[href*="/biler/"]');
            const vehicleSlug = link?.getAttribute('href')?.match(/\/biler\/([^/]+)/)?.[1] || '';
            const vehicleId = card.querySelector('[data-vehicle-id]')?.getAttribute('data-vehicle-id') || '';
            if (vehicleSlug && !originalCardHTML.has(vehicleSlug)) {
                originalCardHTML.set(vehicleSlug, card.outerHTML);
            }
            
            // Extract vehicle data from the card
            const img = card.querySelector('img');
            const titleEl = card.querySelector('h3');
            const versionEl = card.querySelector('.text-muted-foreground.text-xs.font-normal');
            const priceEl = card.querySelector('.text-lg.font-bold');
            const badgeElements = Array.from(card.querySelectorAll('.inline-flex.items-center.rounded-md.border'));
            
            if (!vehicleSlug) return;
            
            // Parse price
            let price = null;
            if (priceEl) {
                const priceText = priceEl.textContent.trim();
                price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
            }
            
            // Parse badges
            let mileage = null, km_driven = null, engine_power_hp = null, first_registration_date = null, fuel_type_name = null, gear_type_name = null;
            
            badgeElements.forEach(badge => {
                const badgeText = badge.textContent.trim();
                if (badgeText.includes('km')) {
                    const kmValue = parseFloat(badgeText.replace(/[^0-9.]/g, ''));
                    mileage = kmValue;
                    km_driven = kmValue;
                } else if (badgeText.includes('HP')) {
                    engine_power_hp = parseFloat(badgeText.replace(/[^0-9.]/g, ''));
                } else if (badgeText.match(/^[A-Z][a-z]{2} \d{4}$/)) {
                    first_registration_date = badgeText;
                } else if (!badgeText.includes('km') && !badgeText.includes('HP') && !badgeText.match(/^[A-Z][a-z]{2} \d{4}$/)) {
                    // Could be fuel type or gear type
                    if (!fuel_type_name) {
                        fuel_type_name = badgeText;
                    } else if (!gear_type_name) {
                        gear_type_name = badgeText;
                    }
                }
            });
            
            // Create vehicle object
            const vehicle = {
                id: vehicleId,
                slug: vehicleSlug,
                title: titleEl ? titleEl.textContent.trim() : '',
                version: versionEl ? versionEl.textContent.trim() : '',
                price: price,
                thumbnail_url: img && img.src && !img.src.includes('placeholder-vehicle') ? img.src : '',
                mileage: mileage,
                km_driven: km_driven,
                engine_power_hp: engine_power_hp,
                first_registration_date: first_registration_date,
                fuel_type_name: fuel_type_name,
                gear_type_name: gear_type_name
            };
            
            // Replace card with list item
            const listItem = document.createElement('div');
            listItem.innerHTML = renderVehicleListItem(vehicle);
            const newElement = listItem.firstElementChild;
            if (newElement) {
                card.replaceWith(newElement);
            }
        });
    }
    
    // Convert list items back to cards
    function convertListToCards() {
        if (!vehicleContainer || currentView !== 'card') return;
        
        const listItems = vehicleContainer.querySelectorAll('.vehicle-item');
        listItems.forEach(listItem => {
            // Extract vehicle slug from list item (link now contains slug)
            const link = listItem.querySelector('a[href*="/biler/"]');
            const vehicleSlug = link ? link.getAttribute('href').match(/\/biler\/([^/]+)/)?.[1] : '';
            
            if (!vehicleSlug) return;
            
            // Restore original card HTML if available
            if (originalCardHTML.has(vehicleSlug)) {
                const originalHTML = originalCardHTML.get(vehicleSlug);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = originalHTML;
                const restoredCard = tempDiv.firstElementChild;
                if (restoredCard) {
                    listItem.replaceWith(restoredCard);
                }
            } else {
                // If original HTML not available, extract data and render card
                const img = listItem.querySelector('img');
                const titleEl = listItem.querySelector('.vehicle-content-wrapper h3, .vehicle-content-wrapper span');
                const priceEl = listItem.querySelector('.text-sm.font-semibold');
                const versionEl = listItem.querySelector('.text-muted-foreground');
                const badgeElements = Array.from(listItem.querySelectorAll('.inline-flex.items-center.rounded-md.border'));
                
                // This is a fallback - ideally we should always have original HTML
                // For now, just remove list-specific classes and restore card structure
                listItem.classList.remove('vehicle-item', 'relative', 'bg-card', 'rounded-lg', 'overflow-hidden');
                listItem.classList.add('flex', 'flex-col', 'rounded-2xl', 'bg-card', 'overflow-hidden', 'p-0', 'cursor-pointer', 'h-full', 'shadow-sm');
            }
        });
    }
    
    // Update view toggle button styles
    function updateViewToggleStyles() {
        viewToggleRadios.forEach(radio => {
            const label = radio.closest('.view-toggle-label');
            if (label) {
                if (radio.checked) {
                    label.classList.add('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                    label.classList.remove('bg-gray-150', 'text-muted-foreground');
                } else {
                    label.classList.remove('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                    label.classList.add('bg-gray-150', 'text-muted-foreground');
                }
            }
        });
    }
    
    // View toggle functionality
    function setView(view) {
        if (!vehicleContainer || (view !== 'card' && view !== 'list')) return;
        
        // Force card view on mobile devices
        if (isMobile() && view === 'list') {
            view = 'card';
        }
        
        currentView = view;
        localStorage.setItem('dealerVehicleView', view);
        
        // Update radio button selection
        viewToggleRadios.forEach(radio => {
            radio.checked = radio.value === view;
        });
        
        // Hide container during conversion to prevent visual transition
        vehicleContainer.style.opacity = '0';
        vehicleContainer.style.transition = 'opacity 0.1s';
        
        // Use requestAnimationFrame to ensure the hide happens before conversion
        requestAnimationFrame(() => {
            // Update container data attribute and classes
            vehicleContainer.setAttribute('data-view', view);
            if (view === 'list') {
                vehicleContainer.classList.remove('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-4');
                vehicleContainer.classList.add('flex', 'flex-col');
                // Convert cards to list view synchronously
                convertCardsToList();
            } else {
                vehicleContainer.classList.add('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-4');
                vehicleContainer.classList.remove('flex', 'flex-col');
                // Convert list items back to cards synchronously
                convertListToCards();
            }
            
            // Show container after conversion is complete
            requestAnimationFrame(() => {
                vehicleContainer.style.opacity = '1';
                // Remove transition after showing to prevent it from affecting future changes
                setTimeout(() => {
                    vehicleContainer.style.transition = '';
                }, 100);
            });
        });
        
        // Update view toggle button styles
        updateViewToggleStyles();
    }
    
    // View toggle radio button change handlers
    viewToggleRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            // Prevent switching to list view on mobile
            if (isMobile() && radio.value === 'list') {
                radio.checked = false;
                const cardRadio = document.querySelector('input[name="view-toggle"][value="card"]');
                if (cardRadio) cardRadio.checked = true;
                updateViewToggleStyles();
                return;
            }
            
            if (radio.checked) {
                const newView = radio.value;
                setView(newView);
            }
        });
    });
    
    // Handle window resize to force card view if switching to mobile
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (isMobile() && currentView === 'list') {
                setView('card');
            }
        }, 250);
    });
    
    // Store original card HTML on page load
    function storeOriginalCards() {
        if (!vehicleContainer) return;
        const cards = vehicleContainer.querySelectorAll('.flex.flex-col.rounded-2xl');
        cards.forEach(card => {
            const link = card.querySelector('a[href*="/biler/"]');
            const vehicleSlug = link ? link.getAttribute('href').match(/\/biler\/([^/]+)/)?.[1] : '';
            if (vehicleSlug && !originalCardHTML.has(vehicleSlug)) {
                originalCardHTML.set(vehicleSlug, card.outerHTML);
            }
        });
    }
    
    // Initialize view on page load
    if (currentView) {
        // Store original cards first
        storeOriginalCards();
        
        // Force card view on mobile
        if (isMobile()) {
            currentView = 'card';
            localStorage.setItem('dealerVehicleView', 'card');
        }
        setView(currentView);
    } else {
        // Store original cards even if no view preference
        storeOriginalCards();
    }
    
    // Initialize view toggle styles
    updateViewToggleStyles();
})();
</script>
@endpush
@endsection
