@extends('layouts.app')

@section('title', 'Vehicle Details | Bilskyen')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<style>
    .detail-section {
        border: 1px solid var(--border);
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
        font-size: 1rem;
        color: var(--foreground);
        font-weight: 500;
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
                <p class="text-muted-foreground text-xl">
                    Registration: <span class="text-foreground font-mono">{{ $vehicle->registration }}</span>
                </p>
            </div>
            <div class="flex flex-col items-start gap-3 lg:items-end">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                </p>
                <span class="inline-flex items-center rounded-md border border-border bg-secondary px-2 py-1 text-sm font-semibold text-secondary-foreground">
                    {{ $vehicle->vehicle_list_status_name ?? 'Published' }}
                </span>
            </div>
        </div>
        <div class="border-t border-border"></div>
    </div>

    <!-- Images Carousel Section -->
    @if($vehicle->images && $vehicle->images->count() > 0)
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                <circle cx="9" cy="9" r="2"></circle>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
            </svg>
            <h2 class="text-foreground text-xl font-semibold">
                Photos ({{ $vehicle->images->count() }})
            </h2>
        </div>

            <div class="relative">
            <div class="embla overflow-hidden" id="vehicle-images-carousel">
                <div class="embla__container flex">
                    @foreach($vehicle->images as $index => $image)
                    <div class="embla__slide flex-shrink-0 basis-full md:basis-1/2 lg:basis-1/3">
                        <a href="{{ $image->image_url }}" class="glightbox" data-gallery="vehicle-gallery" data-glightbox="title: Vehicle photo {{ $index + 1 }}">
                            <div class="border-border bg-muted/50 relative aspect-square cursor-pointer overflow-hidden rounded-lg border transition-all hover:shadow-md mr-4">
                                <img
                                    src="{{ $image->image_url }}"
                                    alt="Vehicle photo {{ $index + 1 }}"
                                    class="h-full w-full object-cover transition-transform hover:scale-105"
                                />
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @if($vehicle->images->count() > 3)
            <button class="embla__prev absolute left-2 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="Previous slide">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
            <button class="embla__next absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-10 w-10 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="Next slide">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
            @endif
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <div class="space-y-6">
        <!-- Basic Information Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Basic Information</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Title</span>
                    <span class="detail-value">{{ $vehicle->title }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Registration</span>
                    <span class="detail-value font-mono">{{ $vehicle->registration }}</span>
                    </div>
                @if($vehicle->vin)
                <div class="detail-item">
                    <span class="detail-label">VIN</span>
                    <span class="detail-value font-mono text-sm">{{ $vehicle->vin }}</span>
                    </div>
                    @endif
                <div class="detail-item">
                    <span class="detail-label">Price</span>
                    <span class="detail-value text-green-600 dark:text-green-400">{{ FormatHelper::formatCurrency($vehicle->price ?? null) }}</span>
                    </div>
                @if($vehicle->location)
                <div class="detail-item">
                    <span class="detail-label">Location</span>
                    <span class="detail-value">{{ $vehicle->location->city }}, {{ $vehicle->location->postcode }}</span>
                    </div>
                @endif
                @if($vehicle->listing_type_name)
                <div class="detail-item">
                    <span class="detail-label">Listing Type</span>
                    <span class="detail-value">{{ $vehicle->listing_type_name }}</span>
                </div>
                @endif
                </div>
            </div>

        <!-- Vehicle Specifications Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Vehicle Specifications</h2>
            <div class="detail-grid">
                @if($vehicle->category_name)
                <div class="detail-item">
                    <span class="detail-label">Category</span>
                    <span class="detail-value">{{ $vehicle->category_name }}</span>
                </div>
                @endif
                @if($vehicle->brand_name)
                <div class="detail-item">
                    <span class="detail-label">Brand</span>
                    <span class="detail-value">{{ $vehicle->brand_name }}</span>
                </div>
                @endif
                @if($vehicle->model_name)
                <div class="detail-item">
                    <span class="detail-label">Model</span>
                    <span class="detail-value">{{ $vehicle->model_name }}</span>
                </div>
                @endif
                @if($vehicle->model_year_name)
                <div class="detail-item">
                    <span class="detail-label">Model Year</span>
                    <span class="detail-value">{{ $vehicle->model_year_name }}</span>
                </div>
                @endif
                @if($vehicle->fuel_type_name)
                <div class="detail-item">
                    <span class="detail-label">Fuel Type</span>
                    <span class="detail-value">{{ $vehicle->fuel_type_name }}</span>
                </div>
                @endif
                @if($vehicle->mileage)
                <div class="detail-item">
                    <span class="detail-label">Mileage</span>
                    <span class="detail-value">{{ number_format($vehicle->mileage) }} km</span>
                    </div>
                @endif
                @if($vehicle->km_driven)
                <div class="detail-item">
                    <span class="detail-label">Kilometers Driven</span>
                    <span class="detail-value">{{ number_format($vehicle->km_driven) }} km</span>
                    </div>
                    @endif
                @if($vehicle->battery_capacity)
                <div class="detail-item">
                    <span class="detail-label">Battery Capacity</span>
                    <span class="detail-value">{{ $vehicle->battery_capacity }} kWh</span>
                    </div>
                @endif
                @if($vehicle->range_km)
                <div class="detail-item">
                    <span class="detail-label">Range</span>
                    <span class="detail-value">{{ number_format($vehicle->range_km) }} km</span>
                </div>
                @endif
                @if($vehicle->charging_type)
                <div class="detail-item">
                    <span class="detail-label">Charging Type</span>
                    <span class="detail-value">{{ $vehicle->charging_type }}</span>
                </div>
                @endif
                @if($vehicle->engine_power)
                <div class="detail-item">
                    <span class="detail-label">Engine Power</span>
                    <span class="detail-value">{{ $vehicle->engine_power }} HP</span>
                    </div>
                @endif
                @if($vehicle->towing_weight)
                <div class="detail-item">
                    <span class="detail-label">Towing Weight</span>
                    <span class="detail-value">{{ number_format($vehicle->towing_weight) }} kg</span>
                    </div>
                @endif
                @if($vehicle->ownership_tax)
                <div class="detail-item">
                    <span class="detail-label">Ownership Tax</span>
                    <span class="detail-value">{{ FormatHelper::formatCurrency($vehicle->ownership_tax ?? null) }}</span>
                    </div>
                @endif
                @if($vehicle->first_registration_date)
                <div class="detail-item">
                    <span class="detail-label">First Registration Date</span>
                    <span class="detail-value">{{ $vehicle->first_registration_date->format('F j, Y') }}</span>
                </div>
                @endif
                </div>
            </div>

        @if($details)
        <!-- Detailed Specifications Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Detailed Specifications</h2>
            <div class="detail-grid">
                @if($details->description)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">Description</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->description }}</p>
                </div>
                @endif
                @if($details->type_name_resolved)
                <div class="detail-item">
                    <span class="detail-label">Type</span>
                    <span class="detail-value">{{ $details->type_name_resolved }}</span>
                </div>
                @endif
                @if($details->use_name)
                <div class="detail-item">
                    <span class="detail-label">Use</span>
                    <span class="detail-value">{{ $details->use_name }}</span>
                </div>
                @endif
                @if($details->color_name)
                <div class="detail-item">
                    <span class="detail-label">Color</span>
                    <span class="detail-value">{{ $details->color_name }}</span>
                </div>
                @endif
                @if($details->body_type_name)
                <div class="detail-item">
                    <span class="detail-label">Body Type</span>
                    <span class="detail-value">{{ $details->body_type_name }}</span>
                </div>
                @endif
                @if($details->price_type_name)
                <div class="detail-item">
                    <span class="detail-label">Price Type</span>
                    <span class="detail-value">{{ $details->price_type_name }}</span>
                </div>
                @endif
                @if($details->condition_name)
                <div class="detail-item">
                    <span class="detail-label">Condition</span>
                    <span class="detail-value">{{ $details->condition_name }}</span>
                </div>
                @endif
                @if($details->gear_type_name)
                <div class="detail-item">
                    <span class="detail-label">Gear Type</span>
                    <span class="detail-value">{{ $details->gear_type_name }}</span>
                </div>
                @endif
                @if($details->sales_type_name)
                <div class="detail-item">
                    <span class="detail-label">Sales Type</span>
                    <span class="detail-value">{{ $details->sales_type_name }}</span>
                </div>
                @endif
                @if($details->version)
                <div class="detail-item">
                    <span class="detail-label">Version</span>
                    <span class="detail-value">{{ $details->version }}</span>
                </div>
                @endif
                @if($details->vin_location)
                <div class="detail-item">
                    <span class="detail-label">VIN Location</span>
                    <span class="detail-value">{{ $details->vin_location }}</span>
                </div>
                @endif
                @if($details->vehicle_external_id)
                <div class="detail-item">
                    <span class="detail-label">Vehicle External ID</span>
                    <span class="detail-value font-mono text-sm">{{ $details->vehicle_external_id }}</span>
                </div>
                @endif
                @if($details->total_weight)
                <div class="detail-item">
                    <span class="detail-label">Total Weight</span>
                    <span class="detail-value">{{ number_format($details->total_weight) }} kg</span>
                </div>
                @endif
                @if($details->vehicle_weight)
                <div class="detail-item">
                    <span class="detail-label">Vehicle Weight</span>
                    <span class="detail-value">{{ number_format($details->vehicle_weight) }} kg</span>
                </div>
                @endif
                @if($details->technical_total_weight)
                <div class="detail-item">
                    <span class="detail-label">Technical Total Weight</span>
                    <span class="detail-value">{{ number_format($details->technical_total_weight) }} kg</span>
                </div>
                @endif
                @if($details->minimum_weight)
                <div class="detail-item">
                    <span class="detail-label">Minimum Weight</span>
                    <span class="detail-value">{{ number_format($details->minimum_weight) }} kg</span>
                </div>
                @endif
                @if($details->gross_combination_weight)
                <div class="detail-item">
                    <span class="detail-label">Gross Combination Weight</span>
                    <span class="detail-value">{{ number_format($details->gross_combination_weight) }} kg</span>
                </div>
                @endif
                @if($details->towing_weight_brakes)
                <div class="detail-item">
                    <span class="detail-label">Towing Weight with Brakes</span>
                    <span class="detail-value">{{ number_format($details->towing_weight_brakes) }} kg</span>
                </div>
                @endif
                @if($details->coupling !== null)
                <div class="detail-item">
                    <span class="detail-label">Coupling</span>
                    <span class="detail-value">{{ $details->coupling ? 'Yes' : 'No' }}</span>
                </div>
                @endif
                @if($details->engine_displacement)
                <div class="detail-item">
                    <span class="detail-label">Engine Displacement</span>
                    <span class="detail-value">{{ number_format($details->engine_displacement) }} cc</span>
                </div>
                @endif
                @if($details->engine_code)
                <div class="detail-item">
                    <span class="detail-label">Engine Code</span>
                    <span class="detail-value font-mono text-sm">{{ $details->engine_code }}</span>
                </div>
                @endif
                @if($details->engine_cylinders)
                <div class="detail-item">
                    <span class="detail-label">Engine Cylinders</span>
                    <span class="detail-value">{{ $details->engine_cylinders }}</span>
                </div>
                @endif
                @if($details->doors)
                <div class="detail-item">
                    <span class="detail-label">Doors</span>
                    <span class="detail-value">{{ $details->doors }}</span>
                </div>
                @endif
                @if($details->minimum_seats)
                <div class="detail-item">
                    <span class="detail-label">Minimum Seats</span>
                    <span class="detail-value">{{ $details->minimum_seats }}</span>
                </div>
                @endif
                @if($details->maximum_seats)
                <div class="detail-item">
                    <span class="detail-label">Maximum Seats</span>
                    <span class="detail-value">{{ $details->maximum_seats }}</span>
                </div>
                @endif
                @if($details->top_speed)
                <div class="detail-item">
                    <span class="detail-label">Top Speed</span>
                    <span class="detail-value">{{ number_format($details->top_speed) }} km/h</span>
                </div>
                @endif
                @if($details->fuel_efficiency)
                <div class="detail-item">
                    <span class="detail-label">Fuel Efficiency</span>
                    <span class="detail-value">{{ number_format($details->fuel_efficiency, 2) }} L/100km</span>
                </div>
                @endif
                @if($details->airbags)
                <div class="detail-item">
                    <span class="detail-label">Airbags</span>
                    <span class="detail-value">{{ $details->airbags }}</span>
                </div>
                @endif
                @if($details->ncap_five !== null)
                <div class="detail-item">
                    <span class="detail-label">NCAP 5-Star</span>
                    <span class="detail-value">{{ $details->ncap_five ? 'Yes' : 'No' }}</span>
                </div>
                @endif
                @if($details->integrated_child_seats)
                <div class="detail-item">
                    <span class="detail-label">Integrated Child Seats</span>
                    <span class="detail-value">{{ $details->integrated_child_seats }}</span>
                </div>
                @endif
                @if($details->seat_belt_alarms)
                <div class="detail-item">
                    <span class="detail-label">Seat Belt Alarms</span>
                    <span class="detail-value">{{ $details->seat_belt_alarms }}</span>
                </div>
                @endif
                @if($details->euronorm)
                <div class="detail-item">
                    <span class="detail-label">Euro Norm</span>
                    <span class="detail-value">{{ $details->euronorm }}</span>
                    </div>
                @endif
                @if($details->wheels)
                <div class="detail-item">
                    <span class="detail-label">Wheels</span>
                    <span class="detail-value">{{ $details->wheels }}</span>
                    </div>
                @endif
                @if($details->axles)
                <div class="detail-item">
                    <span class="detail-label">Axles</span>
                    <span class="detail-value">{{ $details->axles }}</span>
                        </div>
                @endif
                @if($details->drive_axles)
                <div class="detail-item">
                    <span class="detail-label">Drive Axles</span>
                    <span class="detail-value">{{ $details->drive_axles }}</span>
                    </div>
                    @endif
                @if($details->wheelbase)
                <div class="detail-item">
                    <span class="detail-label">Wheelbase</span>
                    <span class="detail-value">{{ number_format($details->wheelbase) }} mm</span>
                </div>
                @endif
                @if($details->type_approval_code)
                <div class="detail-item">
                    <span class="detail-label">Type Approval Code</span>
                    <span class="detail-value font-mono text-sm">{{ $details->type_approval_code }}</span>
            </div>
                @endif
                @if($details->category)
                <div class="detail-item">
                    <span class="detail-label">Category (String)</span>
                    <span class="detail-value">{{ $details->category }}</span>
                </div>
                @endif
                @if($details->extra_equipment)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">Extra Equipment</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->extra_equipment }}</p>
                    </div>
                @endif
                @if($details->dispensations)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">Dispensations</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->dispensations }}</p>
                </div>
                @endif
                @if($details->permits)
                <div class="detail-item md:col-span-2">
                    <span class="detail-label">Permits</span>
                    <p class="detail-value whitespace-pre-wrap">{{ $details->permits }}</p>
            </div>
            @endif
            </div>
        </div>

        <!-- Registration & Status Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Registration & Status</h2>
            <div class="detail-grid">
                @if($details->registration_status)
                <div class="detail-item">
                    <span class="detail-label">Registration Status</span>
                    <span class="detail-value">{{ $details->registration_status }}</span>
                </div>
                @endif
                @if($details->registration_status_updated_date)
                <div class="detail-item">
                    <span class="detail-label">Registration Status Updated Date</span>
                    <span class="detail-value">{{ $details->registration_status_updated_date->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->expire_date)
                <div class="detail-item">
                    <span class="detail-label">Expire Date</span>
                    <span class="detail-value">{{ $details->expire_date->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->status_updated_date)
                <div class="detail-item">
                    <span class="detail-label">Status Updated Date</span>
                    <span class="detail-value">{{ $details->status_updated_date->format('F j, Y') }}</span>
                </div>
                @endif
                </div>
            </div>

        <!-- Inspection Details Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Inspection Details</h2>
            <div class="detail-grid">
                @if($details->last_inspection_date)
                <div class="detail-item">
                    <span class="detail-label">Last Inspection Date</span>
                    <span class="detail-value">{{ $details->last_inspection_date->format('F j, Y') }}</span>
            </div>
            @endif
                @if($details->last_inspection_result)
                <div class="detail-item">
                    <span class="detail-label">Last Inspection Result</span>
                    <span class="detail-value">{{ $details->last_inspection_result }}</span>
                </div>
                @endif
                @if($details->last_inspection_odometer)
                <div class="detail-item">
                    <span class="detail-label">Last Inspection Odometer</span>
                    <span class="detail-value">{{ number_format($details->last_inspection_odometer) }} km</span>
                    </div>
                    @endif
                </div>
            </div>

        <!-- Leasing Information Section -->
        @if($details->leasing_period_start || $details->leasing_period_end)
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Leasing Information</h2>
            <div class="detail-grid">
                @if($details->leasing_period_start)
                <div class="detail-item">
                    <span class="detail-label">Leasing Period Start</span>
                    <span class="detail-value">{{ $details->leasing_period_start->format('F j, Y') }}</span>
                </div>
                @endif
                @if($details->leasing_period_end)
                <div class="detail-item">
                    <span class="detail-label">Leasing Period End</span>
                    <span class="detail-value">{{ $details->leasing_period_end->format('F j, Y') }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endif

        <!-- Equipment & Features Section -->
        @if($vehicle->equipment && $vehicle->equipment->count() > 0)
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Equipment & Features</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @foreach($vehicle->equipment as $equip)
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-green-500 dark:text-green-400">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span class="text-foreground font-medium text-sm">{{ $equip->name }}</span>
                </div>
                    @endforeach
            </div>
            </div>
            @endif

        <!-- Listing Details Section -->
        <div class="detail-section">
            <h2 class="text-foreground text-xl font-semibold mb-4">Listing Details</h2>
            <div class="detail-grid">
                @if($vehicle->vehicle_list_status_name)
                <div class="detail-item">
                    <span class="detail-label">Listing Status</span>
                    <span class="detail-value">{{ $vehicle->vehicle_list_status_name }}</span>
                </div>
                @endif
                @if($vehicle->published_at)
                <div class="detail-item">
                    <span class="detail-label">Published At</span>
                    <span class="detail-value">{{ $vehicle->published_at->format('F j, Y g:i A') }}</span>
                </div>
                @endif
                @if($vehicle->created_at)
                <div class="detail-item">
                    <span class="detail-label">Created At</span>
                    <span class="detail-value">{{ $vehicle->created_at->format('F j, Y g:i A') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

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
                '(min-width: 1024px)': { slidesToScroll: 3 }
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
            slideEffect: 'slide',
            moreText: 'See more',
            moreLength: 60,
            closeOnOutsideClick: true,
            preload: true,
            cssEfects: {
                fade: { in: 'fadeIn', out: 'fadeOut' },
                slide: { in: 'slideIn', out: 'slideOut' }
            }
        });
    }
});
</script>
@endsection
