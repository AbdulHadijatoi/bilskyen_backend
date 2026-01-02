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

    <!-- Main Content Grid -->
    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Vehicle Details - Left Column -->
        <div class="space-y-6 lg:col-span-2">
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

        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Seller Information -->
            @if($vehicle->user || $vehicle->location)
                <div class="bg-muted/50 rounded-lg p-6">
                    <div class="mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-foreground">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <h2 class="text-xl font-semibold text-foreground">
                            Seller Information
                        </h2>
                    </div>
                    <div class="space-y-3">
                        @if($vehicle->location)
                            @if($vehicle->location->country_code)
                                <div class="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                        <circle cx="12" cy="10" r="3"></circle>
                                        <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-foreground">
                                            {{ strtoupper($vehicle->location->country_code) }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">Country</p>
                                    </div>
                                </div>
                            @endif
                            
                            @if($vehicle->location->city || $vehicle->location->postcode)
                                <div class="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-foreground">
                                            @if($vehicle->location->city && $vehicle->location->postcode)
                                                {{ $vehicle->location->city }}, {{ $vehicle->location->postcode }}
                                            @elseif($vehicle->location->city)
                                                {{ $vehicle->location->city }}
                                            @elseif($vehicle->location->postcode)
                                                {{ $vehicle->location->postcode }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-muted-foreground">Location</p>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($vehicle->user && $vehicle->user->phone)
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-muted-foreground">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                <div class="flex-1">
                                    <div id="phone-display" class="hidden">
                                        <p class="text-sm font-medium text-foreground">
                                            <a href="tel:{{ $vehicle->user->phone }}" class="hover:underline">
                                                {{ $vehicle->user->phone }}
                                            </a>
                                        </p>
                                    </div>
                                    <button 
                                        type="button"
                                        id="show-phone-btn"
                                        onclick="togglePhone()"
                                        class="text-sm font-medium text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                    >
                                        Show Phone Number
                                    </button>
                                    <p class="text-xs text-muted-foreground">Phone</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

            <!-- Pricing -->
            <div class="rounded-lg bg-green-50 p-6 dark:bg-green-950/30">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-green-600 dark:text-green-400">
                        <path d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-green-800 dark:text-green-300">
                        Pricing
                    </h2>
                </div>
                <div class="space-y-2">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                    </p>
                    <p class="text-sm text-green-700 dark:text-green-300">
                        Listed Price
                    </p>
                </div>
            </div>

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
                                Actions
                            </h2>
                            <a href="{{ $editRoute }}" class="flex w-full items-center justify-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit Vehicle
                            </a>
                        </div>
                    @endif
                @endif
            @endauth

            <!-- Listing Information - For all users -->
            <div class="rounded-lg bg-blue-50 p-6 dark:bg-blue-950/30">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-blue-600 dark:text-blue-400">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <h2 class="text-xl font-semibold text-blue-800 dark:text-blue-300">
                        @auth
                            @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                                Inventory Information
                            @else
                                Listing Information
                            @endif
                        @else
                            Listing Information
                        @endauth
                    </h2>
                </div>
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            @auth
                                @if(auth()->user()->hasAnyRole(['admin', 'dealer']))
                                    Added to Inventory
                                @else
                                    Added to Listing
                                @endif
                            @else
                                Added to Listing
                            @endauth
                        </label>
                        @if($vehicle->published_at)
                            <p class="text-sm text-blue-900 dark:text-blue-200">
                                {{ $vehicle->published_at->format('F j, Y') }} ({{ $vehicle->published_at->diffForHumans() }})
                            </p>
                        @elseif($vehicle->created_at)
                            <p class="text-sm text-blue-900 dark:text-blue-200">
                                {{ $vehicle->created_at->format('F j, Y') }} ({{ $vehicle->created_at->diffForHumans() }})
                            </p>
                        @endif
                    </div>
                    @auth
                        @if(auth()->user()->hasAnyRole(['admin', 'dealer']) && $vehicle->published_at)
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-blue-700 dark:text-blue-300">
                                    Days in Inventory
                                </label>
                                <p class="font-semibold text-blue-900 dark:text-blue-200">
                                    {{ $vehicle->published_at->diffInDays(now()) }} days
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
                        <div class="rounded-lg bg-yellow-50 p-6 dark:bg-yellow-950/30">
                            <div class="mb-4 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-yellow-600 dark:text-yellow-400">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                    <path d="M12 9v4"></path>
                                    <path d="M12 17h.01"></path>
                                </svg>
                                <h2 class="text-xl font-semibold text-yellow-800 dark:text-yellow-300">
                                    Pending Works
                                </h2>
                            </div>
                            <p class="mb-4 text-sm text-yellow-700 dark:text-yellow-300">
                                Items that require attention
                            </p>
                            <ul class="space-y-3">
                                @foreach($pendingWorks as $work)
                                    <li class="flex items-start gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-600 dark:text-yellow-400">
                                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                            <path d="M12 9v4"></path>
                                            <path d="M12 17h.01"></path>
                                        </svg>
                                        <span class="text-sm text-yellow-800 dark:text-yellow-200">
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
                                Internal Remarks
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
                <div class="bg-muted/50 rounded-lg p-6">
                    <h2 class="text-foreground mb-4 text-xl font-semibold">
                        Interested?
                    </h2>
                    <p class="text-muted-foreground mb-4 text-sm leading-relaxed">
                        Contact us for more information about this vehicle, including
                        pricing, financing options, and scheduling a test drive.
                    </p>
                    <div class="text-muted-foreground text-sm">
                        <p>• Request detailed vehicle history</p>
                        <p>• Schedule inspection</p>
                        <p>• Discuss financing options</p>
                        <p>• Arrange test drive</p>
                    </div>
            </div>
            @endguest
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
    
    // Make togglePhone available globally
    window.togglePhone = togglePhone;
});
</script>
@endsection
