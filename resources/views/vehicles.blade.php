@extends('layouts.app')

@section('title', 'Vehicles | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    // $vehicles is provided by HomeController
@endphp

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <!-- Search Bar -->
    <div id="search-bar-container" class="rounded-lg bg-card p-2 sm:p-3 shadow-sm">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <!-- Search Input -->
            <form class="flex w-full sm:flex-1" method="GET" action="/vehicles" id="search-form">
                <!-- Preserve existing query parameters (including sort) -->
                @foreach(request()->except('search') as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="relative w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        id="search-input"
                        value="{{ request()->query('search', '') }}"
                        placeholder="Search by make, model, registration number, or keywords..."
                        class="flex h-8 w-full rounded-md border border-input bg-background pl-9 pr-2.5 py-1.5 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        autocomplete="off"
                    />
                </div>
            </form>
            
            <!-- Sort Dropdown -->
            <div class="relative">
                <button 
                    type="button" 
                    id="sort-button"
                    class="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 h-3 w-3">
                        <path d="M3 6h18"></path>
                        <path d="M7 12h10"></path>
                        <path d="M10 18h4"></path>
                    </svg>
                    <span id="sort-button-text">
                        @php
                            $sortLabels = [
                                'standard' => 'Standard',
                                'price_asc' => 'Price: (lowest first)',
                                'price_desc' => 'Price: (Highest first)',
                                'date_desc' => 'Date: (Newest first)',
                                'date_asc' => 'Date: (Oldest first)',
                                'year_desc' => 'Model Year: (Newest first)',
                                'year_asc' => 'Model Year: (Oldest First)',
                                'mileage_desc' => 'Mileage: (Highest first)',
                                'mileage_asc' => 'Mileage: (Lowest first)',
                                'fuel_efficiency_desc' => 'Km/l: (Highest first)',
                                'fuel_efficiency_asc' => 'Km/l: (Lowest first)',
                                'range_desc' => 'Range: (Highest first)',
                                'range_asc' => 'Range: (Lowest first)',
                                'battery_desc' => 'Battery capacity: (Highest first)',
                                'battery_asc' => 'Battery capacity: (Lowest first)',
                                'brand_asc' => 'Brand: (Alphabetical)',
                                'brand_desc' => 'Brand: (Reverse alphabetical)',
                                'engine_power_desc' => 'HK: (Highest first)',
                                'engine_power_asc' => 'HK: (Lowest first)',
                                'towing_weight_desc' => 'Trailer weight: (Heaviest first)',
                                'towing_weight_asc' => 'Trailer weight: (Lowest first)',
                                'top_speed_desc' => '0-100 km/h: (Highest first)',
                                'top_speed_asc' => '0-100 km/h: (Lowest first)',
                                'ownership_tax_desc' => 'Owner tax: (Highest first)',
                                'ownership_tax_asc' => 'Owner tax: (Lowest first)',
                                'first_reg_desc' => '1st reg: (Newest first)',
                                'first_reg_asc' => '1st reg: (Eldest first)',
                                'distance_asc' => 'Distance to seller: (Shortest distance)',
                                'distance_desc' => 'Distance to seller: (Longest distance)'
                            ];
                            $currentSort = request()->query('sort', 'standard');
                        @endphp
                        {{ $sortLabels[$currentSort] ?? 'Standard' }}
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1.5 h-3 w-3">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                
                <!-- Sort Dropdown Menu -->
                <div 
                    id="sort-dropdown"
                    class="absolute right-0 top-full mt-1 w-64 rounded-md border border-input bg-background shadow-lg z-50 hidden"
                >
                    <div class="max-h-96 overflow-y-auto py-1" style="scrollbar-width: thin; scrollbar-color: hsl(var(--muted)) transparent;">
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'standard' || !request()->has('sort')) bg-accent @endif" data-sort="standard">
                            Standard
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'price_asc') bg-accent @endif" data-sort="price_asc">
                            Price: (lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'price_desc') bg-accent @endif" data-sort="price_desc">
                            Price: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'date_desc') bg-accent @endif" data-sort="date_desc">
                            Date: (Newest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'date_asc') bg-accent @endif" data-sort="date_asc">
                            Date: (Oldest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'year_desc') bg-accent @endif" data-sort="year_desc">
                            Model Year: (Newest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'year_asc') bg-accent @endif" data-sort="year_asc">
                            Model Year: (Oldest First)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'mileage_desc') bg-accent @endif" data-sort="mileage_desc">
                            Mileage: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'mileage_asc') bg-accent @endif" data-sort="mileage_asc">
                            Mileage: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'fuel_efficiency_desc') bg-accent @endif" data-sort="fuel_efficiency_desc">
                            Km/l: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'fuel_efficiency_asc') bg-accent @endif" data-sort="fuel_efficiency_asc">
                            Km/l: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'range_desc') bg-accent @endif" data-sort="range_desc">
                            Range: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'range_asc') bg-accent @endif" data-sort="range_asc">
                            Range: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'battery_desc') bg-accent @endif" data-sort="battery_desc">
                            Battery capacity: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'battery_asc') bg-accent @endif" data-sort="battery_asc">
                            Battery capacity: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'brand_asc') bg-accent @endif" data-sort="brand_asc">
                            Brand: (Alphabetical)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'brand_desc') bg-accent @endif" data-sort="brand_desc">
                            Brand: (Reverse alphabetical)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'engine_power_desc') bg-accent @endif" data-sort="engine_power_desc">
                            HK: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'engine_power_asc') bg-accent @endif" data-sort="engine_power_asc">
                            HK: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'towing_weight_desc') bg-accent @endif" data-sort="towing_weight_desc">
                            Trailer weight: (Heaviest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'towing_weight_asc') bg-accent @endif" data-sort="towing_weight_asc">
                            Trailer weight: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'top_speed_desc') bg-accent @endif" data-sort="top_speed_desc">
                            0-100 km/h: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'top_speed_asc') bg-accent @endif" data-sort="top_speed_asc">
                            0-100 km/h: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'ownership_tax_desc') bg-accent @endif" data-sort="ownership_tax_desc">
                            Owner tax: (Highest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'ownership_tax_asc') bg-accent @endif" data-sort="ownership_tax_asc">
                            Owner tax: (Lowest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'first_reg_desc') bg-accent @endif" data-sort="first_reg_desc">
                            1st reg: (Newest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'first_reg_asc') bg-accent @endif" data-sort="first_reg_asc">
                            1st reg: (Eldest first)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'distance_asc') bg-accent @endif" data-sort="distance_asc">
                            Distance to seller: (Shortest distance)
                        </button>
                        <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'distance_desc') bg-accent @endif" data-sort="distance_desc">
                            Distance to seller: (Longest distance)
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Filters Button -->
            <button 
                type="button" 
                id="filter-button"
                class="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 h-3 w-3">
                    <line x1="4" x2="20" y1="21" y2="21"></line>
                    <line x1="4" x2="20" y1="7" y2="7"></line>
                    <line x1="4" x2="20" y1="3" y2="3"></line>
                    <line x1="4" x2="20" y1="11" y2="11"></line>
                    <line x1="4" x2="20" y1="15" y2="15"></line>
                </svg>
                More Filters
                <span id="filter-count" class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">
                    ({{ number_format($vehicles->total()) }})
                </span>
            </button>
        </div>
        
        <!-- Applied Filters Chips -->
        <div id="applied-filters-container" class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-border">
            <!-- Filter chips will be rendered here via JavaScript -->
        </div>
    </div>

    <!-- Vehicle Grid -->
    <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @forelse($vehicles as $vehicle)
        <div class="rounded-lg border border-border bg-card overflow-hidden p-0">
            <!-- Vehicle Image -->
            <div class="relative aspect-video overflow-hidden">
                <img
                    src="{{ $vehicle->images->first()?->thumbnail_url ?? '/placeholder-vehicle.jpg' }}"
                    alt="{{ $vehicle->brand_name }} {{ $vehicle->model_name }}"
                    class="h-full w-full object-cover transition-transform hover:scale-105"
                />
                <span class="absolute top-2 right-2 z-10 rounded-md bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">
                    {{ $vehicle->registration }}
                </span>
            </div>
            
            <!-- Vehicle Details -->
            <div class="px-4 py-4 space-y-4">
                <div class="flex flex-col gap-1">
                    <h3 class="flex items-center gap-2 text-xl font-bold">
                        {{ $vehicle->brand_name }} {{ $vehicle->model_name }}
                    </h3>
                    @if($vehicle->details?->version)
                    <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                        {{ $vehicle->details->version }}
                    </p>
                    @endif
                    <p class="text-primary text-2xl font-medium">
                        {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                    </p>
                </div>

                <div class="-mt-2 flex flex-wrap gap-2 text-xs">
                    @if($vehicle->details?->gear_type_name)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->details->gear_type_name }}</span>
                    @endif
                    @if($vehicle->details?->color_name)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->details->color_name }}</span>
                    @endif
                    @if($vehicle->category_name)
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->category_name }}</span>
                    @endif
                </div>

                <div class="text-muted-foreground grid grid-cols-2 gap-2 text-sm">
                    @if($vehicle->model_year_name)
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" x2="16" y1="2" y2="6"></line>
                            <line x1="8" x2="8" y1="2" y2="6"></line>
                            <line x1="3" x2="21" y1="10" y2="10"></line>
                        </svg>
                        <span>{{ $vehicle->model_year_name }}</span>
                    </div>
                    @endif
                    @if($vehicle->mileage || $vehicle->km_driven)
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>{{ number_format($vehicle->mileage ?? $vehicle->km_driven ?? 0) }} km</span>
                    </div>
                    @endif
                    @if($vehicle->fuel_type_name)
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="2" x2="22" y1="2" y2="2"></line>
                            <line x1="6" x2="6" y1="6" y2="22"></line>
                            <line x1="18" x2="18" y1="6" y2="22"></line>
                            <line x1="2" x2="22" y1="22" y2="22"></line>
                        </svg>
                        <span>{{ $vehicle->fuel_type_name }}</span>
                    </div>
                    @endif
                    @if($vehicle->vehicle_list_status_name)
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>{{ $vehicle->vehicle_list_status_name }}</span>
                    </div>
                    @endif
                    @if($vehicle->details?->condition_name)
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            <path d="M18.63 13A17.888 17.888 0 0 1 18 8"></path>
                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9s14 0 17-5c.34-.94.56-1.92.73-2.92"></path>
                            <path d="M2 2l20 20"></path>
                            <path d="M22 8A10 10 0 0 0 9.04 4.32"></path>
                        </svg>
                        <span>{{ $vehicle->details->condition_name }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Vehicle Actions -->
            <div class="mt-auto p-4 pt-2">
                <div class="flex w-full flex-col gap-2 sm:flex-row">
                    <a href="/vehicles/{{ $vehicle->id }}" class="flex-1">
                        <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                            View Details
                        </button>
                    </a>
                    <button class="inline-flex h-9 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                        Enquire
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
                <h3 class="text-lg font-semibold">No vehicles found</h3>
                <p class="text-muted-foreground mt-1">
                    Try adjusting your search or filter criteria.
                </p>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination (Placeholder) -->
    <div class="mt-8 flex items-center justify-center gap-2">
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Previous
        </button>
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            1
        </button>
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </button>
    </div>
</div>

<!-- Filter Drawer -->
<div id="filter-drawer" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <!-- Backdrop -->
    <div 
        id="filter-backdrop"
        class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 ease-out opacity-0"
        aria-hidden="true"
    ></div>
    
    <!-- Drawer Panel -->
    <div 
        id="filter-panel"
        class="absolute right-0 top-0 h-full w-full max-w-md bg-background shadow-2xl flex flex-col transform transition-transform duration-300 ease-out translate-x-full"
        role="dialog"
        aria-modal="true"
        aria-labelledby="filter-drawer-title"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-5 bg-background border-b border-border shrink-0">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </div>
                <div>
                    <h2 id="filter-drawer-title" class="text-xl font-semibold text-foreground">Advanced Filters</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Refine your search</p>
                </div>
            </div>
            <button
                id="filter-close-button"
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                aria-label="Close filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto overscroll-contain" style="scrollbar-width: thin; scrollbar-color: hsl(var(--muted)) transparent;">
            <div class="px-6 py-6 space-y-8">
            <!-- Tabs: Purchase/Leasing -->
            <div class="space-y-3">
                <label class="text-sm font-semibold text-foreground">Listing Type</label>
                <div class="flex gap-2" role="tablist">
                    @php
                        $purchaseType = $filterOptions['listingTypes']->firstWhere('name', 'Purchase');
                        $leasingType = $filterOptions['listingTypes']->firstWhere('name', 'Leasing');
                        $defaultListingTypeId = $currentFilters['listing_type_id'] ?? ($purchaseType ? $purchaseType->id : '');
                        $isPurchaseActive = !isset($currentFilters['listing_type_id']) || (isset($currentFilters['listing_type_id']) && $currentFilters['listing_type_id'] == ($purchaseType ? $purchaseType->id : ''));
                        $isLeasingActive = isset($currentFilters['listing_type_id']) && $leasingType && $currentFilters['listing_type_id'] == $leasingType->id;
                    @endphp
                    @if($purchaseType)
                        <label class="tab-button flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border @if($isPurchaseActive) bg-accent border-primary @else border-input @endif" data-tab="purchase" data-listing-type-id="{{ $purchaseType->id }}">
                            <input 
                                type="radio" 
                                name="listing_type_id_radio" 
                                value="{{ $purchaseType->id }}"
                                class="sr-only peer"
                                @if($isPurchaseActive) checked @endif
                            >
                            <span class="peer-checked:font-semibold">Purchase</span>
                        </label>
                    @endif
                    @if($leasingType)
                        <label class="tab-button flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border @if($isLeasingActive) bg-accent border-primary @else border-input @endif" data-tab="leasing" data-listing-type-id="{{ $leasingType->id }}">
                            <input 
                                type="radio" 
                                name="listing_type_id_radio" 
                                value="{{ $leasingType->id }}"
                                class="sr-only peer"
                                @if($isLeasingActive) checked @endif
                            >
                            <span class="peer-checked:font-semibold">Leasing</span>
                        </label>
                    @endif
                </div>
                <input type="hidden" name="listing_type_id" id="listing-type-input" value="{{ $defaultListingTypeId }}">
            </div>

            <!-- Price Range -->
            <div class="space-y-4">
                <label class="text-sm font-semibold text-foreground">Price Range</label>
                <!-- Input Fields -->
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <label for="price-from" class="sr-only">Price From</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">kr</span>
                            <input 
                                type="number" 
                                id="price-from"
                                name="price_from" 
                                placeholder="Min"
                                min="0"
                                max="1000000"
                                value="{{ $currentFilters['price_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background pl-12 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <span class="text-muted-foreground text-sm font-medium">to</span>
                    <div class="flex-1">
                        <label for="price-to" class="sr-only">Price To</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">kr</span>
                            <input 
                                type="number" 
                                id="price-to"
                                name="price_to" 
                                placeholder="Max"
                                min="0"
                                max="1000000"
                                value="{{ $currentFilters['price_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background pl-12 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                </div>
                <!-- Range Slider -->
                <div class="relative px-2 py-4">
                    <div class="relative h-2 bg-muted rounded-full">
                        <div id="price-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                        <input 
                            type="range" 
                            id="price-slider-min"
                            min="0"
                            max="1000000"
                            step="1000"
                            value="{{ $currentFilters['price_from'] ?? 0 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                        >
                        <input 
                            type="range" 
                            id="price-slider-max"
                            min="0"
                            max="1000000"
                            step="1000"
                            value="{{ $currentFilters['price_to'] ?? 1000000 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="price-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="price-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Brand & Model -->
            <div class="space-y-5">
                <!-- Brand -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Brand</label>
                        <select 
                            name="brand_id" 
                            id="brand-select"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                            <option value="">All Makes</option>
                            @foreach($filterOptions['brands'] as $brand)
                            <option value="{{ $brand->id }}" @if(isset($currentFilters['brand_id']) && $currentFilters['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                </div>

                <!-- Model (filtered by brand) -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Model</label>
                    <select 
                        name="model_id" 
                        id="model-select"
                        class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                    >
                        <option value="">All Models</option>
                        @foreach($filterOptions['models'] as $model)
                            <option value="{{ $model->id }}" data-brand-id="{{ $model->brand_id }}" @if(isset($currentFilters['model_id']) && $currentFilters['model_id'] == $model->id) selected @endif>{{ $model->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Vehicle Type (renamed from Category) -->
            <div class="space-y-4">
                <label class="text-sm font-semibold text-foreground">Vehicle Type</label>
                <select 
                    name="category_id" 
                    class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                >
                    <option value="">All Vehicle Types</option>
                    @foreach($filterOptions['categories'] as $category)
                        <option value="{{ $category->id }}" @if(isset($currentFilters['category_id']) && $currentFilters['category_id'] == $category->id) selected @endif>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Owner Tax Range -->
            <div class="space-y-4">
                <label class="text-sm font-semibold text-foreground">Owner Tax</label>
                <!-- Input Fields -->
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <label for="owner-tax-from" class="sr-only">Owner Tax From</label>
                        <input 
                            type="number" 
                            id="owner-tax-from"
                            name="ownership_tax_from" 
                            placeholder="Min"
                            min="0"
                            max="100000"
                            value="{{ $currentFilters['ownership_tax_from'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>
                    <span class="text-muted-foreground text-sm font-medium">to</span>
                    <div class="flex-1">
                        <label for="owner-tax-to" class="sr-only">Owner Tax To</label>
                        <input 
                            type="number" 
                            id="owner-tax-to"
                            name="ownership_tax_to" 
                            placeholder="Max"
                            min="0"
                            max="100000"
                            value="{{ $currentFilters['ownership_tax_to'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>
                </div>
                <!-- Range Slider -->
                <div class="relative px-2 py-4">
                    <div class="relative h-2 bg-muted rounded-full">
                        <div id="owner-tax-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                        <input 
                            type="range" 
                            id="owner-tax-slider-min"
                            min="0"
                            max="100000"
                            step="100"
                            value="{{ $currentFilters['ownership_tax_from'] ?? 0 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                        >
                        <input 
                            type="range" 
                            id="owner-tax-slider-max"
                            min="0"
                            max="100000"
                            step="100"
                            value="{{ $currentFilters['ownership_tax_to'] ?? 100000 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="owner-tax-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="owner-tax-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Model Year Range -->
            <div class="space-y-4">
                <label class="text-sm font-semibold text-foreground">Model Year</label>
                <!-- Input Fields -->
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <label for="year-from" class="sr-only">Year From</label>
                        <input 
                            type="number" 
                            id="year-from"
                            name="year_from" 
                            placeholder="From"
                            min="1975"
                            max="{{ date('Y') }}"
                            value="{{ $currentFilters['year_from'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>
                    <span class="text-muted-foreground text-sm font-medium">to</span>
                    <div class="flex-1">
                        <label for="year-to" class="sr-only">Year To</label>
                        <input 
                            type="number" 
                            id="year-to"
                            name="year_to" 
                            placeholder="To"
                            min="1975"
                            max="{{ date('Y') + 1 }}"
                            value="{{ $currentFilters['year_to'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>
                </div>
                <!-- Range Slider -->
                <div class="relative px-2 py-4">
                    <div class="relative h-2 bg-muted rounded-full">
                        <div id="year-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                        <input 
                            type="range" 
                            id="year-slider-min"
                            min="1975"
                            max="{{ date('Y') + 1 }}"
                            step="1"
                            value="{{ $currentFilters['year_from'] ?? 1975 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                        >
                        <input 
                            type="range" 
                            id="year-slider-max"
                            min="1975"
                            max="{{ date('Y') + 1 }}"
                            step="1"
                            value="{{ $currentFilters['year_to'] ?? (date('Y') + 1) }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="year-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="year-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="space-y-5">
                <!-- Mileage Range -->
                <div class="space-y-4">
                    <label class="text-sm font-semibold text-foreground">Mileage (km)</label>
                    <!-- Input Fields -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="mileage-from" class="sr-only">Mileage From</label>
                            <input 
                                type="number" 
                                id="mileage-from"
                                name="mileage_from" 
                                placeholder="Min"
                                min="0"
                                max="500000"
                                value="{{ $currentFilters['mileage_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                        <span class="text-muted-foreground text-sm font-medium">to</span>
                        <div class="flex-1">
                            <label for="mileage-to" class="sr-only">Mileage To</label>
                            <input 
                                type="number" 
                                id="mileage-to"
                                name="mileage_to" 
                                placeholder="Max"
                                min="0"
                                max="500000"
                                value="{{ $currentFilters['mileage_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <!-- Range Slider -->
                    <div class="relative px-2 py-4">
                        <div class="relative h-2 bg-muted rounded-full">
                            <div id="mileage-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                            <input 
                                type="range" 
                                id="mileage-slider-min"
                                min="0"
                                max="500000"
                                step="1000"
                                value="{{ $currentFilters['mileage_from'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                            >
                            <input 
                                type="range" 
                                id="mileage-slider-max"
                                min="0"
                                max="500000"
                                step="1000"
                                value="{{ $currentFilters['mileage_to'] ?? 500000 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="mileage-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="mileage-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Price Type -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Price Type</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($filterOptions['priceTypes'] as $priceType)
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['price_type_id']) && (is_array($currentFilters['price_type_id']) ? in_array($priceType->id, $currentFilters['price_type_id']) : $currentFilters['price_type_id'] == $priceType->id)) bg-accent border-primary @endif">
                                <input 
                                    type="checkbox" 
                        name="price_type_id[]" 
                                    value="{{ $priceType->id }}"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['price_type_id']) && (is_array($currentFilters['price_type_id']) ? in_array($priceType->id, $currentFilters['price_type_id']) : $currentFilters['price_type_id'] == $priceType->id)) checked @endif
                                >
                                <span>{{ $priceType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Condition -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Condition</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="condition-radio-label inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') bg-accent border-primary @else border-input @endif">
                            <input 
                                type="radio" 
                                name="condition_id" 
                                value=""
                                class="sr-only peer condition-radio"
                                @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') checked @endif
                            >
                            <span class="peer-checked:font-semibold">All</span>
                        </label>
                        @foreach($filterOptions['conditions'] as $condition)
                            <label class="condition-radio-label inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition->id) bg-accent border-primary @else border-input @endif">
                                <input 
                                    type="radio" 
                                    name="condition_id" 
                                    value="{{ $condition->id }}"
                                    class="sr-only peer condition-radio"
                                    @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition->id) checked @endif
                                >
                                <span class="peer-checked:font-semibold">{{ $condition->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Vehicle Body Type -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Body Type</label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $bodyTypeMap = [
                                'micro' => 'micro',
                                'stationcar' => 'stationcar',
                                'suv' => 'suv',
                                'cuv' => 'cuv',
                                'mpv' => 'mpv',
                                'sedan' => 'sedan',
                                'hatchback' => 'hatchback',
                                'cabriolet' => 'cabriolet',
                                'coupe' => 'coupe'
                            ];
                        @endphp
                        @foreach($filterOptions['bodyTypes'] as $bodyType)
                            @php
                                $bodyTypeNameLower = strtolower($bodyType->name);
                                $isRelevant = false;
                                foreach($bodyTypeMap as $key => $value) {
                                    if(str_contains($bodyTypeNameLower, $value) || str_contains($value, $bodyTypeNameLower)) {
                                        $isRelevant = true;
                                        break;
                                    }
                                }
                            @endphp
                            @if($isRelevant || in_array($bodyTypeNameLower, $bodyTypeMap))
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['body_type_id']) && (is_array($currentFilters['body_type_id']) ? in_array($bodyType->id, $currentFilters['body_type_id']) : $currentFilters['body_type_id'] == $bodyType->id)) bg-accent border-primary @endif">
                                    <input 
                                        type="checkbox" 
                                        name="body_type_id[]" 
                                        value="{{ $bodyType->id }}"
                                        class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        @if(isset($currentFilters['body_type_id']) && (is_array($currentFilters['body_type_id']) ? in_array($bodyType->id, $currentFilters['body_type_id']) : $currentFilters['body_type_id'] == $bodyType->id)) checked @endif
                                    >
                                    <span>{{ $bodyType->name }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Fuel Type -->
            <div>
                <label class="text-sm font-semibold text-foreground mb-3 block">Fuel Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($filterOptions['fuelTypes'] as $fuelType)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['fuel_type_id']) && (is_array($currentFilters['fuel_type_id']) ? in_array($fuelType->id, $currentFilters['fuel_type_id']) : $currentFilters['fuel_type_id'] == $fuelType->id)) bg-accent border-primary @endif">
                            <input 
                                type="checkbox" 
                                name="fuel_type_id[]" 
                                value="{{ $fuelType->id }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                @if(isset($currentFilters['fuel_type_id']) && (is_array($currentFilters['fuel_type_id']) ? in_array($fuelType->id, $currentFilters['fuel_type_id']) : $currentFilters['fuel_type_id'] == $fuelType->id)) checked @endif
                            >
                            <span>{{ $fuelType->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Gear Type -->
            <div>
                <label class="text-sm font-semibold text-foreground mb-3 block">Gear Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($filterOptions['gearTypes'] as $gearType)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['gear_type_id']) && (is_array($currentFilters['gear_type_id']) ? in_array($gearType->id, $currentFilters['gear_type_id']) : $currentFilters['gear_type_id'] == $gearType->id)) bg-accent border-primary @endif">
                            <input 
                                type="checkbox" 
                                name="gear_type_id[]" 
                                value="{{ $gearType->id }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                @if(isset($currentFilters['gear_type_id']) && (is_array($currentFilters['gear_type_id']) ? in_array($gearType->id, $currentFilters['gear_type_id']) : $currentFilters['gear_type_id'] == $gearType->id)) checked @endif
                            >
                            <span>{{ $gearType->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Drive Wheels -->
            <div>
                <label class="text-sm font-semibold text-foreground mb-3 block">Drive Wheels</label>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('fwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'fwd')) bg-accent border-primary @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="fwd"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('fwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'fwd')) checked @endif
                        >
                        <span>FWD</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('rwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'rwd')) bg-accent border-primary @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="rwd"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('rwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'rwd')) checked @endif
                        >
                        <span>RWD</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('awd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'awd')) bg-accent border-primary @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="awd"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('awd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'awd')) checked @endif
                        >
                        <span>AWD</span>
                    </label>
                </div>
            </div>

            <!-- Registration & Seller -->
            <div class="space-y-5">
                <!-- First Registration Year Range -->
                <div class="space-y-4">
                    <label class="text-sm font-semibold text-foreground">First Registration Year</label>
                    <!-- Input Fields -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="first-reg-year-from" class="sr-only">Year From</label>
                            <input 
                                type="number" 
                                id="first-reg-year-from"
                                name="first_registration_year_from" 
                                placeholder="From"
                                min="1975"
                                max="{{ date('Y') }}"
                                value="{{ $currentFilters['first_registration_year_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                        <span class="text-muted-foreground text-sm font-medium">to</span>
                        <div class="flex-1">
                            <label for="first-reg-year-to" class="sr-only">Year To</label>
                            <input 
                                type="number" 
                                id="first-reg-year-to"
                                name="first_registration_year_to" 
                                placeholder="To"
                                min="1975"
                                max="{{ date('Y') }}"
                                value="{{ $currentFilters['first_registration_year_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <!-- Range Slider -->
                    <div class="relative px-2 py-4">
                        <div class="relative h-2 bg-muted rounded-full">
                            <div id="first-reg-year-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                            <input 
                                type="range" 
                                id="first-reg-year-slider-min"
                                min="1975"
                                max="{{ date('Y') }}"
                                step="1"
                                value="{{ $currentFilters['first_registration_year_from'] ?? 1975 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                            >
                            <input 
                                type="range" 
                                id="first-reg-year-slider-max"
                                min="1975"
                                max="{{ date('Y') }}"
                                step="1"
                                value="{{ $currentFilters['first_registration_year_to'] ?? date('Y') }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="first-reg-year-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="first-reg-year-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Seller Type & Sales Type -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Seller Type -->
                    <div>
                        <label class="text-sm font-semibold text-foreground mb-3 block">Seller Type</label>
                        <div class="flex flex-col gap-2">
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('dealer', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'dealer')) bg-accent border-primary @endif">
                                <input 
                                    type="checkbox" 
                                    name="seller_type[]" 
                                    value="dealer"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('dealer', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'dealer')) checked @endif
                                >
                                <span>Dealer</span>
                            </label>
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('private', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'private')) bg-accent border-primary @endif">
                                <input 
                                    type="checkbox" 
                                    name="seller_type[]" 
                                    value="private"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('private', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'private')) checked @endif
                                >
                                <span>Private</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sales Type -->
                    <div>
                        <label class="text-sm font-semibold text-foreground mb-3 block">Sales Type</label>
                        <div class="flex flex-col gap-2">
                            @foreach($filterOptions['salesTypes'] as $salesType)
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['sales_type_id']) && (is_array($currentFilters['sales_type_id']) ? in_array($salesType->id, $currentFilters['sales_type_id']) : $currentFilters['sales_type_id'] == $salesType->id)) bg-accent border-primary @endif">
                                    <input 
                                        type="checkbox" 
                                        name="sales_type_id[]" 
                                        value="{{ $salesType->id }}"
                                        class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        @if(isset($currentFilters['sales_type_id']) && (is_array($currentFilters['sales_type_id']) ? in_array($salesType->id, $currentFilters['sales_type_id']) : $currentFilters['sales_type_id'] == $salesType->id)) checked @endif
                                    >
                                    <span>{{ $salesType->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Seller Distance -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Seller Distance (km)</label>
                    <input 
                        type="number" 
                        name="seller_distance" 
                        placeholder="Distance"
                        min="0"
                        value="{{ $currentFilters['seller_distance'] ?? '' }}"
                        class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                    >
                </div>
            </div>

            <!-- Performance -->
            <div class="space-y-5">
                <!-- Horsepower Range -->
                <div class="space-y-4">
                    <label class="text-sm font-semibold text-foreground">Horsepower (HP)</label>
                    <!-- Input Fields -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="horsepower-min" class="sr-only">Horsepower Min</label>
                            <input 
                                type="number" 
                                id="horsepower-min"
                                name="engine_power_from" 
                                placeholder="Min"
                                min="0"
                                max="1000"
                                value="{{ $currentFilters['engine_power_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                        <span class="text-muted-foreground text-sm font-medium">to</span>
                        <div class="flex-1">
                            <label for="horsepower-max" class="sr-only">Horsepower Max</label>
                            <input 
                                type="number" 
                                id="horsepower-max"
                                name="engine_power_to" 
                                placeholder="Max"
                                min="0"
                                max="1000"
                                value="{{ $currentFilters['engine_power_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <!-- Range Slider -->
                    <div class="relative px-2 py-4">
                        <div class="relative h-2 bg-muted rounded-full">
                            <div id="horsepower-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                            <input 
                                type="range" 
                                id="horsepower-slider-min"
                                min="0"
                                max="1000"
                                step="10"
                                value="{{ $currentFilters['engine_power_from'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                            >
                            <input 
                                type="range" 
                                id="horsepower-slider-max"
                                min="0"
                                max="1000"
                                step="10"
                                value="{{ $currentFilters['engine_power_to'] ?? 1000 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="horsepower-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="horsepower-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battery & Charging (EV) -->
            <div class="space-y-5">
                <!-- Battery Capacity -->
                <div class="space-y-4">
                    <label class="text-sm font-semibold text-foreground">Battery Capacity (kWh)</label>
                    <!-- Input Fields -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="battery-capacity-min" class="sr-only">Battery Capacity Min</label>
                            <input 
                                type="number" 
                                id="battery-capacity-min"
                                name="battery_capacity_from" 
                                placeholder="Min"
                                min="0"
                                max="200"
                                value="{{ $currentFilters['battery_capacity_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                        <span class="text-muted-foreground text-sm font-medium">to</span>
                        <div class="flex-1">
                            <label for="battery-capacity-max" class="sr-only">Battery Capacity Max</label>
                            <input 
                                type="number" 
                                id="battery-capacity-max"
                                name="battery_capacity_to" 
                                placeholder="Max"
                                min="0"
                                max="200"
                                value="{{ $currentFilters['battery_capacity_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <!-- Range Slider -->
                    <div class="relative px-2 py-4">
                        <div class="relative h-2 bg-muted rounded-full">
                            <div id="battery-capacity-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                            <input 
                                type="range" 
                                id="battery-capacity-slider-min"
                                min="0"
                                max="200"
                                step="5"
                                value="{{ $currentFilters['battery_capacity_from'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                            >
                            <input 
                                type="range" 
                                id="battery-capacity-slider-max"
                                min="0"
                                max="200"
                                step="5"
                                value="{{ $currentFilters['battery_capacity_to'] ?? 200 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="battery-capacity-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="battery-capacity-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Range (km) -->
                <div class="space-y-4">
                    <label class="text-sm font-semibold text-foreground">Range (km)</label>
                    <!-- Input Fields -->
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="range-km-from" class="sr-only">Range From</label>
                            <input 
                                type="number" 
                                id="range-km-from"
                                name="range_km_from" 
                                placeholder="Min"
                                min="0"
                                max="1000"
                                value="{{ $currentFilters['range_km_from'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                        <span class="text-muted-foreground text-sm font-medium">to</span>
                        <div class="flex-1">
                            <label for="range-km-to" class="sr-only">Range To</label>
                            <input 
                                type="number" 
                                id="range-km-to"
                                name="range_km_to" 
                                placeholder="Max"
                                min="0"
                                max="1000"
                                value="{{ $currentFilters['range_km_to'] ?? '' }}"
                                class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                    <!-- Range Slider -->
                    <div class="relative px-2 py-4">
                        <div class="relative h-2 bg-muted rounded-full">
                            <div id="range-km-range-track" class="absolute h-2 bg-primary rounded-full"></div>
                            <input 
                                type="range" 
                                id="range-km-slider-min"
                                min="0"
                                max="1000"
                                step="10"
                                value="{{ $currentFilters['range_km_from'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-10"
                            >
                            <input 
                                type="range" 
                                id="range-km-slider-max"
                                min="0"
                                max="1000"
                                step="10"
                                value="{{ $currentFilters['range_km_to'] ?? 1000 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="range-km-handle-min" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="range-km-handle-max" class="absolute w-5 h-5 bg-primary rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Charging Type -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Charging Type</label>
                    <select 
                        name="charging_type" 
                        class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                    >
                        <option value="">All</option>
                        <option value="AC" @if(isset($currentFilters['charging_type']) && $currentFilters['charging_type'] == 'AC') selected @endif>AC</option>
                        <option value="DC" @if(isset($currentFilters['charging_type']) && $currentFilters['charging_type'] == 'DC') selected @endif>DC</option>
                        <option value="AC/DC" @if(isset($currentFilters['charging_type']) && $currentFilters['charging_type'] == 'AC/DC') selected @endif>AC/DC</option>
                    </select>
                </div>
            </div>

            <!-- Economy & Environment -->
            <div class="space-y-5">
                <!-- Euro Norm -->
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Euro Norm</label>
                    <select 
                        name="euronorm" 
                        class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                    >
                        <option value="">All</option>
                        <option value="Euro 1" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 1') selected @endif>Euro 1</option>
                        <option value="Euro 2" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 2') selected @endif>Euro 2</option>
                        <option value="Euro 3" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 3') selected @endif>Euro 3</option>
                        <option value="Euro 4" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 4') selected @endif>Euro 4</option>
                        <option value="Euro 5" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 5') selected @endif>Euro 5</option>
                        <option value="Euro 6" @if(isset($currentFilters['euronorm']) && $currentFilters['euronorm'] == 'Euro 6') selected @endif>Euro 6</option>
                    </select>
                </div>
            </div>

            <!-- Physical Details -->
            <div class="space-y-5">
                <!-- Doors & Seats -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Doors Min -->
                    <div>
                        <label class="text-sm font-semibold text-foreground mb-3 block">Doors (Min)</label>
                        <input 
                            type="number" 
                            name="doors" 
                            placeholder="Minimum"
                            min="2"
                            max="6"
                            value="{{ $currentFilters['doors'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>

                    <!-- Seats Min -->
                    <div>
                        <label class="text-sm font-semibold text-foreground mb-3 block">Seats (Min)</label>
                        <input 
                            type="number" 
                            name="seats_min" 
                            placeholder="Minimum"
                            min="2"
                            max="9"
                            value="{{ $currentFilters['seats_min'] ?? '' }}"
                            class="w-full h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                        >
                    </div>
                </div>
            </div>

            <!-- Equipment -->
            <div class="space-y-5">
                <div>
                    <label class="text-sm font-semibold text-foreground mb-3 block">Equipment</label>
                            <div class="space-y-2">
                        @foreach($filterOptions['equipmentTypes'] as $equipmentType)
                            <div class="equipment-type-group border border-input rounded-lg overflow-hidden">
                                <button 
                                    type="button"
                                    class="equipment-type-toggle w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-foreground hover:bg-accent transition-colors"
                                    data-type-id="{{ $equipmentType->id }}"
                                >
                                    <span class="uppercase tracking-wide">{{ $equipmentType->name }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon transition-transform">
                                        <path d="m6 9 6 6 6-6"></path>
                                    </svg>
                                </button>
                                <div class="equipment-type-content hidden px-4 pb-3 pt-2">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($equipmentType->equipments as $equipment)
                                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['equipment_ids']) && (is_array($currentFilters['equipment_ids']) ? in_array($equipment->id, $currentFilters['equipment_ids']) : $currentFilters['equipment_ids'] == $equipment->id)) bg-accent border-primary @endif">
                                            <input 
                                                type="checkbox" 
                                                name="equipment_ids[]" 
                                                value="{{ $equipment->id }}"
                                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                @if(isset($currentFilters['equipment_ids']) && (is_array($currentFilters['equipment_ids']) ? in_array($equipment->id, $currentFilters['equipment_ids']) : $currentFilters['equipment_ids'] == $equipment->id)) checked @endif
                                            >
                                            <span>{{ $equipment->name }}</span>
                                        </label>
                                    @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="sticky bottom-0 px-6 py-4 bg-background border-t border-border shrink-0 flex items-center justify-between gap-3 shadow-lg z-10">
            <button
                id="filter-reset-button"
                type="button"
                class="flex h-10 items-center justify-center rounded-lg border border-input bg-background px-5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                Reset
            </button>
            <button
                id="filter-apply-button"
                type="button"
                class="flex h-10 flex-1 items-center justify-center rounded-lg bg-primary text-primary-foreground px-6 text-sm font-semibold transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 shadow-sm"
            >
                Apply Filters
                <span id="filter-apply-count" class="ml-2 rounded-full bg-primary-foreground/20 px-2 py-0.5 text-xs font-semibold">
                    ({{ number_format($vehicles->total()) }})
                </span>
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Custom scrollbar styling for filter drawer */
    #filter-panel .overflow-y-auto::-webkit-scrollbar {
        width: 8px;
    }
    
    #filter-panel .overflow-y-auto::-webkit-scrollbar-track {
        background: transparent;
    }
    
    #filter-panel .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: hsl(var(--muted));
        border-radius: 4px;
    }
    
    #filter-panel .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background-color: hsl(var(--muted-foreground) / 0.3);
    }
    
    /* Custom scrollbar styling for sort dropdown */
    #sort-dropdown .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    
    #sort-dropdown .overflow-y-auto::-webkit-scrollbar-track {
        background: transparent;
    }
    
    #sort-dropdown .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: hsl(var(--muted));
        border-radius: 3px;
    }
    
    #sort-dropdown .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background-color: hsl(var(--muted-foreground) / 0.3);
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        // Constants
        const vehicleGrid = document.querySelector('.grid.w-full');
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const sortButton = document.getElementById('sort-button');
        const sortDropdown = document.getElementById('sort-dropdown');
        const sortButtonText = document.getElementById('sort-button-text');
        const sortOptions = document.querySelectorAll('.sort-option');
        const filterButton = document.getElementById('filter-button');
        const filterDrawer = document.getElementById('filter-drawer');
        const filterPanel = document.getElementById('filter-panel');
        const filterCloseButton = document.getElementById('filter-close-button');
        const filterBackdrop = document.getElementById('filter-backdrop');
        const filterApplyButton = document.getElementById('filter-apply-button');
        const filterResetButton = document.getElementById('filter-reset-button');
        
        const sortLabels = {
            'standard': 'Standard',
            'price_asc': 'Price: (lowest first)',
            'price_desc': 'Price: (Highest first)',
            'date_desc': 'Date: (Newest first)',
            'date_asc': 'Date: (Oldest first)',
            'year_desc': 'Model Year: (Newest first)',
            'year_asc': 'Model Year: (Oldest First)',
            'mileage_desc': 'Mileage: (Highest first)',
            'mileage_asc': 'Mileage: (Lowest first)',
            'fuel_efficiency_desc': 'Km/l: (Highest first)',
            'fuel_efficiency_asc': 'Km/l: (Lowest first)',
            'range_desc': 'Range: (Highest first)',
            'range_asc': 'Range: (Lowest first)',
            'battery_desc': 'Battery capacity: (Highest first)',
            'battery_asc': 'Battery capacity: (Lowest first)',
            'brand_asc': 'Brand: (Alphabetical)',
            'brand_desc': 'Brand: (Reverse alphabetical)',
            'engine_power_desc': 'HK: (Highest first)',
            'engine_power_asc': 'HK: (Lowest first)',
            'towing_weight_desc': 'Trailer weight: (Heaviest first)',
            'towing_weight_asc': 'Trailer weight: (Lowest first)',
            'top_speed_desc': '0-100 km/h: (Highest first)',
            'top_speed_asc': '0-100 km/h: (Lowest first)',
            'ownership_tax_desc': 'Owner tax: (Highest first)',
            'ownership_tax_asc': 'Owner tax: (Lowest first)',
            'first_reg_desc': '1st reg: (Newest first)',
            'first_reg_asc': '1st reg: (Eldest first)',
            'distance_asc': 'Distance to seller: (Shortest distance)',
            'distance_desc': 'Distance to seller: (Longest distance)'
        };
        
        let searchDebounceTimer = null;
        let isLoading = false;
        
        // Format currency helper (matches PHP FormatHelper)
        function formatCurrency(amount) {
            if (amount === null || amount === undefined) {
                return 'N/A';
            }
            return new Intl.NumberFormat('da-DK', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount) + ' kr.';
        }
        
        // Render single vehicle card
        function renderVehicleCard(vehicle) {
            const details = vehicle.details || {};
            const imageUrl = vehicle.image_url || '/placeholder-vehicle.jpg';
            
            return `
                <div class="rounded-lg border border-border bg-card overflow-hidden p-0">
                    <!-- Vehicle Image -->
                    <div class="relative aspect-video overflow-hidden">
                        <img
                            src="${imageUrl}"
                            alt="${vehicle.brand_name || ''} ${vehicle.model_name || ''}"
                            class="h-full w-full object-cover transition-transform hover:scale-105"
                        />
                        <span class="absolute top-2 right-2 z-10 rounded-md bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">
                            ${vehicle.registration || ''}
                        </span>
                    </div>
                    
                    <!-- Vehicle Details -->
                    <div class="px-4 py-4 space-y-4">
                        <div class="flex flex-col gap-1">
                            <h3 class="flex items-center gap-2 text-xl font-bold">
                                ${vehicle.brand_name || ''} ${vehicle.model_name || ''}
                            </h3>
                            ${details.version ? `
                            <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                                ${details.version}
                            </p>
                            ` : ''}
                            <p class="text-primary text-2xl font-medium">
                                ${formatCurrency(vehicle.price)}
                            </p>
                        </div>

                        <div class="-mt-2 flex flex-wrap gap-2 text-xs">
                            ${details.gear_type_name ? `
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${details.gear_type_name}</span>
                            ` : ''}
                            ${details.color_name ? `
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${details.color_name}</span>
                            ` : ''}
                            ${vehicle.category_name ? `
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${vehicle.category_name}</span>
                            ` : ''}
                        </div>

                        <div class="text-muted-foreground grid grid-cols-2 gap-2 text-sm">
                            ${vehicle.model_year_name ? `
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" x2="16" y1="2" y2="6"></line>
                                    <line x1="8" x2="8" y1="2" y2="6"></line>
                                    <line x1="3" x2="21" y1="10" y2="10"></line>
                                </svg>
                                <span>${vehicle.model_year_name}</span>
                            </div>
                            ` : ''}
                            ${vehicle.mileage || vehicle.km_driven ? `
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                                <span>${new Intl.NumberFormat('da-DK').format(vehicle.mileage || vehicle.km_driven || 0)} km</span>
                            </div>
                            ` : ''}
                            ${vehicle.fuel_type_name ? `
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <line x1="2" x2="22" y1="2" y2="2"></line>
                                    <line x1="6" x2="6" y1="6" y2="22"></line>
                                    <line x1="18" x2="18" y1="6" y2="22"></line>
                                    <line x1="2" x2="22" y1="22" y2="22"></line>
                                </svg>
                                <span>${vehicle.fuel_type_name}</span>
                            </div>
                            ` : ''}
                            ${vehicle.vehicle_list_status_name ? `
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>${vehicle.vehicle_list_status_name}</span>
                            </div>
                            ` : ''}
                            ${details.condition_name ? `
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    <path d="M18.63 13A17.888 17.888 0 0 1 18 8"></path>
                                    <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9s14 0 17-5c.34-.94.56-1.92.73-2.92"></path>
                                    <path d="M2 2l20 20"></path>
                                    <path d="M22 8A10 10 0 0 0 9.04 4.32"></path>
                                </svg>
                                <span>${details.condition_name}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Vehicle Actions -->
                    <div class="mt-auto p-4 pt-2">
                        <div class="flex w-full flex-col gap-2 sm:flex-row">
                            <a href="/vehicles/${vehicle.id}" class="flex-1">
                                <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                    View Details
                                </button>
                            </a>
                            <button class="inline-flex h-9 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                Enquire
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Render vehicle grid
        function renderVehicleGrid(vehicles) {
            if (!vehicleGrid) return;
            
            if (vehicles.length === 0) {
                vehicleGrid.innerHTML = `
                    <div class="col-span-full flex items-center justify-center py-12">
                        <div class="flex flex-col items-center justify-center text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-muted-foreground">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                            <h3 class="text-lg font-semibold">No vehicles found</h3>
                            <p class="text-muted-foreground mt-1">
                                Try adjusting your search or filter criteria.
                            </p>
                        </div>
                    </div>
                `;
                return;
            }
            
            vehicleGrid.innerHTML = vehicles.map(vehicle => renderVehicleCard(vehicle)).join('');
        }
        
        // Render pagination
        function renderPagination(pagination) {
            const paginationContainer = document.querySelector('.mt-8.flex.items-center.justify-center.gap-2');
            if (!paginationContainer) return;
            
            const { current_page, last_page, total } = pagination;
            
            if (last_page <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <button 
                    ${current_page === 1 ? 'disabled' : ''}
                    data-page="${current_page - 1}"
                    class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    Previous
                </button>
            `;
            
            // Page numbers
            const maxPagesToShow = 7;
            let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(last_page, startPage + maxPagesToShow - 1);
            
            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }
            
            if (startPage > 1) {
                paginationHTML += `
                    <button data-page="1" class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        1
                    </button>
                `;
                if (startPage > 2) {
                    paginationHTML += `<span class="px-2 text-muted-foreground">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button 
                        data-page="${i}"
                        class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${i === current_page ? 'bg-accent' : ''}"
                    >
                        ${i}
                    </button>
                `;
            }
            
            if (endPage < last_page) {
                if (endPage < last_page - 1) {
                    paginationHTML += `<span class="px-2 text-muted-foreground">...</span>`;
                }
                paginationHTML += `
                    <button data-page="${last_page}" class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        ${last_page}
                    </button>
                `;
            }
            
            // Next button
            paginationHTML += `
                <button 
                    ${current_page === last_page ? 'disabled' : ''}
                    data-page="${current_page + 1}"
                    class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    Next
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
            
            paginationContainer.innerHTML = paginationHTML;
            
            // Add click handlers to pagination buttons
            paginationContainer.querySelectorAll('.pagination-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const page = parseInt(btn.getAttribute('data-page'));
                    if (page && !btn.disabled) {
                        fetchVehicles({ page });
                    }
                });
            });
        }
        
        // Show loading state
        function showLoading() {
            if (!vehicleGrid) return;
            isLoading = true;
            vehicleGrid.innerHTML = `
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg class="animate-spin h-8 w-8 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-muted-foreground">Loading vehicles...</p>
                    </div>
                </div>
            `;
        }
        
        // Show error state
        function showError(message) {
            if (!vehicleGrid) return;
            isLoading = false;
            vehicleGrid.innerHTML = `
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-6 w-6 text-destructive">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y2="12" y1="8"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg>
                        <h3 class="text-lg font-semibold">Error loading vehicles</h3>
                        <p class="text-muted-foreground mt-1">${message || 'Please try again later.'}</p>
                    </div>
                </div>
            `;
        }
        
        // Render filter chips
        function renderFilterChips() {
            const container = document.getElementById('applied-filters-container');
            if (!container) return;
            
            const chips = [];
            const filters = collectFilters();
            
            // Helper to get option text by value
            function getOptionText(selectIdOrName, value) {
                let select = document.getElementById(selectIdOrName);
                if (!select) {
                    select = document.querySelector(`select[name="${selectIdOrName}"]`);
                }
                if (!select) return null;
                const option = Array.from(select.options).find(opt => opt.value == value);
                return option ? option.textContent : null;
            }
            
            // Helper to get label text for checkbox/radio
            function getLabelText(name, value) {
                const input = document.querySelector(`[name="${name}"][value="${value}"]`);
                if (!input) return null;
                const label = input.closest('label');
                if (label) {
                    const span = label.querySelector('span');
                    return span ? span.textContent.trim() : label.textContent.trim();
                }
                return null;
            }
            
            // Search
            if (filters.search) {
                chips.push({
                    key: 'search',
                    label: `Search: "${filters.search}"`,
                    value: filters.search
                });
            }
            
            // Listing Type
            if (filters.listing_type_id) {
                const listingTypeName = getLabelText('listing_type_id_radio', filters.listing_type_id);
                if (listingTypeName) {
                    chips.push({
                        key: 'listing_type_id',
                        label: `Type: ${listingTypeName}`,
                        value: filters.listing_type_id
                    });
                }
            }
            
            // Brand
            if (filters.brand_id) {
                const brandName = getOptionText('brand_id', filters.brand_id);
                if (brandName) {
                    chips.push({
                        key: 'brand_id',
                        label: `Brand: ${brandName}`,
                        value: filters.brand_id
                    });
                }
            }
            
            // Model
            if (filters.model_id) {
                const modelName = getOptionText('model_id', filters.model_id);
                if (modelName) {
                    chips.push({
                        key: 'model_id',
                        label: `Model: ${modelName}`,
                        value: filters.model_id
                    });
                }
            }
            
            // Category
            if (filters.category_id) {
                const categoryName = getOptionText('category_id', filters.category_id);
                if (categoryName) {
                    chips.push({
                        key: 'category_id',
                        label: `Category: ${categoryName}`,
                        value: filters.category_id
                    });
                }
            }
            
            // Price range
            if (filters.price_from || filters.price_to) {
                const from = filters.price_from ? formatCurrency(filters.price_from).replace(' kr.', '') : '';
                const to = filters.price_to ? formatCurrency(filters.price_to).replace(' kr.', '') : '';
                if (from && to) {
                    chips.push({
                        key: 'price_range',
                        label: `Price: ${from} - ${to} kr.`,
                        value: { from: filters.price_from, to: filters.price_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'price_from',
                        label: `Price: From ${from} kr.`,
                        value: filters.price_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'price_to',
                        label: `Price: Up to ${to} kr.`,
                        value: filters.price_to
                    });
                }
            }
            
            // Year range
            if (filters.year_from || filters.year_to) {
                const from = filters.year_from || '';
                const to = filters.year_to || '';
                if (from && to) {
                    chips.push({
                        key: 'year_range',
                        label: `Year: ${from} - ${to}`,
                        value: { from: filters.year_from, to: filters.year_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'year_from',
                        label: `Year: From ${from}`,
                        value: filters.year_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'year_to',
                        label: `Year: Up to ${to}`,
                        value: filters.year_to
                    });
                }
            }
            
            // Mileage range
            if (filters.mileage_from || filters.mileage_to) {
                const from = filters.mileage_from ? new Intl.NumberFormat('en-US').format(filters.mileage_from) : '';
                const to = filters.mileage_to ? new Intl.NumberFormat('en-US').format(filters.mileage_to) : '';
                if (from && to) {
                    chips.push({
                        key: 'mileage_range',
                        label: `Mileage: ${from} - ${to} km`,
                        value: { from: filters.mileage_from, to: filters.mileage_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'mileage_from',
                        label: `Mileage: From ${from} km`,
                        value: filters.mileage_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'mileage_to',
                        label: `Mileage: Up to ${to} km`,
                        value: filters.mileage_to
                    });
                }
            }
            
            // Condition
            if (filters.condition_id) {
                const conditionName = getLabelText('condition_id', filters.condition_id);
                if (conditionName) {
                    chips.push({
                        key: 'condition_id',
                        label: `Condition: ${conditionName}`,
                        value: filters.condition_id
                    });
                }
            }
            
            // Body types
            if (filters.body_type_id && Array.isArray(filters.body_type_id)) {
                filters.body_type_id.forEach(id => {
                    const name = getLabelText('body_type_id[]', id);
                    if (name) {
                        chips.push({
                            key: 'body_type_id',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Fuel types
            if (filters.fuel_type_id && Array.isArray(filters.fuel_type_id)) {
                filters.fuel_type_id.forEach(id => {
                    const name = getLabelText('fuel_type_id[]', id);
                    if (name) {
                        chips.push({
                            key: 'fuel_type_id',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Gear types
            if (filters.gear_type_id && Array.isArray(filters.gear_type_id)) {
                filters.gear_type_id.forEach(id => {
                    const name = getLabelText('gear_type_id[]', id);
                    if (name) {
                        chips.push({
                            key: 'gear_type_id',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Price types
            if (filters.price_type_id && Array.isArray(filters.price_type_id)) {
                filters.price_type_id.forEach(id => {
                    const name = getLabelText('price_type_id[]', id);
                    if (name) {
                        chips.push({
                            key: 'price_type_id',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Equipment
            if (filters.equipment_ids && Array.isArray(filters.equipment_ids)) {
                filters.equipment_ids.forEach(id => {
                    const name = getLabelText('equipment_ids[]', id);
                    if (name) {
                        chips.push({
                            key: 'equipment_ids',
                            label: name,
                            value: id,
                            isArray: true
                        });
                    }
                });
            }
            
            // Render chips
            if (chips.length === 0) {
                container.innerHTML = '';
                return;
            }
            
            container.innerHTML = chips.map(chip => `
                <div class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary border border-primary/20">
                    <span>${chip.label}</span>
                    <button 
                        type="button"
                        class="filter-chip-remove ml-0.5 rounded-full hover:bg-primary/20 p-0.5 transition-colors"
                        data-filter-key="${chip.key}"
                        data-filter-value="${typeof chip.value === 'object' ? JSON.stringify(chip.value) : chip.value}"
                        data-is-array="${chip.isArray || false}"
                        aria-label="Remove filter"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
            
            // Add click handlers to remove chips
            container.querySelectorAll('.filter-chip-remove').forEach(btn => {
                btn.addEventListener('click', () => {
                    const key = btn.getAttribute('data-filter-key');
                    const value = btn.getAttribute('data-filter-value');
                    const isArray = btn.getAttribute('data-is-array') === 'true';
                    
                    // Remove filter from DOM
                    if (isArray) {
                        const checkbox = document.querySelector(`[name="${key}[]"][value="${value}"]`);
                        if (checkbox) checkbox.checked = false;
                    } else if (key === 'search') {
                        if (searchInput) searchInput.value = '';
                    } else if (key === 'listing_type_id') {
                        // Reset to Purchase (default)
                        const purchaseLabel = document.querySelector('label[data-tab="purchase"]');
                        const listingTypeInput = document.getElementById('listing-type-input');
                        if (purchaseLabel) {
                            const purchaseRadio = purchaseLabel.querySelector('input[type="radio"]');
                            if (purchaseRadio) {
                                purchaseRadio.checked = true;
                                const purchaseListingTypeId = purchaseLabel.getAttribute('data-listing-type-id');
                                if (listingTypeInput && purchaseListingTypeId) {
                                    listingTypeInput.value = purchaseListingTypeId;
                                }
                                if (typeof updateTabStyles === 'function') {
                                    updateTabStyles();
                                }
                            }
                        }
                    } else if (key === 'price_range') {
                        const priceFrom = document.querySelector('[name="price_from"]');
                        const priceTo = document.querySelector('[name="price_to"]');
                        if (priceFrom) priceFrom.value = '';
                        if (priceTo) priceTo.value = '';
                    } else if (key === 'year_range') {
                        const yearFrom = document.querySelector('[name="year_from"]');
                        const yearTo = document.querySelector('[name="year_to"]');
                        if (yearFrom) yearFrom.value = '';
                        if (yearTo) yearTo.value = '';
                    } else if (key === 'mileage_range') {
                        const mileageFrom = document.querySelector('[name="mileage_from"]');
                        const mileageTo = document.querySelector('[name="mileage_to"]');
                        if (mileageFrom) mileageFrom.value = '';
                        if (mileageTo) mileageTo.value = '';
                    } else {
                        const input = document.querySelector(`[name="${key}"]`);
                        if (input) {
                            if (input.type === 'radio' || input.type === 'checkbox') {
                                input.checked = false;
                            } else {
                                input.value = '';
                            }
                        }
                    }
                    
                    // Re-apply filters
                    autoApplyFilters();
                });
            });
        }
        
        // Centralized fetch vehicles function
        async function fetchVehicles(params = {}) {
            if (isLoading) return;
            
            // Get current URL parameters
            const url = new URL(window.location.href);
            const currentParams = new URLSearchParams(url.search);
            
            // Update with new parameters
            Object.keys(params).forEach(key => {
                if (params[key] === null || params[key] === '' || params[key] === undefined) {
                    currentParams.delete(key);
                } else if (Array.isArray(params[key])) {
                    currentParams.delete(key);
                    params[key].forEach(val => currentParams.append(key + '[]', val));
                } else {
                    currentParams.set(key, params[key]);
                }
            });
            
            // Build query string
            const queryString = currentParams.toString();
            const requestUrl = '/vehicles' + (queryString ? '?' + queryString : '');
            
            // Show loading
            showLoading();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            try {
                const response = await fetch(requestUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Render vehicles and pagination
                renderVehicleGrid(data.vehicles);
                renderPagination(data.pagination);
                
                // Update count badges
                const filterCount = document.getElementById('filter-count');
                const filterApplyCount = document.getElementById('filter-apply-count');
                const totalCount = data.pagination?.total || 0;
                const formattedCount = new Intl.NumberFormat('en-US').format(totalCount);
                
                if (filterCount) {
                    filterCount.textContent = formattedCount;
                }
                if (filterApplyCount) {
                    filterApplyCount.textContent = formattedCount;
                }
                
                // Update filter chips
                renderFilterChips();
                
                // Update sort button text if sort changed
                if (params.sort !== undefined) {
                    const sortValue = params.sort || 'standard';
                    if (sortButtonText) {
                        sortButtonText.textContent = sortLabels[sortValue] || 'Standard';
                    }
                    // Update active sort option
                    sortOptions.forEach(opt => {
                        opt.classList.remove('bg-accent');
                        if (opt.getAttribute('data-sort') === sortValue) {
                            opt.classList.add('bg-accent');
                        }
                    });
                }
                
                isLoading = false;
            } catch (error) {
                console.error('Error fetching vehicles:', error);
                showError('Failed to load vehicles. Please try again.');
            }
        }
        
        // Search form handler with debounce
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    const searchValue = e.target.value.trim();
                    fetchVehicles({ search: searchValue || null, page: 1 });
                }, 300);
            });
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    const searchValue = e.target.value.trim();
                    fetchVehicles({ search: searchValue || null, page: 1 });
                }
            });
        }
        
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                const searchValue = searchInput?.value.trim();
                fetchVehicles({ search: searchValue || null, page: 1 });
            });
        }
        
        // Sort dropdown functionality
        if (sortButton && sortDropdown) {
            sortButton.addEventListener('click', (e) => {
                e.stopPropagation();
                sortDropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!sortButton.contains(e.target) && !sortDropdown.contains(e.target)) {
                    sortDropdown.classList.add('hidden');
                }
            });
            
            // Handle sort option selection
            sortOptions.forEach(option => {
                option.addEventListener('click', () => {
                    const sortValue = option.getAttribute('data-sort');
                    const sortLabel = sortLabels[sortValue] || 'Standard';
                    
                    // Update button text
                    if (sortButtonText) {
                        sortButtonText.textContent = sortLabel;
                    }
                    
                    // Update active state
                    sortOptions.forEach(opt => opt.classList.remove('bg-accent'));
                    option.classList.add('bg-accent');
                    
                    // Close dropdown
                    sortDropdown.classList.add('hidden');
                    
                    // Fetch vehicles with new sort parameter
                    fetchVehicles({ sort: sortValue === 'standard' ? null : sortValue, page: 1 });
                });
            });
        }
        
        // Sticky search bar on scroll
        const searchBarContainer = document.getElementById('search-bar-container');
        let lastScrollY = window.scrollY;
        let originalOffsetTop = null;
        let isSticky = false;
        
        function handleStickySearchBar() {
            if (!searchBarContainer) return;
            
            const currentScrollY = window.scrollY;
            const scrollDirection = currentScrollY > lastScrollY ? 'down' : 'up';
            
            // Get original position on first load (before any sticky behavior)
            if (originalOffsetTop === null) {
                originalOffsetTop = searchBarContainer.offsetTop;
            }
            
            const rect = searchBarContainer.getBoundingClientRect();
            const isAtTop = rect.top <= 0;
            
            // When scrolling down and search bar reaches top - make it sticky
            if (scrollDirection === 'down' && isAtTop && !isSticky) {
                searchBarContainer.classList.add('sticky', 'top-0', 'z-30');
                isSticky = true;
            }
            // When scrolling back up past original position - remove sticky
            else if (scrollDirection === 'up' && currentScrollY < originalOffsetTop && isSticky) {
                searchBarContainer.classList.remove('sticky', 'top-0', 'z-30');
                isSticky = false;
            }
            // When scrolling up while sticky and element naturally comes back into view
            else if (scrollDirection === 'up' && isSticky && rect.top > 0) {
                searchBarContainer.classList.remove('sticky', 'top-0', 'z-30');
                isSticky = false;
            }
            
            lastScrollY = currentScrollY;
        }
        
        // Throttle scroll events for better performance
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) {
                cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = requestAnimationFrame(handleStickySearchBar);
        });
        
        // Initialize on load
        handleStickySearchBar();
        
        // Recalculate original position on resize (only if not sticky)
        window.addEventListener('resize', () => {
            if (!isSticky && originalOffsetTop !== null) {
                originalOffsetTop = searchBarContainer.offsetTop;
            }
        });
        
        // Filter drawer handlers
        if (filterButton) {
            filterButton.addEventListener('click', () => {
                openDrawer();
                setTimeout(() => {
                    sliderConfigs.forEach(config => initRangeSlider(config));
                    setupAutoApplyFilters();
                    setupEquipmentCollapsible();
                    updateConditionStyles();
                }, 50);
            });
        }

        if (filterCloseButton) {
            filterCloseButton.addEventListener('click', closeDrawer);
        }

        if (filterBackdrop) {
            filterBackdrop.addEventListener('click', closeDrawer);
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && filterDrawer && !filterDrawer.classList.contains('hidden')) {
                closeDrawer();
            }
        });

        function openDrawer() {
            if (!filterDrawer || !filterPanel || !filterBackdrop) return;
            
            filterDrawer.classList.remove('hidden');
            // Force reflow to ensure hidden class is removed before animation
            filterDrawer.offsetHeight;
            // Trigger animation
            requestAnimationFrame(() => {
                filterPanel.classList.remove('translate-x-full');
                filterBackdrop.style.opacity = '1';
            });
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            if (!filterDrawer || !filterPanel || !filterBackdrop) return;
            
            filterPanel.classList.add('translate-x-full');
            filterBackdrop.style.opacity = '0';
            // Wait for animation to complete before hiding
            setTimeout(() => {
                filterDrawer.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // Tab functionality for Purchase/Leasing (radio buttons)
        const tabRadios = document.querySelectorAll('input[name="listing_type_id_radio"]');
        const listingTypeInput = document.getElementById('listing-type-input');
        
        function updateTabStyles() {
            tabRadios.forEach(radio => {
                const label = radio.closest('label.tab-button');
                if (label) {
                    if (radio.checked) {
                        label.classList.add('bg-accent', 'border-primary');
                        label.classList.remove('border-input');
                    } else {
                        label.classList.remove('bg-accent', 'border-primary');
                        label.classList.add('border-input');
                    }
                }
            });
        }
        
        tabRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.checked) {
                // Update hidden input
                    const listingTypeId = radio.value;
                if (listingTypeInput) {
                    listingTypeInput.value = listingTypeId || '';
                    }
                    // Update tab styles
                    updateTabStyles();
                    // Auto-apply filters
                    autoApplyFilters();
                }
            });
        });

        // Initialize tab styles on page load
        updateTabStyles();

        // Brand-Model dependency: Filter models based on selected brand
        const brandSelect = document.getElementById('brand-select');
        const modelSelect = document.getElementById('model-select');
        
        function updateModelOptions() {
            if (!brandSelect || !modelSelect) return;
            
            const selectedBrandId = brandSelect.value;
            const modelOptions = modelSelect.querySelectorAll('option[data-brand-id]');
            
            modelOptions.forEach(option => {
                if (!selectedBrandId || option.getAttribute('data-brand-id') === selectedBrandId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset model selection if current model is not available for selected brand
            const selectedModelOption = modelSelect.options[modelSelect.selectedIndex];
            if (selectedModelOption && selectedModelOption.style.display === 'none') {
                modelSelect.value = '';
            }
        }
        
        if (brandSelect) {
        brandSelect.addEventListener('change', updateModelOptions);
        }
        
        // Initialize model options on page load
        updateModelOptions();
        
        // Condition radio button styling
        function updateConditionStyles() {
            const conditionRadios = document.querySelectorAll('input[name="condition_id"]');
            conditionRadios.forEach(radio => {
                const label = radio.closest('.condition-radio-label');
                if (label) {
                    if (radio.checked) {
                        label.classList.add('bg-accent', 'border-primary');
                        label.classList.remove('border-input');
                    } else {
                        label.classList.remove('bg-accent', 'border-primary');
                        label.classList.add('border-input');
                    }
                }
            });
        }
        
        // Set up condition radio listeners
        document.querySelectorAll('input[name="condition_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                updateConditionStyles();
            });
        });
        
        // Initialize condition styles
        updateConditionStyles();
        
        // Equipment collapsible functionality
        function setupEquipmentCollapsible() {
            if (!filterDrawer) return;
            
            const equipmentToggles = filterDrawer.querySelectorAll('.equipment-type-toggle');
            equipmentToggles.forEach(toggle => {
                // Remove existing listeners by cloning
                const newToggle = toggle.cloneNode(true);
                toggle.parentNode.replaceChild(newToggle, toggle);
                
                newToggle.addEventListener('click', () => {
                    const content = newToggle.nextElementSibling;
                    const icon = newToggle.querySelector('.equipment-type-icon');
                    
                    if (content) {
                        content.classList.toggle('hidden');
                        if (icon) {
                            icon.classList.toggle('rotate-180');
                        }
                    }
                });
            });
        }
        

        // Dual-handle range slider functionality
        function initRangeSlider(config) {
            const { minSlider, maxSlider, minInput, maxInput, minHandle, maxHandle, track, min, max } = config;
            
            if (!minSlider || !maxSlider || !minInput || !maxInput || !minHandle || !maxHandle || !track) return;
            
            function updateSlider() {
                const minVal = parseFloat(minSlider.value) || min;
                const maxVal = parseFloat(maxSlider.value) || max;
                
                // Ensure min doesn't exceed max and vice versa
                if (minVal > maxVal) {
                    minSlider.value = maxVal;
                    maxSlider.value = minVal;
                }
                
                const finalMin = Math.min(minVal, maxVal);
                const finalMax = Math.max(minVal, maxVal);
                
                // Update input fields
                minInput.value = finalMin || '';
                maxInput.value = finalMax || '';
                
                // Calculate percentages
                const minPercent = ((finalMin - min) / (max - min)) * 100;
                const maxPercent = ((finalMax - min) / (max - min)) * 100;
                
                // Update handle positions (w-5 h-5 = 20px, so -10px to center)
                minHandle.style.left = `calc(${minPercent}% - 10px)`;
                maxHandle.style.left = `calc(${maxPercent}% - 10px)`;
                
                // Update track fill
                track.style.left = `${minPercent}%`;
                track.style.width = `${maxPercent - minPercent}%`;
            }
            
            function updateFromInput(input, slider) {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    const clampedValue = Math.max(min, Math.min(max, value));
                    slider.value = clampedValue;
                    input.value = clampedValue || '';
                    updateSlider();
                }
            }
            
            // Initialize
            updateSlider();
            
            // Slider events
            minSlider.addEventListener('input', updateSlider);
            maxSlider.addEventListener('input', updateSlider);
            
            // Input events
            minInput.addEventListener('input', () => updateFromInput(minInput, minSlider));
            maxInput.addEventListener('input', () => updateFromInput(maxInput, maxSlider));
            
            // Handle drag events for visual handles
            let isDragging = false;
            let activeHandle = null;
            
            [minHandle, maxHandle].forEach((handle, index) => {
                handle.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    activeHandle = index === 0 ? minSlider : maxSlider;
                    e.preventDefault();
                });
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging || !activeHandle) return;
                
                const sliderContainer = activeHandle.closest('.relative');
                const rect = sliderContainer.getBoundingClientRect();
                const percent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const value = min + (percent / 100) * (max - min);
                const step = parseFloat(activeHandle.step) || 1;
                const steppedValue = Math.round(value / step) * step;
                const clampedValue = Math.max(min, Math.min(max, steppedValue));
                
                activeHandle.value = clampedValue;
                updateSlider();
            });
            
            document.addEventListener('mouseup', () => {
                isDragging = false;
                activeHandle = null;
            });
        }
        
        // Initialize all range sliders
        const sliderConfigs = [
            {
                minSlider: document.getElementById('price-slider-min'),
                maxSlider: document.getElementById('price-slider-max'),
                minInput: document.getElementById('price-from'),
                maxInput: document.getElementById('price-to'),
                minHandle: document.getElementById('price-handle-min'),
                maxHandle: document.getElementById('price-handle-max'),
                track: document.getElementById('price-range-track'),
                min: 0,
                max: 1000000
            },
            {
                minSlider: document.getElementById('year-slider-min'),
                maxSlider: document.getElementById('year-slider-max'),
                minInput: document.getElementById('year-from'),
                maxInput: document.getElementById('year-to'),
                minHandle: document.getElementById('year-handle-min'),
                maxHandle: document.getElementById('year-handle-max'),
                track: document.getElementById('year-range-track'),
                min: 1975,
                max: {{ date('Y') + 1 }}
            },
            {
                minSlider: document.getElementById('mileage-slider-min'),
                maxSlider: document.getElementById('mileage-slider-max'),
                minInput: document.getElementById('mileage-from'),
                maxInput: document.getElementById('mileage-to'),
                minHandle: document.getElementById('mileage-handle-min'),
                maxHandle: document.getElementById('mileage-handle-max'),
                track: document.getElementById('mileage-range-track'),
                min: 0,
                max: 500000
            },
            {
                minSlider: document.getElementById('first-reg-year-slider-min'),
                maxSlider: document.getElementById('first-reg-year-slider-max'),
                minInput: document.getElementById('first-reg-year-from'),
                maxInput: document.getElementById('first-reg-year-to'),
                minHandle: document.getElementById('first-reg-year-handle-min'),
                maxHandle: document.getElementById('first-reg-year-handle-max'),
                track: document.getElementById('first-reg-year-range-track'),
                min: 1975,
                max: {{ date('Y') }}
            },
            {
                minSlider: document.getElementById('horsepower-slider-min'),
                maxSlider: document.getElementById('horsepower-slider-max'),
                minInput: document.getElementById('horsepower-min'),
                maxInput: document.getElementById('horsepower-max'),
                minHandle: document.getElementById('horsepower-handle-min'),
                maxHandle: document.getElementById('horsepower-handle-max'),
                track: document.getElementById('horsepower-range-track'),
                min: 0,
                max: 1000
            },
            {
                minSlider: document.getElementById('battery-capacity-slider-min'),
                maxSlider: document.getElementById('battery-capacity-slider-max'),
                minInput: document.getElementById('battery-capacity-min'),
                maxInput: document.getElementById('battery-capacity-max'),
                minHandle: document.getElementById('battery-capacity-handle-min'),
                maxHandle: document.getElementById('battery-capacity-handle-max'),
                track: document.getElementById('battery-capacity-range-track'),
                min: 0,
                max: 200
            },
            {
                minSlider: document.getElementById('owner-tax-slider-min'),
                maxSlider: document.getElementById('owner-tax-slider-max'),
                minInput: document.getElementById('owner-tax-from'),
                maxInput: document.getElementById('owner-tax-to'),
                minHandle: document.getElementById('owner-tax-handle-min'),
                maxHandle: document.getElementById('owner-tax-handle-max'),
                track: document.getElementById('owner-tax-range-track'),
                min: 0,
                max: 100000
            },
            {
                minSlider: document.getElementById('range-km-slider-min'),
                maxSlider: document.getElementById('range-km-slider-max'),
                minInput: document.getElementById('range-km-from'),
                maxInput: document.getElementById('range-km-to'),
                minHandle: document.getElementById('range-km-handle-min'),
                maxHandle: document.getElementById('range-km-handle-max'),
                track: document.getElementById('range-km-range-track'),
                min: 0,
                max: 1000
            }
        ];

        // Close drawer
        filterCloseButton.addEventListener('click', closeDrawer);
        filterBackdrop.addEventListener('click', closeDrawer);

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !filterDrawer.classList.contains('hidden')) {
                closeDrawer();
            }
        });

        // Reset filters
        if (filterResetButton) {
        filterResetButton.addEventListener('click', () => {
            const inputs = filterDrawer.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else if (input.type === 'range') {
                    // Reset range sliders to their min/max defaults
                    const config = sliderConfigs.find(c => 
                        c.minSlider === input || c.maxSlider === input
                    );
                    if (config) {
                        if (input === config.minSlider) {
                            input.value = config.min;
                            config.minInput.value = '';
                        } else {
                            input.value = config.max;
                            config.maxInput.value = '';
                        }
                    }
                } else if (input.tagName === 'SELECT' && input.multiple) {
                    // Reset multi-select dropdowns
                    Array.from(input.options).forEach(option => {
                        option.selected = false;
                    });
                } else {
                    input.value = '';
                }
            });
            // Reset tabs to "Purchase" (default)
            const purchaseLabel = document.querySelector('label[data-tab="purchase"]');
            if (purchaseLabel) {
                const purchaseRadio = purchaseLabel.querySelector('input[type="radio"]');
                if (purchaseRadio) {
                    purchaseRadio.checked = true;
                    // Update hidden input
                    const purchaseListingTypeId = purchaseLabel.getAttribute('data-listing-type-id');
                    if (listingTypeInput && purchaseListingTypeId) {
                        listingTypeInput.value = purchaseListingTypeId;
                    }
                    // Update tab styles
                    if (typeof updateTabStyles === 'function') {
                        updateTabStyles();
                    }
                }
            }
            // Reset model options
            if (typeof updateModelOptions === 'function') {
            updateModelOptions();
            }
            // Reinitialize sliders after reset
            setTimeout(() => {
                sliderConfigs.forEach(config => initRangeSlider(config));
            }, 50);
        });
        }

        // Collect all filter values
        function collectFilters() {
            const filters = {};
            
            // Preserve search and sort parameters from URL
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search');
            if (search) filters.search = search;
            
            const sort = urlParams.get('sort');
            if (sort) filters.sort = sort;
            
            // Basic filters
            const listingTypeId = document.getElementById('listing-type-input')?.value;
            if (listingTypeId) filters.listing_type_id = listingTypeId;
            
            const categoryId = document.querySelector('[name="category_id"]')?.value;
            if (categoryId) filters.category_id = categoryId;
            
            const brandId = document.querySelector('[name="brand_id"]')?.value;
            if (brandId) filters.brand_id = brandId;
            
            const modelId = document.querySelector('[name="model_id"]')?.value;
            if (modelId) filters.model_id = modelId;
            
            // Price range
            const priceFrom = document.querySelector('[name="price_from"]')?.value;
            if (priceFrom) filters.price_from = priceFrom;
            
            const priceTo = document.querySelector('[name="price_to"]')?.value;
            if (priceTo) filters.price_to = priceTo;
            
            // Owner tax range
            const ownershipTaxFrom = document.querySelector('[name="ownership_tax_from"]')?.value;
            if (ownershipTaxFrom) filters.ownership_tax_from = ownershipTaxFrom;
            
            const ownershipTaxTo = document.querySelector('[name="ownership_tax_to"]')?.value;
            if (ownershipTaxTo) filters.ownership_tax_to = ownershipTaxTo;
            
            // Year range
            const yearFrom = document.querySelector('[name="year_from"]')?.value;
            if (yearFrom) filters.year_from = yearFrom;
            
            const yearTo = document.querySelector('[name="year_to"]')?.value;
            if (yearTo) filters.year_to = yearTo;
            
            // Mileage range
            const mileageFrom = document.querySelector('[name="mileage_from"]')?.value;
            if (mileageFrom) filters.mileage_from = mileageFrom;
            
            const mileageTo = document.querySelector('[name="mileage_to"]')?.value;
            if (mileageTo) filters.mileage_to = mileageTo;
            
            // Price Type (checkboxes)
            const priceTypeIds = Array.from(document.querySelectorAll('[name="price_type_id[]"]:checked')).map(cb => cb.value);
                if (priceTypeIds.length > 0) filters.price_type_id = priceTypeIds;
            
            const conditionId = document.querySelector('[name="condition_id"]:checked')?.value;
            if (conditionId) filters.condition_id = conditionId;
            
            const bodyTypeIds = Array.from(document.querySelectorAll('[name="body_type_id[]"]:checked')).map(cb => cb.value);
            if (bodyTypeIds.length > 0) filters.body_type_id = bodyTypeIds;
            
            const fuelTypeIds = Array.from(document.querySelectorAll('[name="fuel_type_id[]"]:checked')).map(cb => cb.value);
            if (fuelTypeIds.length > 0) filters.fuel_type_id = fuelTypeIds;
            
            const gearTypeIds = Array.from(document.querySelectorAll('[name="gear_type_id[]"]:checked')).map(cb => cb.value);
            if (gearTypeIds.length > 0) filters.gear_type_id = gearTypeIds;
            
            const driveAxles = Array.from(document.querySelectorAll('[name="drive_axles[]"]:checked')).map(cb => cb.value);
            if (driveAxles.length > 0) filters.drive_axles = driveAxles;
            
            // First registration year
            const firstRegYearFrom = document.querySelector('[name="first_registration_year_from"]')?.value;
            if (firstRegYearFrom) filters.first_registration_year_from = firstRegYearFrom;
            
            const firstRegYearTo = document.querySelector('[name="first_registration_year_to"]')?.value;
            if (firstRegYearTo) filters.first_registration_year_to = firstRegYearTo;
            
            // Seller type
            const sellerTypes = Array.from(document.querySelectorAll('[name="seller_type[]"]:checked')).map(cb => cb.value);
            if (sellerTypes.length > 0) filters.seller_type = sellerTypes;
            
            // Sales type
            const salesTypeIds = Array.from(document.querySelectorAll('[name="sales_type_id[]"]:checked')).map(cb => cb.value);
            if (salesTypeIds.length > 0) filters.sales_type_id = salesTypeIds;
            
            // Seller distance
            const sellerDistance = document.querySelector('[name="seller_distance"]')?.value;
            if (sellerDistance) filters.seller_distance = sellerDistance;
            
            // Performance
            const enginePowerFrom = document.querySelector('[name="engine_power_from"]')?.value;
            if (enginePowerFrom) filters.engine_power_from = enginePowerFrom;
            
            const enginePowerTo = document.querySelector('[name="engine_power_to"]')?.value;
            if (enginePowerTo) filters.engine_power_to = enginePowerTo;
            
            // Battery & Charging
            const batteryCapacityFrom = document.querySelector('[name="battery_capacity_from"]')?.value;
            if (batteryCapacityFrom) filters.battery_capacity_from = batteryCapacityFrom;
            
            const batteryCapacityTo = document.querySelector('[name="battery_capacity_to"]')?.value;
            if (batteryCapacityTo) filters.battery_capacity_to = batteryCapacityTo;
            
            const rangeKmFrom = document.querySelector('[name="range_km_from"]')?.value;
            if (rangeKmFrom) filters.range_km_from = rangeKmFrom;
            
            const rangeKmTo = document.querySelector('[name="range_km_to"]')?.value;
            if (rangeKmTo) filters.range_km_to = rangeKmTo;
            
            const chargingType = document.querySelector('[name="charging_type"]')?.value;
            if (chargingType) filters.charging_type = chargingType;
            
            // Economy & Environment
            const euronorm = document.querySelector('[name="euronorm"]')?.value;
            if (euronorm) filters.euronorm = euronorm;
            
            // Physical Details
            const doors = document.querySelector('[name="doors"]')?.value;
            if (doors) filters.doors = doors;
            
            const seatsMin = document.querySelector('[name="seats_min"]')?.value;
            if (seatsMin) filters.seats_min = seatsMin;
            
            // Equipment
            const equipmentIds = Array.from(document.querySelectorAll('[name="equipment_ids[]"]:checked')).map(cb => cb.value);
            if (equipmentIds.length > 0) filters.equipment_ids = equipmentIds;
            
            return filters;
        }
        
        // Auto-apply filters when any filter changes
        let filterDebounceTimer = null;
        
        function autoApplyFilters() {
            clearTimeout(filterDebounceTimer);
            filterDebounceTimer = setTimeout(() => {
                const filters = collectFilters();
                fetchVehicles({ ...filters, page: 1 });
            }, 500); // 500ms debounce for better UX
        }

        // Build query string from filters
        function buildQueryString(filters) {
            const params = new URLSearchParams();
            
            Object.keys(filters).forEach(key => {
                const value = filters[key];
                if (Array.isArray(value)) {
                    value.forEach(v => params.append(key + '[]', v));
                } else {
                    params.append(key, value);
                }
            });
            
            return params.toString();
        }

        // Apply filters via AJAX (manual apply button - also closes drawer)
        if (filterApplyButton) {
        filterApplyButton.addEventListener('click', async () => {
            const filters = collectFilters();
            
            // Show loading state
            filterApplyButton.disabled = true;
            filterApplyButton.textContent = 'Loading...';
            
            try {
                    // Use centralized fetchVehicles function
                    await fetchVehicles({ ...filters, page: 1 });
                
                // Close drawer
            closeDrawer();
            } catch (error) {
                console.error('Error applying filters:', error);
                alert('Failed to apply filters. Please try again.');
            } finally {
                // Reset button state
                filterApplyButton.disabled = false;
                filterApplyButton.textContent = 'Apply Filters';
            }
        });
        }
        
        // Set up auto-apply listeners for all filter inputs
        function setupAutoApplyFilters() {
            if (!filterDrawer) return;
            
            // Radio buttons (listing type, condition)
            filterDrawer.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', autoApplyFilters);
            });
            
            // Checkboxes (body type, fuel type, gear type, equipment, etc.)
            filterDrawer.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', autoApplyFilters);
            });
            
            // Select dropdowns
            filterDrawer.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', autoApplyFilters);
            });
            
            // Number inputs (with debounce)
            filterDrawer.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(filterDebounceTimer);
                    filterDebounceTimer = setTimeout(() => {
                        autoApplyFilters();
                    }, 800); // Longer debounce for number inputs
                });
            });
            
            // Range sliders (with debounce)
            filterDrawer.querySelectorAll('input[type="range"]').forEach(slider => {
                slider.addEventListener('input', () => {
                    clearTimeout(filterDebounceTimer);
                    filterDebounceTimer = setTimeout(() => {
                        autoApplyFilters();
                    }, 500);
                });
            });
        }
        
        
        // Initialize filter chips on page load
        renderFilterChips();
    })();
</script>
@endpush
@endsection

