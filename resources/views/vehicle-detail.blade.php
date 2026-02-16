@extends('layouts.app')

@section('title', __('messages.pages.vehicles.detail.page_title') . ' | Bilskyen')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<style>
    /* Fix GLightbox z-index and description issues */
    .gslide-description {
        display: none !important;
    }
    
    /* Ensure only one image is visible at a time */
    .gslide {
        opacity: 0;
        visibility: hidden;
        z-index: 1;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    .gslide.current {
        opacity: 1;
        visibility: visible;
        z-index: 10;
    }
    
    .gslide.prev,
    .gslide.next {
        opacity: 0;
        visibility: hidden;
        z-index: 1;
    }
    
    .gslide-image {
        z-index: 1;
        position: relative;
    }
    
    .gslide-inner {
        z-index: 1;
        position: relative;
    }
    
    /* Ensure proper stacking during transitions */
    .glightbox-container {
        z-index: 9999;
    }
    
    .glightbox-opened .gslide {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
    }
    
    .glightbox-opened .gslide.current {
        opacity: 1;
        visibility: visible;
        z-index: 100;
        position: relative;
    }
    
    /* Hide all slides except current during navigation */
    .glightbox-opened .gslide:not(.current) {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    .detail-section {
        /* border: 1px solid var(--border); */
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .detail-label {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        font-weight: 500;
    }
    
    .detail-value {
        color: var(--foreground);
        /* font-weight: 500; */
    }
    
    .equipment-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .equipment-chip svg {
        flex-shrink: 0;
        color: var(--primary);
        width: 14px;
        height: 14px;
    }
</style>
@endpush

@php
    use App\Helpers\FormatHelper;
    $details = $vehicle->details;
@endphp

@section('content')
<div class="container space-y-8 py-6">
    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                    <h1 class="text-foreground text-3xl font-bold tracking-tight">
                    {{ $vehicle->title }}
                    </h1>
                {{-- <p class="text-muted-foreground text-xl">
                    Registration: <span class="text-foreground font-mono">{{ $vehicle->registration }}</span>
                </p> --}}
            </div>
            <div class="flex flex-col items-start gap-3 lg:items-end">
                <p class="text-3xl font-bold text-primary">
                    {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                </p>
                
            </div>
        </div>
        <div class="border-t border-border"></div>
    </div>

    <!-- Main Content Grid -->
    @php
        $contactUser = null;
        $contactWhatsApp = null;
        $contactEmail = null;
        $dealerOwner = null;
        $dealerPhone = null;
        if ($vehicle->dealer && $vehicle->dealer->owner) {
            $dealerOwner = $vehicle->dealer->owner;
            $contactUser = $dealerOwner;
            $contactWhatsApp = $dealerOwner->whatsapp_number ?? $dealerOwner->phone ?? null;
            $contactEmail = $dealerOwner->email ?? null;
            if ($details && $details->seller_phone) {
                $dealerPhone = $details->seller_phone;
            } elseif ($dealerOwner && $dealerOwner->phone) {
                $dealerPhone = $dealerOwner->phone;
            }
        } elseif ($vehicle->user) {
            $contactUser = $vehicle->user;
            $contactWhatsApp = $vehicle->user->whatsapp_number ?? $vehicle->user->phone ?? null;
            $contactEmail = $vehicle->user->email ?? null;
        }
    @endphp
    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Vehicle Details - Left Column -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Images Carousel Section -->
            @if($vehicle->images && $vehicle->images->count() > 0)
            <div class="relative">
                <div class="absolute top-2 left-2 z-10 flex items-center gap-2 bg-white/70 px-2 py-2 rounded-md">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-4 w-4">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                        <circle cx="9" cy="9" r="2"></circle>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                    </svg>
                    <h2 class="text-foreground text-xs font-medium">
                        {{ __('messages.pages.vehicles.detail.photos') }} ({{ $vehicle->images->count() }})
                    </h2>
                </div>

                <div class="relative">
                    <div class="embla overflow-hidden" id="vehicle-images-carousel">
                        <div class="embla__container flex">
                            @foreach($vehicle->images as $index => $image)
                            <div class="embla__slide flex-shrink-0 basis-full md:basis-1/2 lg:basis-1/2">
                                <a href="{{ $image->image_url }}" class="glightbox" data-gallery="vehicle-gallery">
                                    <div class="border-border bg-muted/50 relative aspect-[4/3] cursor-pointer overflow-hidden rounded-lg border transition-all hover:shadow-md mr-4">
                                        <img
                                            src="{{ $image->image_url }}"
                                            alt="{{ __('messages.pages.vehicles.detail.photos') }} {{ $index + 1 }}"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @if($vehicle->images->count() > 2)
                    <button class="embla__prev absolute left-2 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="{{ __('messages.pages.vehicles.detail.previous_slide') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button class="embla__next absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="{{ __('messages.pages.vehicles.detail.next_slide') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
            @endif

            <!-- Dealer Information (mobile only - below photos) -->
            @if($vehicle->dealer)
            <div class="block lg:hidden">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-foreground">
                            <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ __('messages.pages.vehicles.detail.dealer_information') }}
                        </h2>
                    </div>
                    <div class="space-y-3">
                        @if($dealerOwner && $dealerOwner->name)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ ucfirst($dealerOwner->name) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.contact_name') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_address)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_address }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.address') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_postcode)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_postcode }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.postal_code') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($dealerPhone)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div class="dealer-phone-display-mobile hidden">
                                        <p class="text-sm font-medium text-foreground">
                                            <a href="tel:{{ $dealerPhone }}" class="hover:underline">
                                                {{ $dealerPhone }}
                                            </a>
                                        </p>
                                    </div>
                                    <button 
                                        type="button"
                                        onclick="showDealerPhoneAndCreateLead({{ $vehicle->id }}, event)"
                                        class="show-dealer-phone-btn-mobile text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                        data-vehicle-id="{{ $vehicle->id }}"
                                        data-phone="{{ $dealerPhone }}"
                                    >
                                        {{ __('messages.pages.vehicles.detail.show_phone_number') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.phone') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($contactEmail && $vehicle->dealer)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <div class="flex-1">
                                    <button 
                                        type="button"
                                        onclick="handleEmailClick({{ $vehicle->id }}, event)"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded dealer-email-btn-mobile"
                                        data-email="{{ $contactEmail }}"
                                    >
                                        {{ __('messages.pages.vehicles.detail.send_email') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.email') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

        <!-- Basic Information Section -->
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.basic_information') }}</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.title_label') }}</span>
                    <span class="detail-value">{{ $vehicle->title }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.price') }}</span>
                    <span class="detail-value text-primary">{{ FormatHelper::formatCurrency($vehicle->price ?? null) }}</span>
                    </div>
                @if($vehicle->listing_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.listing_type') }}</span>
                    <span class="detail-value">{{ $vehicle->listing_type_name }}</span>
                </div>
                @endif
                </div>
            </div>

        <!-- Vehicle Specifications Section -->
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.vehicle_specifications') }}</h2>
            <div class="detail-grid">
                @if($vehicle->brand_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.brand') }}</span>
                    <span class="detail-value">{{ $vehicle->brand_name }}</span>
                </div>
                @endif
                @if($vehicle->model_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.model') }}</span>
                    <span class="detail-value">{{ $vehicle->model_name }}</span>
                </div>
                @endif
                @if($vehicle->model_year_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.model_year') }}</span>
                    <span class="detail-value">{{ $vehicle->model_year_name }}</span>
                </div>
                @endif
                @if($vehicle->fuel_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.fuel_type') }}</span>
                    <span class="detail-value">{{ $vehicle->fuel_type_name }}</span>
                </div>
                @endif
                @if($vehicle->engine_power_hp)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.engine_power') }}</span>
                    <span class="detail-value">{{ number_format($vehicle->engine_power_hp, 0) }} HP</span>
                </div>
                @endif
                @if($vehicle->mileage)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.mileage_km') }}</span>
                    <span class="detail-value">{{ number_format($vehicle->mileage) }} km</span>
                    </div>
                @endif
                @if($vehicle->km_driven)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.kilometers_driven') }}</span>
                    <span class="detail-value">{{ number_format($vehicle->km_driven) }} km</span>
                    </div>
                    @endif
                @if($vehicle->battery_capacity)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.battery_capacity') }}</span>
                    <span class="detail-value">{{ $vehicle->battery_capacity }} kWh</span>
                    </div>
                @endif
                @if($vehicle->range_km)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.range') }}</span>
                    <span class="detail-value">{{ number_format($vehicle->range_km) }} km</span>
                </div>
                @endif
                @if($vehicle->charging_type)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.charging_type') }}</span>
                    <span class="detail-value">{{ $vehicle->charging_type }}</span>
                </div>
                @endif
                @if($vehicle->towing_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.towing_weight') }}</span>
                    <span class="detail-value">{{ number_format($vehicle->towing_weight) }} kg</span>
                    </div>
                @endif
                @if($vehicle->ownership_tax)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.owner_tax') }}</span>
                    <span class="detail-value">{{ FormatHelper::formatCurrency($vehicle->ownership_tax ?? null) }}</span>
                    </div>
                @endif
                @if($details && $details->annual_tax)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.annual_tax') }}</span>
                    <span class="detail-value">{{ FormatHelper::formatCurrency($details->annual_tax ?? null) }}</span>
                </div>
                @endif
                @if($vehicle->first_registration_date)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.first_registration_date') }}</span>
                    <span class="detail-value">{{ $vehicle->first_registration_date->format('F j, Y') }}</span>
                </div>
                @endif
                </div>
            </div>

        @if($details)
        <!-- Detailed Specifications Section -->
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.detailed_specifications') }}</h2>
            <div class="detail-grid">
                @if($details->description)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.description') }}</span>
                    <p class="detail-value whitespace-pre-wrap text-sm">{{ $details->description }}</p>
                </div>
                @endif
                @if($details->type_name_resolved)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.type') }}</span>
                    <span class="detail-value">{{ $details->type_name_resolved }}</span>
                </div>
                @endif
                @if($details->use_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.use') }}</span>
                    <span class="detail-value">{{ $details->use_name }}</span>
                </div>
                @endif
                @if($details->color_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.color') }}</span>
                    <span class="detail-value">{{ $details->color_name }}</span>
                </div>
                @endif
                @if($details->body_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.body_type') }}</span>
                    <span class="detail-value">{{ $details->body_type_name }}</span>
                </div>
                @endif
                @if($details->variant && $details->variant->name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.variant') }}</span>
                    <span class="detail-value">{{ $details->variant->name }}</span>
                </div>
                @endif
                @if($details->price_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.price_type') }}</span>
                    <span class="detail-value">{{ $details->price_type_name }}</span>
                </div>
                @endif
                @if($details->condition_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.condition') }}</span>
                    <span class="detail-value">{{ $details->condition_name }}</span>
                </div>
                @endif
                @if($details->gear_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.gear_type') }}</span>
                    <span class="detail-value">{{ $details->gear_type_name }}</span>
                </div>
                @endif
                @if($details->transmission_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.transmission') }}</span>
                    <span class="detail-value">{{ $details->transmission_name }}</span>
                </div>
                @endif
                @if($details->sales_type_name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.sales_type') }}</span>
                    <span class="detail-value">{{ $details->sales_type_name }}</span>
                </div>
                @endif
                @if($details->servicebog && $details->servicebog !== 'Default')
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.service_book') }}</span>
                    <span class="detail-value">{{ $details->servicebog }}</span>
                </div>
                @endif
                @if($vehicle->version)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.version') }}</span>
                    <span class="detail-value">{{ $vehicle->version }}</span>
                </div>
                @endif
                @if($details->vin_location)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.vin_location') }}</span>
                    <span class="detail-value">{{ $details->vin_location }}</span>
                </div>
                @endif
                @if($details->total_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.total_weight') }}</span>
                    <span class="detail-value">{{ number_format($details->total_weight) }} kg</span>
                </div>
                @endif
                @if($details->vehicle_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.vehicle_weight') }}</span>
                    <span class="detail-value">{{ number_format($details->vehicle_weight) }} kg</span>
                </div>
                @endif
                @if($details->technical_total_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.technical_total_weight') }}</span>
                    <span class="detail-value">{{ number_format($details->technical_total_weight) }} kg</span>
                </div>
                @endif
                @if($details->minimum_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.minimum_weight') }}</span>
                    <span class="detail-value">{{ number_format($details->minimum_weight) }} kg</span>
                </div>
                @endif
                @if($details->gross_combination_weight)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.gross_combination_weight') }}</span>
                    <span class="detail-value">{{ number_format($details->gross_combination_weight) }} kg</span>
                </div>
                @endif
                @if($details->towing_weight_brakes)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.towing_weight_brakes') }}</span>
                    <span class="detail-value">{{ number_format($details->towing_weight_brakes) }} kg</span>
                </div>
                @endif
                @if($details->engine_displacement)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.engine_displacement') }}</span>
                    <span class="detail-value">{{ number_format($details->engine_displacement) }} cc</span>
                </div>
                @endif
                @if($details->engine_code)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.engine_code') }}</span>
                    <span class="detail-value font-mono text-sm">{{ $details->engine_code }}</span>
                </div>
                @endif
                @if($details->engine_cylinders)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.engine_cylinders') }}</span>
                    <span class="detail-value">{{ $details->engine_cylinders }}</span>
                </div>
                @endif
                @if($details->doors)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.doors_min') }}</span>
                    <span class="detail-value">{{ $details->doors }}</span>
                </div>
                @endif
                @if($details->minimum_seats)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.minimum_seats') }}</span>
                    <span class="detail-value">{{ $details->minimum_seats }}</span>
                </div>
                @endif
                @if($details->maximum_seats)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.maximum_seats') }}</span>
                    <span class="detail-value">{{ $details->maximum_seats }}</span>
                </div>
                @endif
                @if($details->top_speed)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.top_speed') }}</span>
                    <span class="detail-value">{{ number_format($details->top_speed) }} km/h</span>
                </div>
                @endif
                @if($vehicle->fuel_efficiency)
                <div class="detail-item">
                    @php
                        $fuelTypeId = $vehicle->fuel_type_id;
                        $electricFuelTypes = [3, 7]; // Electric, El
                        $hybridFuelTypes = [4, 5]; // Hybrid, Plug-in Hybrid
                        
                        if ($fuelTypeId && in_array($fuelTypeId, $electricFuelTypes)) {
                            $label = __('messages.pages.vehicles.detail.electric_range');
                            $unit = 'km';
                            $value = number_format($vehicle->fuel_efficiency, 0);
                        } elseif ($fuelTypeId && in_array($fuelTypeId, $hybridFuelTypes)) {
                            $label = __('messages.pages.vehicles.detail.electric_range_km_l');
                            $unit = 'km';
                            $value = number_format($vehicle->fuel_efficiency, 2);
                        } else {
                            $label = __('messages.pages.vehicles.detail.fuel_efficiency');
                            $unit = 'km/l';
                            $value = number_format($vehicle->fuel_efficiency, 2);
                        }
                    @endphp
                    <span class="detail-label">{{ $label }}</span>
                    <span class="detail-value">{{ $value }} {{ $unit }}</span>
                </div>
                @endif
                @if($details->airbags)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.airbags') }}</span>
                    <span class="detail-value">{{ $details->airbags }}</span>
                </div>
                @endif
                @if($details->ncap_five !== null)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.ncap_five_star') }}</span>
                    <span class="detail-value">{{ $details->ncap_five ? __('messages.pages.vehicles.detail.yes') : __('messages.pages.vehicles.detail.no') }}</span>
                </div>
                @endif
                @if($details->integrated_child_seats)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.integrated_child_seats') }}</span>
                    <span class="detail-value">{{ $details->integrated_child_seats }}</span>
                </div>
                @endif
                @if($details->seat_belt_alarms)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.seat_belt_alarms') }}</span>
                    <span class="detail-value">{{ $details->seat_belt_alarms }}</span>
                </div>
                @endif
                @if($details->euronom && $details->euronom->name)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.forms.euro_norm') }}</span>
                    <span class="detail-value">{{ $details->euronom->name }}</span>
                    </div>
                @endif
                @if($details->wheels)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.wheels') }}</span>
                    <span class="detail-value">{{ $details->wheels }}</span>
                    </div>
                @endif
                @if($details->axles)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.axles') }}</span>
                    <span class="detail-value">{{ $details->axles }}</span>
                        </div>
                @endif
                @if($details->drive_axles)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.drive_axles') }}</span>
                    <span class="detail-value">{{ $details->drive_axles }}</span>
                    </div>
                    @endif
                @if($details->wheelbase)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.wheelbase') }}</span>
                    <span class="detail-value">{{ number_format($details->wheelbase) }} mm</span>
                </div>
                @endif
                @if($details->category)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.category_string') }}</span>
                    <span class="detail-value">{{ $details->category }}</span>
                </div>
                @endif
                @if($details->extra_equipment)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.extra_equipment') }}</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->extra_equipment }}</p>
                    </div>
                @endif
                @if($details->dispensations)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.dispensations') }}</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->dispensations }}</p>
                </div>
                @endif
                @if($details->permits)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.permits') }}</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->permits }}</p>
            </div>
            @endif
            </div>
        </div>

        <!-- Registration & Status Section -->
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.registration_status') }}</h2>
            <div class="detail-grid">
                @if($details->registration_status)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.registration_status_label') }}</span>
                    <span class="detail-value">{{ $details->registration_status }}</span>
                </div>
                @endif
                @if($details->registration_status_updated_date)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.registration_status_updated_date') }}</span>
                    <span class="detail-value">{{ $details->registration_status_updated_date->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->expire_date)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.expire_date') }}</span>
                    <span class="detail-value">{{ $details->expire_date->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->status_updated_date)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.status_updated_date') }}</span>
                    <span class="detail-value">{{ $details->status_updated_date->format('F j, Y') }}</span>
                </div>
                @endif
                </div>
            </div>

        <!-- Inspection Details Section -->
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.inspection_details') }}</h2>
            <div class="detail-grid">
                @if($details->last_inspection_date)
                    <div class="detail-item">
                        <span class="detail-label">{{ __('messages.pages.vehicles.detail.last_inspection_date') }}</span>
                        <span class="detail-value">{{ $details->last_inspection_date->format('F j, Y') }}</span>
                    </div>
                @endif
                @if($details->last_inspection_result)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.last_inspection_result') }}</span>
                    <span class="detail-value">{{ $details->last_inspection_result }}</span>
                </div>
                @endif
                @if($details->last_inspection_odometer)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.last_inspection_odometer') }}</span>
                    <span class="detail-value">{{ number_format($details->last_inspection_odometer) }} km</span>
                    </div>
                    @endif
                </div>
            </div>

        <!-- Leasing Information Section -->
        @if($details->leasing_period_start || $details->leasing_period_end)
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.leasing_information') }}</h2>
            <div class="detail-grid">
                @if($details->leasing_period_start)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.leasing_period_start') }}</span>
                    <span class="detail-value">{{ $details->leasing_period_start->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->leasing_period_end)
                <div class="detail-item">
                    <span class="detail-label">{{ __('messages.pages.vehicles.detail.leasing_period_end') }}</span>
                    <span class="detail-value">{{ $details->leasing_period_end->format('F j, Y') }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endif

        <!-- Equipment & Features Section -->
        @if($vehicle->equipment && $vehicle->equipment->count() > 0)
        <div class="detail-section bg-gray-50">
            <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.equipment_features') }}</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($vehicle->equipment as $equip)
                <div class="equipment-chip">
                    {{-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg> --}}
                    <span>{{ $equip->name }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">

             <!-- Pricing -->
             <div class="rounded-lg bg-primary p-6">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-primary-foreground">
                        <path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-primary-foreground">
                        {{ __('messages.pages.vehicles.detail.pricing') }}
                    </h2>
                </div>
                <div class="space-y-2">
                    <p class="text-3xl font-bold text-primary-foreground">
                        {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                    </p>
                    <p class="text-sm text-primary-foreground">
                        {{ __('messages.pages.vehicles.detail.listed_price') }}
                    </p>
                </div>
            </div>

            <!-- Contact Actions -->
            @if($contactUser)
            <div class="rounded-lg bg-gray-50 p-6 border border-border">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-foreground">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-foreground">
                        {{ __('messages.pages.vehicles.detail.contact_actions') }}
                    </h2>
                </div>
                <div class="space-y-3">
                    <!-- Enquiry Form Button -->
                    <button 
                        type="button"
                        onclick="openEnquiryDialog('enquiry', {{ $vehicle->id }})"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        {{ __('messages.pages.vehicles.detail.send_enquiry') }}
                    </button>

                    <!-- WhatsApp Button -->
                    @if($contactWhatsApp)
                    <button 
                        type="button"
                        onclick="handleWhatsAppClick({{ $vehicle->id }}, event)"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-green-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                        id="whatsapp-btn-{{ $vehicle->id }}"
                        data-whatsapp="{{ $contactWhatsApp }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        <span class="whatsapp-btn-text">{{ __('messages.pages.vehicles.detail.contact_via_whatsapp') }}</span>
                    </button>
                    @endif

                    <!-- Test Drive Request Button -->
                    <button 
                        type="button"
                        onclick="openEnquiryDialog('test-drive', {{ $vehicle->id }})"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <rect x="3" y="11" width="18" height="6" rx="2" />
                            <path d="M5 17l1.5 2h11L19 17" />
                            <circle cx="7.5" cy="16" r="1" />
                            <circle cx="16.5" cy="16" r="1" />
                            <path d="M7 11V7a3 3 0 0 1 6 0v4" />
                            <path d="M9 11V7a1 1 0 1 1 2 0v4" />
                            <path d="M4 11V8" />
                            <path d="M20 11V8" />
                        </svg>
                        <span>{{ __('messages.pages.vehicles.detail.request_test_drive') }}</span>
                    </button>

                    <!-- Price Negotiation Button -->
                    <button 
                        type="button"
                        onclick="openEnquiryDialog('price-negotiation', {{ $vehicle->id }})"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="12" y1="2" x2="12" y2="22"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>{{ __('messages.pages.vehicles.detail.price_negotiation') }}</span>
                    </button>
                </div>
            </div>
            @endif

            @auth
                @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                    <!-- Edit Action - Only for admin/dealer -->
                    @php
                        $editRoute = null;
                        try {
                            if (\Illuminate\Support\Facades\Route::has('dealer.vehicles.edit')) {
                                $editRoute = route('dealer.vehicles.edit', $vehicle->serial_no);
                            } elseif (\Illuminate\Support\Facades\Route::has('vehicles.edit')) {
                                $editRoute = route('vehicles.edit', $vehicle->serial_no);
                            }
                        } catch (\Exception $e) {
                            // Route doesn't exist
                        }
                    @endphp
                    @if($editRoute)
                        <div class="bg-muted/50 rounded-lg p-6">
                            <h2 class="text-foreground mb-4 text-xl font-semibold">
                                {{ __('messages.pages.vehicles.detail.actions') }}
                            </h2>
                            <a href="{{ $editRoute }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                {{ __('messages.pages.vehicles.detail.edit_vehicle') }}
                            </a>
                        </div>
                    @endif
                @endif
            @endauth

            <!-- Dealer Information (desktop sidebar only) -->
            @if($vehicle->dealer)
                <div class="hidden lg:block">
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-foreground">
                            <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ __('messages.pages.vehicles.detail.dealer_information') }}
                        </h2>
                    </div>
                    <div class="space-y-3">
                        @if($dealerOwner && $dealerOwner->name)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ ucfirst($dealerOwner->name) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.contact_name') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_address)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_address }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.address') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_postcode)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_postcode }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.postal_code') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($dealerPhone)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div id="dealer-phone-display" class="hidden">
                                        <p class="text-sm font-medium text-foreground">
                                            <a href="tel:{{ $dealerPhone }}" class="hover:underline">
                                                {{ $dealerPhone }}
                                            </a>
                                        </p>
                                    </div>
                                    <button 
                                        type="button"
                                        id="show-dealer-phone-btn"
                                        onclick="showDealerPhoneAndCreateLead({{ $vehicle->id }}, event)"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                    >
                                        {{ __('messages.pages.vehicles.detail.show_phone_number') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.phone') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($contactEmail && $vehicle->dealer)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <div class="flex-1">
                                    <button 
                                        type="button"
                                        onclick="handleEmailClick({{ $vehicle->id }}, event)"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                        id="dealer-email-btn-{{ $vehicle->id }}"
                                        data-email="{{ $contactEmail }}"
                                    >
                                        {{ __('messages.pages.vehicles.detail.send_email') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.email') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                </div>

                <!-- Dealer Page Link Card -->
                @if($vehicle->dealer && $vehicle->dealer->slug)
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-primary">
                                <path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-foreground mb-1">
                                {{ __('messages.pages.vehicles.detail.view_dealer_page') }}
                            </h3>
                            <p class="text-xs text-muted-foreground mb-3">
                                {{ __('messages.pages.vehicles.detail.see_all_vehicles') }}
                            </p>
                            <a 
                                href="/dealer-{{ $vehicle->dealer->slug }}" 
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M5 12h14"></path>
                                    <path d="m12 5 7 7-7 7"></path>
                                </svg>
                                {{ __('messages.pages.vehicles.detail.visit_dealer_page') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            @endif

            <!-- Seller Information (Private Seller) -->
            @if($vehicle->user && !$vehicle->dealer)
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-foreground">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <h2 class="text-xl font-semibold text-foreground">
                            {{ __('messages.pages.vehicles.detail.seller_information') }}
                        </h2>
                    </div>
                    <div class="space-y-3">
                        @if($vehicle->user && $vehicle->user->name)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ ucfirst($vehicle->user->name) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.forms.name') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_address)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_address }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.address') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($vehicle->seller_postcode)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ $vehicle->seller_postcode }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.postal_code') }}</p>
                                </div>
                            </div>
                        @endif
                        @php
                            $sellerPhone = $details && $details->seller_phone ? $details->seller_phone : null;
                            if (empty($sellerPhone) && $vehicle->user && $vehicle->user->phone) {
                                $sellerPhone = $vehicle->user->phone;
                            }
                        @endphp
                        @if($sellerPhone)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div id="phone-display" class="hidden">
                                        <p class="text-sm font-medium text-foreground">
                                            <a href="tel:{{ $sellerPhone }}" class="hover:underline">
                                                {{ $sellerPhone }}
                                            </a>
                                        </p>
                                    </div>
                                    <button 
                                        type="button"
                                        id="show-phone-btn"
                                        onclick="showPhoneAndCreateLead({{ $vehicle->id }}, event)"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                    >
                                        {{ __('messages.pages.vehicles.detail.show_phone_number') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.phone') }}</p>
                                </div>
                            </div>
                        @endif
                        @if($contactEmail && $vehicle->user && !$vehicle->dealer)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                                <div class="flex-1">
                                    <button 
                                        type="button"
                                        onclick="handleEmailClick({{ $vehicle->id }}, event)"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                        id="seller-email-btn-{{ $vehicle->id }}"
                                        data-email="{{ $contactEmail }}"
                                    >
                                        {{ __('messages.pages.vehicles.detail.send_email') }}
                                    </button>
                                    <p class="text-xs text-muted-foreground">{{ __('messages.pages.vehicles.detail.email') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif


            @auth
                @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                    <!-- Edit Action - Only for admin/dealer -->
                    @php
                        $editRoute = null;
                        try {
                            if (\Illuminate\Support\Facades\Route::has('dealer.vehicles.edit')) {
                                $editRoute = route('dealer.vehicles.edit', $vehicle->serial_no);
                            } elseif (\Illuminate\Support\Facades\Route::has('vehicles.edit')) {
                                $editRoute = route('vehicles.edit', $vehicle->serial_no);
                            }
                        } catch (\Exception $e) {
                            // Route doesn't exist
                        }
                    @endphp
                    @if($editRoute)
                        <div class="bg-muted/50 rounded-lg p-6">
                            <h2 class="text-foreground mb-4 text-xl font-semibold">
                                {{ __('messages.pages.vehicles.detail.actions') }}
                            </h2>
                            <a href="{{ $editRoute }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                {{ __('messages.pages.vehicles.detail.edit_vehicle') }}
                            </a>
                        </div>
                    @endif
                @endif
            @endauth

            <!-- Listing Information - For all users -->
            <div class="rounded-lg bg-gray-50 p-6">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h2 class="text-xl font-semibold">
                            @auth
                            @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                                {{ __('messages.pages.vehicles.detail.inventory_information') }}
                            @else
                                {{ __('messages.pages.vehicles.detail.listing_information') }}
                            @endif
                        @else
                            {{ __('messages.pages.vehicles.detail.listing_information') }}
                        @endauth
                    </h2>
                </div>
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">
                            @auth
                                @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                                    {{ __('messages.pages.vehicles.detail.added_to_inventory') }}
                                @else
                                    {{ __('messages.pages.vehicles.detail.added_to_listing') }}
                                @endif
                            @else
                                {{ __('messages.pages.vehicles.detail.added_to_listing') }}
                            @endauth
                        </label>
                        @if($vehicle->published_at)
                            <p class="text-sm">
                                {{ $vehicle->published_at->format('F j, Y') }} ({{ $vehicle->published_at->diffForHumans() }})
                            </p>
                        @elseif($vehicle->created_at)
                            <p class="text-sm">
                                {{ $vehicle->created_at->format('F j, Y') }} ({{ $vehicle->created_at->diffForHumans() }})
                            </p>
                        @endif
                    </div>
                    @auth
                        @if(auth()->user()->hasAnyRole(['admin', 'dealer']) && $vehicle->published_at)
                            <div class="space-y-1">
                                <label class="text-sm font-medium">
                                    {{ __('messages.pages.vehicles.detail.days_in_inventory') }}
                                </label>
                                <p class="font-semibold">
                                    {{ floor($vehicle->published_at->diffInDays(now(), false)) }} {{ __('messages.pages.vehicles.detail.days') }}
                                </p>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>

            @auth
                @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                    @php
                        $pendingWorks = [];
                        if($vehicle->details && $vehicle->details->extra_equipment) {
                            // You can parse pending works from extra_equipment or other fields
                            // For now, we'll leave it empty or add logic based on your data structure
                        }
                    @endphp
                    @if(!empty($pendingWorks))
                        <!-- Pending Works - Only for admin/dealer -->
                        <div class="rounded-lg bg-yellow-50 p-6">
                            <div class="mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-yellow-600">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                </svg>
                                <h2 class="text-xl font-semibold text-yellow-800">
                                    {{ __('messages.pages.vehicles.detail.pending_works') }}
                                </h2>
                            </div>
                            <p class="mb-4 text-sm text-yellow-700">
                                {{ __('messages.pages.vehicles.detail.items_require_attention') }}
                            </p>
                            <ul class="space-y-3">
                                @foreach($pendingWorks as $work)
                                    <li class="flex items-start gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-600">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                            <path d="M12 9v4"></path>
                                            <path d="M12 17h.01"></path>
                                        </svg>
                                        <span class="text-sm text-yellow-800">
                                            {{ $work }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($vehicle->details && $vehicle->details->extra_equipment)
                        <!-- Internal Remarks - Only for admin/dealer -->
                        <div class="bg-muted/50 rounded-lg p-6">
                            <h2 class="text-foreground mb-4 text-xl font-semibold">
                                {{ __('messages.pages.vehicles.detail.internal_remarks') }}
                            </h2>
                            <p class="text-foreground text-sm leading-relaxed">
                                {{ $vehicle->details->extra_equipment }}
                            </p>
                        </div>
                    @endif
                @endif
            @endauth

            <!-- Public Contact Information - Only for non-authenticated users -->
            @guest
                <div class="rounded-lg bg-gray-50 p-6">
                    <h2 class="text-foreground mb-4 text-xl font-semibold">
                        {{ __('messages.pages.vehicles.detail.interested') }}
                    </h2>
                    <p class="text-muted-foreground mb-4 text-sm leading-relaxed">
                        {{ __('messages.pages.vehicles.detail.contact_message') }}
                    </p>
                    <div class="text-muted-foreground text-sm">
                        <p>• {{ __('messages.pages.vehicles.detail.request_detailed_history') }}</p>
                        <p>• {{ __('messages.pages.vehicles.detail.schedule_inspection') }}</p>
                        <p>• {{ __('messages.pages.vehicles.detail.arrange_test_drive') }}</p>
                    </div>
                </div>
            @endguest
        </div>
    </div>
</div>

<!-- Enquiry Dialogs -->
<x-enquiry-dialog type="enquiry" :vehicle="$vehicle" />
<x-enquiry-dialog type="test-drive" :vehicle="$vehicle" />
<x-enquiry-dialog type="price-negotiation" :vehicle="$vehicle" />

<!-- Login Dialog -->
<x-login-dialog />

<script src="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/embla-carousel.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Embla Carousel
    const emblaNode = document.querySelector('#vehicle-images-carousel');
    if (emblaNode) {
        const prevBtn = document.querySelector('.embla__prev');
        const nextBtn = document.querySelector('.embla__next');
        const emblaApi = EmblaCarousel(emblaNode, { 
            loop: false, 
            align: 'start',
            slidesToScroll: 1,
            breakpoints: {
                '(min-width: 768px)': { slidesToScroll: 2 },
                '(min-width: 1024px)': { slidesToScroll: 2 }
            }
        });

        function togglePrevNextBtns() {
            if (emblaApi) {
                if (prevBtn) prevBtn.disabled = !emblaApi.canScrollPrev();
                if (nextBtn) nextBtn.disabled = !emblaApi.canScrollNext();
            }
        }

        if (emblaApi) {
            emblaApi.on('select', togglePrevNextBtns);
            emblaApi.on('init', togglePrevNextBtns);
            if (prevBtn) prevBtn.addEventListener('click', emblaApi.scrollPrev);
            if (nextBtn) nextBtn.addEventListener('click', emblaApi.scrollNext);
        }
    }

    // Initialize GLightbox for image viewer
    if (typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            closeButton: true,
            zoomable: true,
            draggable: true,
            openEffect: 'fade',
            closeEffect: 'fade',
            slideEffect: 'fade', // Use fade instead of slide to show one image at a time
            moreText: '{{ __('messages.common.see_more') }}',
            moreLength: 60,
            closeOnOutsideClick: true,
            preload: false, // Disable preload to ensure only current image loads
            description: false, // Disable description section
            cssEfects: {
                fade: { in: 'fadeIn', out: 'fadeOut' }
            }
        });
        
        // Ensure only current slide is visible
        lightbox.on('slide_changed', ({ prev, current }) => {
            // Hide all slides except current
            const slides = document.querySelectorAll('.gslide');
            slides.forEach(slide => {
                if (!slide.classList.contains('current')) {
                    slide.style.opacity = '0';
                    slide.style.visibility = 'hidden';
                } else {
                    slide.style.opacity = '1';
                    slide.style.visibility = 'visible';
                }
            });
        });
    }

    // Get access token from cookie helper
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    // Check if user is authenticated
    function isUserAuthenticated() {
        return getCookie('access_token') !== null;
    }
    
    // Toggle phone number visibility
    function togglePhone() {
        const phoneDisplay = document.getElementById('phone-display');
        const showPhoneBtn = document.getElementById('show-phone-btn');
        
        if (phoneDisplay && showPhoneBtn) {
            if (phoneDisplay.classList.contains('hidden')) {
                phoneDisplay.classList.remove('hidden');
                showPhoneBtn.classList.add('hidden');
            } else {
                phoneDisplay.classList.add('hidden');
                showPhoneBtn.classList.remove('hidden');
            }
        }
    }
    
    // Show phone number and create lead
    window.showPhoneAndCreateLead = async function(vehicleId, event) {
        // Prevent any default behavior and stop propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        // Check if user is authenticated
        if (!isUserAuthenticated()) {
            // Store action for after login
            sessionStorage.setItem('pendingAction', JSON.stringify({
                type: 'showPhone',
                vehicleId: vehicleId
            }));
            
            // Open login dialog with callback to show phone after login
            if (window.openLoginDialog) {
                window.openLoginDialog(() => {
                    // After successful login, automatically show phone
                    window.showPhoneAndCreateLead(vehicleId, event);
                });
            } else {
                    if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.forms.please_login_view_phone') }}', 'error');
                    }
                setTimeout(() => {
                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                }, 1500);
            }
            return false;
        }
        
        // Get button element
        const button = event?.target?.closest('button') || event?.target;
        
        // Show phone number immediately (before creating lead)
        togglePhone();
        
        // Hide the button after showing phone
        if (button) {
            button.style.display = 'none';
        }
        
        // Create lead in the background (don't wait for it)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Make API call to create lead asynchronously (fire and forget)
        fetch(`/vehicles/${vehicleId}/enquire`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                category: '{{ __('messages.forms.phone_number_revealed') }}'
            }),
            credentials: 'same-origin'
        }).catch(error => {
            // Silently handle errors - phone is already shown
            console.error('Error creating lead:', error);
        });
        
        return false;
    };
    
    // Toggle dealer phone number visibility (supports desktop sidebar and mobile block)
    function toggleDealerPhone(clickedButton) {
        let phoneDisplay, showPhoneBtn;
        if (clickedButton && clickedButton.classList.contains('show-dealer-phone-btn-mobile')) {
            const block = clickedButton.closest('.bg-gray-50');
            phoneDisplay = block ? block.querySelector('.dealer-phone-display-mobile') : null;
            showPhoneBtn = clickedButton;
        } else {
            phoneDisplay = document.getElementById('dealer-phone-display');
            showPhoneBtn = document.getElementById('show-dealer-phone-btn');
        }
        if (phoneDisplay && showPhoneBtn) {
            if (phoneDisplay.classList.contains('hidden')) {
                phoneDisplay.classList.remove('hidden');
                showPhoneBtn.classList.add('hidden');
            } else {
                phoneDisplay.classList.add('hidden');
                showPhoneBtn.classList.remove('hidden');
            }
        }
    }
    
    // Show dealer phone number and create lead
    window.showDealerPhoneAndCreateLead = async function(vehicleId, event) {
        // Prevent any default behavior and stop propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        // Check if user is authenticated
        if (!isUserAuthenticated()) {
            // Store action for after login
            sessionStorage.setItem('pendingAction', JSON.stringify({
                type: 'showDealerPhone',
                vehicleId: vehicleId
            }));
            
            // Open login dialog with callback to show phone after login
            if (window.openLoginDialog) {
                window.openLoginDialog(() => {
                    // After successful login, automatically show phone
                    window.showDealerPhoneAndCreateLead(vehicleId, event);
                });
            } else {
                    if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.forms.please_login_view_phone') }}', 'error');
                    }
                setTimeout(() => {
                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                }, 1500);
            }
            return false;
        }
        
        // Get button element
        const button = event?.target?.closest('button') || event?.target;
        
        // Show phone number immediately (before creating lead)
        toggleDealerPhone(button);
        
        // Hide the button after showing phone
        if (button) {
            button.style.display = 'none';
        }
        
        // Create lead in the background (don't wait for it)
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Make API call to create lead asynchronously (fire and forget)
        fetch(`/vehicles/${vehicleId}/enquire`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                category: '{{ __('messages.forms.phone_number_revealed') }}'
            }),
            credentials: 'same-origin'
        }).catch(error => {
            // Silently handle errors - phone is already shown
            console.error('Error creating lead:', error);
        });
        
        return false;
    };
    
    // Make togglePhone available globally
    window.togglePhone = togglePhone;
    window.toggleDealerPhone = toggleDealerPhone;

    // Helper function to create lead
    async function createLead(vehicleId, category, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const button = event?.target?.closest('button') || event?.target;
        const originalText = button ? button.querySelector('span')?.textContent || button.textContent : '';

        try {
            if (button) {
                button.disabled = true;
                const textSpan = button.querySelector('span');
                if (textSpan) {
                    textSpan.textContent = '{{ __('messages.forms.loading') }}';
                } else {
                    button.textContent = '{{ __('messages.forms.loading') }}';
                }
            }

            const response = await fetch(`/vehicles/${vehicleId}/enquire`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ category }),
                credentials: 'same-origin'
            });

            if (!response.ok) {
                if (response.status === 401) {
                    if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.forms.please_login_continue') }}', 'error');
                    }
                    setTimeout(() => {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                    return false;
                }

                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || '{{ __('messages.forms.failed_to_process') }}';
                
                if (window.showSnackbar) {
                    window.showSnackbar(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
                return false;
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error creating lead:', error);
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
            } else {
                alert('{{ __('messages.dialogs.error_occurred') }}');
            }
            return false;
        } finally {
            if (button) {
                button.disabled = false;
                const textSpan = button.querySelector('span');
                if (textSpan && originalText) {
                    textSpan.textContent = originalText;
                } else if (originalText) {
                    button.textContent = originalText;
                }
            }
        }
    }

    // Handle WhatsApp click
    window.handleWhatsAppClick = async function(vehicleId, event) {
        const button = event?.target?.closest('button');
        const whatsappNumber = button?.dataset?.whatsapp;
        
        if (!whatsappNumber) {
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.forms.whatsapp_not_available') }}', 'error');
            }
            return false;
        }

        // Create lead first
        const leadResult = await createLead(vehicleId, '{{ __('messages.forms.whatsapp_clicked') }}', event);
        
        if (leadResult) {
            // Format phone number for WhatsApp (remove spaces, dashes, etc.)
            const formattedNumber = whatsappNumber.replace(/[\s\-\(\)]/g, '');
            // Open WhatsApp
            window.open(`https://wa.me/${formattedNumber}`, '_blank');
            
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.forms.opening_whatsapp') }}', 'success');
            }
        }
        
        return false;
    };

    // Handle Email click
    window.handleEmailClick = async function(vehicleId, event) {
        // Prevent any default behavior and stop propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        // Check if user is authenticated
        if (!isUserAuthenticated()) {
            // Store action for after login
            const button = event?.target?.closest('button');
            const email = button?.dataset?.email;
            sessionStorage.setItem('pendingAction', JSON.stringify({
                type: 'handleEmail',
                vehicleId: vehicleId,
                email: email
            }));
            
            // Open login dialog with callback to open email after login
            if (window.openLoginDialog) {
                window.openLoginDialog(() => {
                    // After successful login, automatically open email
                    window.handleEmailClick(vehicleId, event);
                });
            } else {
                if (window.showSnackbar) {
                    window.showSnackbar('{{ __('messages.forms.please_login_contact_email') }}', 'error');
                }
                setTimeout(() => {
                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                }, 1500);
            }
            return false;
        }
        
        const button = event?.target?.closest('button');
        const email = button?.dataset?.email;
        
        if (!email) {
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.forms.email_not_available') }}', 'error');
            }
            return false;
        }

        // Create lead first
        const leadResult = await createLead(vehicleId, '{{ __('messages.forms.email_clicked') }}', event);
        
        if (leadResult) {
            // Get vehicle title for email subject
            const vehicleTitle = document.querySelector('h1')?.textContent?.trim() || '{{ __('messages.forms.vehicle_enquiry') }}';
            const subject = encodeURIComponent(`{{ __('messages.forms.enquiry_about') }}: ${vehicleTitle}`);
            const body = encodeURIComponent(`{{ __('messages.forms.enquiry_email_body') }}: ${vehicleTitle}\n\n{{ __('messages.forms.enquiry_email_body_end') }}`);
            
            // Open email client
            window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
            
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.forms.opening_email_client') }}', 'success');
            }
        }
        
        return false;
    };

    // Handle Test Drive Request - Now handled by form page, keeping for backward compatibility
    window.handleTestDriveRequest = async function(vehicleId, event) {
        // Redirect to test drive form instead of creating lead directly
        window.location.href = `/vehicles/${vehicleId}/test-drive`;
        return false;
    };

    // Handle Price Negotiation - Now handled by form page, keeping for backward compatibility
    window.handlePriceNegotiation = async function(vehicleId, event) {
        // Redirect to price negotiation form instead of creating lead directly
        window.location.href = `/vehicles/${vehicleId}/price-negotiation`;
        return false;
    };
    
    // Check for pending actions after page load (for redirect-based login)
    window.addEventListener('load', function() {
        const pendingAction = sessionStorage.getItem('pendingAction');
        if (pendingAction && isUserAuthenticated()) {
            try {
                const action = JSON.parse(pendingAction);
                sessionStorage.removeItem('pendingAction');
                
                // Execute the pending action
                if (action.type === 'showPhone' && action.vehicleId) {
                    // Create a synthetic event
                    const syntheticEvent = { target: document.getElementById('show-phone-btn') };
                    window.showPhoneAndCreateLead(action.vehicleId, syntheticEvent);
                } else if (action.type === 'showDealerPhone' && action.vehicleId) {
                    // Create a synthetic event
                    const syntheticEvent = { target: document.getElementById('show-dealer-phone-btn') };
                    window.showDealerPhoneAndCreateLead(action.vehicleId, syntheticEvent);
                } else if (action.type === 'handleEmail' && action.vehicleId && action.email) {
                    // Find the email button and create synthetic event
                    const emailButton = document.querySelector(`[data-email="${action.email}"]`);
                    if (emailButton) {
                        const syntheticEvent = { target: emailButton };
                        window.handleEmailClick(action.vehicleId, syntheticEvent);
                    }
                }
            } catch (error) {
                console.error('Error executing pending action:', error);
            }
        }
    });
});
</script>
@endsection
