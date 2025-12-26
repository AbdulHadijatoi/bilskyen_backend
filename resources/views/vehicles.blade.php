@extends('layouts.app')

@section('title', 'Vehicles | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    // $vehicles is provided by HomeController
@endphp

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <!-- Search Bar -->
    <div class="flex items-center gap-4">
        <!-- Search Input -->
        <form class="flex w-full items-center" method="GET" action="/vehicles">
            <input
                type="text"
                name="search"
                value="{{ request()->query('search', '') }}"
                placeholder="Search vehicles..."
                class="flex h-10 w-full rounded-l-md rounded-r-none border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                autocomplete="off"
            />
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-l-none rounded-r-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
                Search
            </button>
        </form>
        <!-- Filter Button -->
        <button 
            type="button" 
            id="filter-button"
            class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
        >
            <span class="mr-2">Filter</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
        </button>
    </div>

    <!-- Vehicle Grid -->
    <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @forelse($vehicles as $vehicle)
        <div class="rounded-lg border border-border bg-card overflow-hidden p-0">
            <!-- Vehicle Image -->
            <div class="relative aspect-video overflow-hidden">
                <img
                    src="{{ !empty($vehicle->images) && is_array($vehicle->images) ? ($vehicle->images[0] ?? '/placeholder-vehicle.jpg') : '/placeholder-vehicle.jpg' }}"
                    alt="{{ $vehicle->make }} {{ $vehicle->model }}"
                    class="h-full w-full object-cover transition-transform hover:scale-105"
                />
                <span class="absolute top-2 right-2 z-10 rounded-md bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">
                    {{ $vehicle->registration_number }}
                </span>
            </div>
            
            <!-- Vehicle Details -->
            <div class="px-4 py-4 space-y-4">
                <div class="flex flex-col gap-1">
                    <h3 class="flex items-center gap-2 text-xl font-bold">
                        {{ $vehicle->make }} {{ $vehicle->model }}
                    </h3>
                    <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                        {{ $vehicle->variant }}
                    </p>
                    <p class="text-primary text-2xl font-medium">
                        {{ FormatHelper::formatCurrency($vehicle->listing_price) }}
                    </p>
                </div>

                <div class="-mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->transmission_type }}</span>
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->color }}</span>
                    <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->vehicle_type }}</span>
                </div>

                <div class="text-muted-foreground grid grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" x2="16" y1="2" y2="6"></line>
                            <line x1="8" x2="8" y1="2" y2="6"></line>
                            <line x1="3" x2="21" y1="10" y2="10"></line>
                        </svg>
                        <span>{{ $vehicle->year }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>{{ number_format($vehicle->odometer) }} km</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="2" x2="22" y1="2" y2="2"></line>
                            <line x1="6" x2="6" y1="6" y2="22"></line>
                            <line x1="18" x2="18" y1="6" y2="22"></line>
                            <line x1="2" x2="22" y1="22" y2="22"></line>
                        </svg>
                        <span>{{ $vehicle->fuel_type }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>
                            {{ $vehicle->ownership_count }} Owner{{ $vehicle->ownership_count > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>{{ $vehicle->status }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            <path d="M18.63 13A17.888 17.888 0 0 1 18 8"></path>
                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9s14 0 17-5c.34-.94.56-1.92.73-2.92"></path>
                            <path d="M2 2l20 20"></path>
                            <path d="M22 8A10 10 0 0 0 9.04 4.32"></path>
                        </svg>
                        <span>{{ $vehicle->condition }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Vehicle Actions -->
            <div class="mt-auto p-4 pt-2">
                <div class="flex w-full flex-col gap-2 sm:flex-row">
                    <a href="/vehicles/{{ $vehicle->serial_no }}" class="flex-1">
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
<div id="filter-drawer" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div 
        id="filter-backdrop"
        class="absolute inset-0 bg-black/50 transition-opacity duration-300 opacity-0"
        aria-hidden="true"
    ></div>
    
    <!-- Drawer Panel -->
    <div 
        id="filter-panel"
        class="absolute right-0 top-0 h-full w-full max-w-lg bg-background shadow-xl flex flex-col transform transition-transform duration-300 translate-x-full"
        role="dialog"
        aria-modal="true"
        aria-labelledby="filter-drawer-title"
    >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-border px-6 py-4">
            <h2 id="filter-drawer-title" class="text-xl font-semibold">Advanced Filters</h2>
            <button
                id="filter-close-button"
                type="button"
                class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                aria-label="Close filters"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
            <!-- Basic Filters -->
            <div class="space-y-4">
                <h3 class="text-base font-semibold">Basic Filters</h3>
                
                <!-- Listing Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Listing Type</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="radio" 
                                name="listing_type_id" 
                                value=""
                                class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                checked
                            >
                            <span class="text-sm">All</span>
                        </label>
                        @foreach($filterOptions['listingTypes'] as $listingType)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="listing_type_id" 
                                    value="{{ $listingType->id }}"
                                    class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['listing_type_id']) && $currentFilters['listing_type_id'] == $listingType->id) checked @endif
                                >
                                <span class="text-sm">{{ $listingType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Category -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Category</label>
                    <select 
                        name="category_id" 
                        class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Categories</option>
                        @foreach($filterOptions['categories'] as $category)
                            <option value="{{ $category->id }}" @if(isset($currentFilters['category_id']) && $currentFilters['category_id'] == $category->id) selected @endif>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Make (Popular vs All Brands) -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Make</label>
                    <div class="space-y-3">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="brand_filter_type" 
                                    value="popular"
                                    class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                    checked
                                    id="brand-popular"
                                >
                                <span class="text-sm">Popular brands</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="brand_filter_type" 
                                    value="all"
                                    class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                    id="brand-all"
                                >
                                <span class="text-sm">All brands</span>
                            </label>
                        </div>
                        <select 
                            name="brand_id" 
                            id="brand-select"
                            class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Makes</option>
                            @foreach($filterOptions['popularBrands'] as $brand)
                                <option value="{{ $brand->id }}" class="brand-option brand-popular" @if(isset($currentFilters['brand_id']) && $currentFilters['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                            @endforeach
                            @foreach($filterOptions['brands'] as $brand)
                                <option value="{{ $brand->id }}" class="brand-option brand-all" style="display: none;" @if(isset($currentFilters['brand_id']) && $currentFilters['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Price & Year -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Price & Year</h3>
                
                <!-- Price Range -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Price Range</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="price-from" class="sr-only">Price From</label>
                            <input 
                                type="number" 
                                id="price-from"
                                name="price_from" 
                                placeholder="Min"
                                value="{{ $currentFilters['price_from'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
                        <div class="flex-1">
                            <label for="price-to" class="sr-only">Price To</label>
                            <input 
                                type="number" 
                                id="price-to"
                                name="price_to" 
                                placeholder="Max"
                                value="{{ $currentFilters['price_to'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>

                <!-- Model Year Range -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Model Year</label>
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
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
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
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Vehicle Details</h3>
                
                <!-- Mileage Range -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Mileage (km)</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="mileage-from" class="sr-only">Mileage From</label>
                            <input 
                                type="number" 
                                id="mileage-from"
                                name="mileage_from" 
                                placeholder="Min"
                                value="{{ $currentFilters['mileage_from'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
                        <div class="flex-1">
                            <label for="mileage-to" class="sr-only">Mileage To</label>
                            <input 
                                type="number" 
                                id="mileage-to"
                                name="mileage_to" 
                                placeholder="Max"
                                value="{{ $currentFilters['mileage_to'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>

                <!-- Price Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Price Type</label>
                    <div class="space-y-2">
                        @foreach($filterOptions['priceTypes'] as $priceType)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="price_type_id[]" 
                                    value="{{ $priceType->id }}"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['price_type_id']) && (is_array($currentFilters['price_type_id']) ? in_array($priceType->id, $currentFilters['price_type_id']) : $currentFilters['price_type_id'] == $priceType->id)) checked @endif
                                >
                                <span class="text-sm">{{ $priceType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Condition -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Condition</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="radio" 
                                name="condition_id" 
                                value=""
                                class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                checked
                            >
                            <span class="text-sm">All</span>
                        </label>
                        @foreach($filterOptions['conditions'] as $condition)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="radio" 
                                    name="condition_id" 
                                    value="{{ $condition->id }}"
                                    class="h-4 w-4 border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition->id) checked @endif
                                >
                                <span class="text-sm">{{ $condition->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Vehicle Body Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Vehicle Body Type</label>
                    <div class="space-y-2">
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
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        name="body_type_id[]" 
                                        value="{{ $bodyType->id }}"
                                        class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                        @if(isset($currentFilters['body_type_id']) && (is_array($currentFilters['body_type_id']) ? in_array($bodyType->id, $currentFilters['body_type_id']) : $currentFilters['body_type_id'] == $bodyType->id)) checked @endif
                                    >
                                    <span class="text-sm">{{ $bodyType->name }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Fuel Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Fuel Type</label>
                    <div class="space-y-2">
                        @php
                            $fuelTypeMap = [
                                'electric' => ['electric', 'ev'],
                                'petrol' => ['petrol', 'gasoline'],
                                'diesel' => ['diesel'],
                                'hybrid_petrol' => ['hybrid', 'hev'],
                                'hybrid_diesel' => ['hybrid diesel'],
                                'plugin_petrol' => ['plugin', 'phev', 'plug-in'],
                                'plugin_diesel' => ['plugin diesel']
                            ];
                        @endphp
                        @foreach($filterOptions['fuelTypes'] as $fuelType)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="fuel_type_id[]" 
                                    value="{{ $fuelType->id }}"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['fuel_type_id']) && (is_array($currentFilters['fuel_type_id']) ? in_array($fuelType->id, $currentFilters['fuel_type_id']) : $currentFilters['fuel_type_id'] == $fuelType->id)) checked @endif
                                >
                                <span class="text-sm">{{ $fuelType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Gear Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Gear Type</label>
                    <div class="space-y-2">
                        @foreach($filterOptions['gearTypes'] as $gearType)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="gear_type_id[]" 
                                    value="{{ $gearType->id }}"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['gear_type_id']) && (is_array($currentFilters['gear_type_id']) ? in_array($gearType->id, $currentFilters['gear_type_id']) : $currentFilters['gear_type_id'] == $gearType->id)) checked @endif
                                >
                                <span class="text-sm">{{ $gearType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Drive Wheels -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Drive Wheels</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="drive_axles[]" 
                                value="fwd"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('fwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'fwd')) checked @endif
                            >
                            <span class="text-sm">FWD (Front Wheel Drive)</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="drive_axles[]" 
                                value="rwd"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('rwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'rwd')) checked @endif
                            >
                            <span class="text-sm">RWD (Rear Wheel Drive)</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="drive_axles[]" 
                                value="awd"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('awd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'awd')) checked @endif
                            >
                            <span class="text-sm">AWD (All Wheel Drive)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Registration & Seller -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Registration & Seller</h3>
                
                <!-- First Registration Year Range -->
                <div>
                    <label class="text-sm font-medium mb-2 block">First Registration Year</label>
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
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
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
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>

                <!-- Seller Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Seller Type</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="seller_type[]" 
                                value="dealer"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('dealer', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'dealer')) checked @endif
                            >
                            <span class="text-sm">Dealer</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="seller_type[]" 
                                value="private"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('private', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'private')) checked @endif
                            >
                            <span class="text-sm">Private</span>
                        </label>
                    </div>
                </div>

                <!-- Sales Type -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Sales Type</label>
                    <div class="space-y-2">
                        @foreach($filterOptions['salesTypes'] as $salesType)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="sales_type_id[]" 
                                    value="{{ $salesType->id }}"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                    @if(isset($currentFilters['sales_type_id']) && (is_array($currentFilters['sales_type_id']) ? in_array($salesType->id, $currentFilters['sales_type_id']) : $currentFilters['sales_type_id'] == $salesType->id)) checked @endif
                                >
                                <span class="text-sm">{{ $salesType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Seller Distance -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Seller Distance (km)</label>
                    <input 
                        type="number" 
                        name="seller_distance" 
                        placeholder="Distance in km"
                        min="0"
                        value="{{ $currentFilters['seller_distance'] ?? '' }}"
                        class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                </div>
            </div>

            <!-- Performance -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Performance</h3>
                
                <!-- Horsepower Range -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Horsepower (HP)</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="horsepower-min" class="sr-only">Horsepower Min</label>
                            <input 
                                type="number" 
                                id="horsepower-min"
                                name="engine_power_from" 
                                placeholder="Min"
                                min="0"
                                value="{{ $currentFilters['engine_power_from'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
                        <div class="flex-1">
                            <label for="horsepower-max" class="sr-only">Horsepower Max</label>
                            <input 
                                type="number" 
                                id="horsepower-max"
                                name="engine_power_to" 
                                placeholder="Max"
                                min="0"
                                value="{{ $currentFilters['engine_power_to'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battery & Charging (EV) -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Battery & Charging (EV)</h3>
                
                <!-- Battery Capacity -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Battery Capacity (kWh)</label>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="battery-capacity-min" class="sr-only">Battery Capacity Min</label>
                            <input 
                                type="number" 
                                id="battery-capacity-min"
                                name="battery_capacity_from" 
                                placeholder="Min"
                                min="0"
                                value="{{ $currentFilters['battery_capacity_from'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                        <span class="text-muted-foreground">-</span>
                        <div class="flex-1">
                            <label for="battery-capacity-max" class="sr-only">Battery Capacity Max</label>
                            <input 
                                type="number" 
                                id="battery-capacity-max"
                                name="battery_capacity_to" 
                                placeholder="Max"
                                min="0"
                                value="{{ $currentFilters['battery_capacity_to'] ?? '' }}"
                                class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Economy & Environment -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Economy & Environment</h3>
                
                <!-- Euro Norm -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Euro Norm</label>
                    <select 
                        name="euronorm" 
                        class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Physical Details</h3>
                
                <!-- Doors Min -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Doors (Min)</label>
                    <input 
                        type="number" 
                        name="doors" 
                        placeholder="Minimum doors"
                        min="2"
                        max="6"
                        value="{{ $currentFilters['doors'] ?? '' }}"
                        class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                </div>

                <!-- Seats Min -->
                <div>
                    <label class="text-sm font-medium mb-2 block">Seats (Min)</label>
                    <input 
                        type="number" 
                        name="seats_min" 
                        placeholder="Minimum seats"
                        min="2"
                        max="9"
                        value="{{ $currentFilters['seats_min'] ?? '' }}"
                        class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                </div>
            </div>

            <!-- Equipment -->
            <div class="space-y-4 border-t border-border pt-4">
                <h3 class="text-base font-semibold">Equipment</h3>
                
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($filterOptions['equipment'] as $equipment)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="equipment_ids[]" 
                                value="{{ $equipment->id }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                                @if(isset($currentFilters['equipment_ids']) && (is_array($currentFilters['equipment_ids']) ? in_array($equipment->id, $currentFilters['equipment_ids']) : $currentFilters['equipment_ids'] == $equipment->id)) checked @endif
                            >
                            <span class="text-sm">{{ $equipment->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="border-t border-border px-6 py-4 flex items-center justify-between gap-4">
            <button
                id="filter-reset-button"
                type="button"
                class="text-sm font-medium text-primary underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-md px-2 py-1"
            >
                Reset
            </button>
            <div class="flex gap-3">
                <button
                    id="filter-apply-button"
                    type="button"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        const filterDrawer = document.getElementById('filter-drawer');
        const filterPanel = document.getElementById('filter-panel');
        const filterBackdrop = document.getElementById('filter-backdrop');
        const filterButton = document.getElementById('filter-button');
        const filterCloseButton = document.getElementById('filter-close-button');
        const filterResetButton = document.getElementById('filter-reset-button');
        const filterApplyButton = document.getElementById('filter-apply-button');
        const vehicleGrid = document.querySelector('.grid.w-full');

        function openDrawer() {
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
            filterPanel.classList.add('translate-x-full');
            filterBackdrop.style.opacity = '0';
            // Wait for animation to complete before hiding
            setTimeout(() => {
                filterDrawer.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        // Popular brands toggle functionality
        const brandFilterTypeRadios = document.querySelectorAll('[name="brand_filter_type"]');
        const brandSelect = document.getElementById('brand-select');
        
        function updateBrandOptions() {
            const selectedType = document.querySelector('[name="brand_filter_type"]:checked')?.value || 'popular';
            const allOptions = brandSelect.querySelectorAll('.brand-option');
            
            allOptions.forEach(option => {
                if (selectedType === 'popular') {
                    option.style.display = option.classList.contains('brand-popular') ? '' : 'none';
                } else {
                    option.style.display = '';
                }
            });
            
            // Reset selection if current selection is hidden
            const selectedOption = brandSelect.options[brandSelect.selectedIndex];
            if (selectedOption && selectedOption.style.display === 'none') {
                brandSelect.value = '';
            }
        }
        
        brandFilterTypeRadios.forEach(radio => {
            radio.addEventListener('change', updateBrandOptions);
        });

        // Open drawer
        filterButton.addEventListener('click', openDrawer);

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
        filterResetButton.addEventListener('click', () => {
            const inputs = filterDrawer.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            // Reset brand filter to popular
            document.getElementById('brand-popular').checked = true;
            updateBrandOptions();
        });

        // Collect all filter values
        function collectFilters() {
            const filters = {};
            
            // Preserve search parameter from URL
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search');
            if (search) filters.search = search;
            
            // Basic filters
            const listingTypeId = document.querySelector('[name="listing_type_id"]:checked')?.value;
            if (listingTypeId) filters.listing_type_id = listingTypeId;
            
            const categoryId = document.querySelector('[name="category_id"]')?.value;
            if (categoryId) filters.category_id = categoryId;
            
            const brandId = document.querySelector('[name="brand_id"]')?.value;
            if (brandId) filters.brand_id = brandId;
            
            // Price range
            const priceFrom = document.querySelector('[name="price_from"]')?.value;
            if (priceFrom) filters.price_from = priceFrom;
            
            const priceTo = document.querySelector('[name="price_to"]')?.value;
            if (priceTo) filters.price_to = priceTo;
            
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
            
            // Arrays (checkboxes)
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

        // Apply filters via AJAX
        filterApplyButton.addEventListener('click', async () => {
            const filters = collectFilters();
            const queryString = buildQueryString(filters);
            const url = '/vehicles' + (queryString ? '?' + queryString : '');
            
            // Show loading state
            filterApplyButton.disabled = true;
            filterApplyButton.textContent = 'Loading...';
            
            try {
                // Make AJAX request
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch vehicles');
                }
                
                const html = await response.text();
                
                // Parse the response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update vehicle grid
                const newGrid = doc.querySelector('.grid.w-full');
                if (newGrid && vehicleGrid) {
                    vehicleGrid.innerHTML = newGrid.innerHTML;
                }
                
                // Update pagination if present
                const pagination = document.querySelector('.mt-8.flex.items-center.justify-center');
                const newPagination = doc.querySelector('.mt-8.flex.items-center.justify-center');
                if (newPagination && pagination) {
                    pagination.innerHTML = newPagination.innerHTML;
                }
                
                // Update URL without page reload
                window.history.pushState({}, '', url);
                
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
    })();
</script>
@endpush
@endsection

