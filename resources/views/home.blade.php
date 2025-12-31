@extends('layouts.app')

@section('title', 'Bilskyen | Home')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
@endpush

@section('content')
<div class="flex min-h-screen flex-col pt-0">
    <!-- Search Section -->
    <section class="relative bg-gradient-to-b from-muted/60 to-muted py-12 md:py-16">
    
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-6">
                <h1 class="text-4xl font-bold tracking-tighter md:text-6xl">
                    Find Your Perfect Vehicle at Bilskyen
                </h1>
            </div>
            <div class="rounded-lg bg-card p-4 md:p-6 shadow-lg">
                <p class="text-muted-foreground text-base md:text-lg mb-4">
                    Search our inventory to find the perfect match for your needs.
                </p>

                <div class="flex flex-col gap-4">
                <!-- First Row: 4 Dropdown Fields (equal width) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Brand Dropdown -->
                    <div class="relative flex-1" data-dropdown="brand">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Brand</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="Search brand..." class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="">All Brands</button>
                                @foreach($filterOptions['brands'] as $brand)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="{{ $brand->id }}" data-text="{{ $brand->name }}">{{ $brand->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="brand_id" value="" class="dropdown-input">
                        </div>

                    <!-- Model Dropdown -->
                    <div class="relative flex-1" data-dropdown="model">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Model</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="Search model..." class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="">All Models</button>
                                @foreach($filterOptions['models'] as $model)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="{{ $model->id }}" data-text="{{ $model->name }}" data-brand-id="{{ $model->brand_id }}">{{ $model->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="model_id" value="" class="dropdown-input">
                        </div>

                    <!-- Model Year Dropdown -->
                    <div class="relative flex-1" data-dropdown="model_year">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Model Year</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="Search year..." class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="">All Years</button>
                                @foreach($filterOptions['modelYears'] as $modelYear)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="{{ $modelYear->id }}" data-text="{{ $modelYear->name }}">{{ $modelYear->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="model_year_id" value="" class="dropdown-input">
                        </div>

                    <!-- Fuel Type Dropdown -->
                    <div class="relative flex-1" data-dropdown="fuel_type">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Fuel Type</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="Search fuel type..." class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="">All Fuel Types</button>
                                @foreach($filterOptions['fuelTypes'] as $fuelType)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="{{ $fuelType->id }}" data-text="{{ $fuelType->name }}">{{ $fuelType->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="fuel_type_id" value="" class="dropdown-input">
                    </div>
                        </div>

                <!-- Second Row: 3 Dropdown Fields + Search Button (equal width) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Category Dropdown -->
                    <div class="relative flex-1" data-dropdown="category">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Category</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="Search category..." class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="">All Categories</button>
                                @foreach($filterOptions['categories'] as $category)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground" data-value="{{ $category->id }}" data-text="{{ $category->name }}">{{ $category->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="category_id" value="" class="dropdown-input">
                        </div>

                    <!-- Price Dropdown (with range slider) -->
                    <div class="relative flex-1" data-dropdown="price">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">Price</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[300px] rounded-md border border-border bg-background shadow-lg p-3 sm:p-4">
                            <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2.5 block">Price Range</label>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex-1">
                                    <input type="number" id="price-from-dropdown" name="price_from" placeholder="Min" min="0" max="1000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                                <span class="text-muted-foreground/60 text-sm">–</span>
                                <div class="flex-1">
                                    <input type="number" id="price-to-dropdown" name="price_to" placeholder="Max" min="0" max="1000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                            </div>
                            <div class="relative px-1">
                                <div class="relative h-0.5 bg-muted rounded-full">
                                    <div id="price-range-track-dropdown" class="absolute h-0.5 bg-primary rounded-full"></div>
                                    <input type="range" id="price-slider-min-dropdown" min="0" max="1000000" step="1000" value="0" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-10">
                                    <input type="range" id="price-slider-max-dropdown" min="0" max="1000000" step="1000" value="1000000" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-20">
                                    <div id="price-handle-min-dropdown" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                    <div id="price-handle-max-dropdown" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                </div>
                            </div>
                        </div>
                        </div>

                    <!-- KM Driven Dropdown (with range slider) -->
                    <div class="relative flex-1" data-dropdown="km_driven">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">KM Driven</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[300px] rounded-md border border-border bg-background shadow-lg p-3 sm:p-4">
                            <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2.5 block">KM Driven Range</label>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex-1">
                                    <input type="number" id="km-driven-from" name="km_driven_from" placeholder="Min" min="0" max="500000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                                <span class="text-muted-foreground/60 text-sm">–</span>
                                <div class="flex-1">
                                    <input type="number" id="km-driven-to" name="km_driven_to" placeholder="Max" min="0" max="500000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                            </div>
                            <div class="relative px-1">
                                <div class="relative h-0.5 bg-muted rounded-full">
                                    <div id="km-driven-range-track" class="absolute h-0.5 bg-primary rounded-full"></div>
                                    <input type="range" id="km-driven-slider-min" min="0" max="500000" step="1000" value="0" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-10">
                                    <input type="range" id="km-driven-slider-max" min="0" max="500000" step="1000" value="500000" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-20">
                                    <div id="km-driven-handle-min" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                    <div id="km-driven-handle-max" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search Button -->
                    <form method="GET" action="/vehicles" id="filter-form" class="flex-1">
                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                Search
                        </button>
                    </form>
                </div>

                <!-- Third Row: Reset Filters Link -->
                <div class="flex flex-col sm:flex-row justify-start sm:justify-end gap-3 sm:gap-4">
                    <button type="button" id="reset-filters-button" class="text-sm text-muted-foreground hover:text-foreground transition-colors underline bg-transparent border-0 p-0 cursor-pointer">
                        Reset Filters
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </section>

    <!-- Hero Section -->
    <section class="relative bg-card py-16 md:py-20">
        <div class="container mx-auto px-4 md:px-6">
            <div class="max-w-4xl space-y-8">
                <p class="text-xl text-muted-foreground">
                    Revolutionizing the car buying experience with transparent pricing, quality vehicles, and exceptional customer service.
                </p>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="/vehicles" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        Browse Vehicles
                    </a>
                    <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-md border border-input bg-background px-8 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Vehicles Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col gap-8">
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Featured Vehicles
                    </h2>
                    <p class="text-muted-foreground">
                        Explore our selection of quality vehicles ready for you to drive home today.
                    </p>
                </div>
                
                <!-- Featured Vehicles Carousel -->
                <div class="relative">
                    @php
                        $featuredVehicles = [
                            [
                                'id' => 1,
                                'make' => 'Toyota',
                                'model' => 'Camry',
                                'variant' => 'XLE',
                                'year' => 2022,
                                'listingPrice' => 2500000,
                                'transmissionType' => 'Automatic',
                                'color' => 'Silver',
                                'vehicleType' => 'Sedan',
                                'odometer' => 15000,
                                'fuelType' => 'Petrol',
                                'ownershipCount' => 1,
                                'status' => 'Available',
                                'condition' => 'Excellent',
                                'registrationNumber' => 'KL-01-AB-1234',
                                'image' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop'
                            ],
                            [
                                'id' => 2,
                                'make' => 'Honda',
                                'model' => 'CR-V',
                                'variant' => 'VX',
                                'year' => 2021,
                                'listingPrice' => 3200000,
                                'transmissionType' => 'Automatic',
                                'color' => 'White',
                                'vehicleType' => 'SUV',
                                'odometer' => 25000,
                                'fuelType' => 'Petrol',
                                'ownershipCount' => 1,
                                'status' => 'Available',
                                'condition' => 'Excellent',
                                'registrationNumber' => 'KL-02-CD-5678',
                                'image' => 'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop'
                            ],
                            [
                                'id' => 3,
                                'make' => 'BMW',
                                'model' => '3 Series',
                                'variant' => '330i',
                                'year' => 2023,
                                'listingPrice' => 5500000,
                                'transmissionType' => 'Automatic',
                                'color' => 'Black',
                                'vehicleType' => 'Sedan',
                                'odometer' => 8000,
                                'fuelType' => 'Petrol',
                                'ownershipCount' => 1,
                                'status' => 'Available',
                                'condition' => 'Excellent',
                                'registrationNumber' => 'KL-03-EF-9012',
                                'image' => 'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=800&h=600&fit=crop'
                            ],
                        ];
                    @endphp
                    <div class="relative">
                        <div class="embla overflow-hidden" id="featured-vehicles-embla">
                            <div class="embla__container -ml-4 flex">
                                @foreach($featuredVehicles as $vehicle)
                                <div class="embla__slide pl-4 basis-full sm:basis-1/2 lg:basis-1/3 flex-shrink-0 min-w-0">
                                    <div class="gap-y-2 overflow-hidden rounded-lg border border-border bg-card p-0 h-full">
                            <div class="p-0">
                                <div class="relative aspect-video overflow-hidden">
                                    <img src="{{ $vehicle['image'] }}" alt="{{ $vehicle['make'] }} {{ $vehicle['model'] }}" class="h-full w-full object-cover transition-transform hover:scale-105">
                                    <span class="absolute top-2 right-2 z-10 rounded-md bg-secondary px-2 py-0.5 text-xs">
                                        {{ $vehicle['registrationNumber'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="px-4 py-0">
                                <div class="space-y-4">
                                    <div class="flex flex-col gap-1">
                                        <h3 class="flex items-center gap-2 text-xl font-bold">
                                            {{ $vehicle['make'] }} {{ $vehicle['model'] }}
                                        </h3>
                                        <p class="-mt-1.5 text-xs font-normal text-muted-foreground">
                                            {{ $vehicle['variant'] }}
                                        </p>
                                        <p class="text-2xl font-medium text-primary">
                                            {{ number_format($vehicle['listingPrice'], 0, ',', '.') }} kr.
                                        </p>
                                    </div>

                                    <div class="-mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle['transmissionType'] }}</span>
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle['color'] }}</span>
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle['vehicleType'] }}</span>
                                    </div>

                                    <div class="text-muted-foreground grid grid-cols-2 gap-2 text-sm">
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                                <line x1="16" x2="16" y1="2" y2="6"></line>
                                                <line x1="8" x2="8" y1="2" y2="6"></line>
                                                <line x1="3" x2="21" y1="10" y2="10"></line>
                                            </svg>
                                            <span>{{ $vehicle['year'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M18 6H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h13"></path>
                                                <path d="M6 12h13a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H6"></path>
                                                <path d="M12 6v12"></path>
                                            </svg>
                                            <span>{{ number_format($vehicle['odometer']) }} km</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M3 3h18v18H3zM12 7v10M7 12h10"></path>
                                            </svg>
                                            <span>{{ $vehicle['fuelType'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                            <span>{{ $vehicle['ownershipCount'] }} Owner{{ $vehicle['ownershipCount'] > 1 ? 's' : '' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                            <span>{{ $vehicle['status'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M21.16 6.26l-9 5a2 2 0 0 1-1.84 0l-9-5A2 2 0 0 0 1 8v8a2 2 0 0 0 1.16 1.74l9 5a2 2 0 0 0 1.84 0l9-5A2 2 0 0 0 22 16V8a2 2 0 0 0-1.16-1.74z"></path>
                                                <path d="M12 2v20"></path>
                                            </svg>
                                            <span>{{ $vehicle['condition'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-auto p-4 pt-2">
                                <div class="flex w-full flex-col gap-2 sm:flex-row">
                                    <a href="/vehicles/{{ $vehicle['id'] }}" class="flex-1">
                                        <button class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                            View Details
                                        </button>
                                    </a>
                                    <button class="flex-1 inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                        Enquire
                                    </button>
                                </div>
                            </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-6 flex justify-center gap-2">
                            <button class="embla__prev inline-flex h-8 w-8 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="Previous slide">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="m12 19-7-7 7-7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </button>
                            <button class="embla__next inline-flex h-8 w-8 items-center justify-center rounded-full border border-input bg-background shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="Next slide">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="m12 5 7 7-7 7"></path>
                                    <path d="M19 12H5"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <a href="/vehicles" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            View All Vehicles
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-muted py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    Why Choose Bilskyen
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    We're committed to providing exceptional service and quality vehicles to our customers.
                </p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.7L18.7 4c-.3-.8-1-1.3-1.7-1.3H8.7c-.7 0-1.4.5-1.7 1.3L5.5 11.3C4.7 11.3 4 12.1 4 13v3c0 .6.4 1 1 1h2"></path>
                                <circle cx="7" cy="18" r="2"></circle>
                                <circle cx="17" cy="18" r="2"></circle>
                                <path d="M12 8v6M9 11h6"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">100+</h3>
                        <p class="mb-2 font-medium">Quality Vehicles</p>
                        <p class="text-sm text-muted-foreground">Thoroughly inspected vehicles in our inventory</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">500+</h3>
                        <p class="mb-2 font-medium">Happy Customers</p>
                        <p class="text-sm text-muted-foreground">Satisfied customers who found their perfect vehicle</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                <line x1="16" x2="16" y1="2" y2="6"></line>
                                <line x1="8" x2="8" y1="2" y2="6"></line>
                                <line x1="3" x2="21" y1="10" y2="10"></line>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">15+</h3>
                        <p class="mb-2 font-medium">Years of Experience</p>
                        <p class="text-sm text-muted-foreground">Years serving our community with integrity</p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card">
                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-4 rounded-full bg-primary/10 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M7 10v12M17 10v12M3 10h2a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1a2 2 0 0 1 2-2h2"></path>
                                <path d="M12 2v6"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold">98%</h3>
                        <p class="mb-2 font-medium">Satisfaction Rate</p>
                        <p class="text-sm text-muted-foreground">Customer satisfaction based on reviews</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    Our Services
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    We provide comprehensive services to make your vehicle purchase smooth and enjoyable.
                </p>
            </div>
            <div class="grid gap-8 md:grid-cols-3">
                <div class="bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center">
                    <div class="mb-4 rounded-full bg-primary/10 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary">
                            <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                            <line x1="2" x2="22" y1="10" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Financing Options</h3>
                    <p class="text-muted-foreground">
                        We work with multiple lenders to find the best financing solutions for your budget.
                    </p>
                </div>
                <div class="bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center">
                    <div class="mb-4 rounded-full bg-primary/10 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Vehicle Warranty</h3>
                    <p class="text-muted-foreground">
                        Extended warranty options to protect your investment and give you peace of mind.
                    </p>
                </div>
                <div class="bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center">
                    <div class="mb-4 rounded-full bg-primary/10 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" x2="16" y1="2" y2="6"></line>
                            <line x1="8" x2="8" y1="2" y2="6"></line>
                            <line x1="3" x2="21" y1="10" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">Service Department</h3>
                    <p class="text-muted-foreground">
                        Professional maintenance and repair services to keep your vehicle in top condition.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="bg-muted py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    Customer Testimonials
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    Hear what our customers have to say about their experience with us.
                </p>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $testimonials = [
                        [
                            'name' => 'John Davis',
                            'location' => 'Copenhagen, Denmark',
                            'quote' => 'The team at Bilskyen made buying a car so easy. They were transparent about pricing and helped me find the perfect vehicle for my family.',
                            'rating' => 5
                        ],
                        [
                            'name' => 'Priya Sharma',
                            'location' => 'Aarhus, Denmark',
                            'quote' => 'I was impressed with their knowledge and no-pressure approach. I got a great deal on my new car and would definitely recommend them.',
                            'rating' => 5
                        ],
                        [
                            'name' => 'Ahmed Khan',
                            'location' => 'Odense, Denmark',
                            'quote' => 'The financing options they provided were better than I expected. The entire process was smooth and I drove away very happy.',
                            'rating' => 4
                        ],
                    ];
                @endphp
                @foreach($testimonials as $testimonial)
                <div class="overflow-hidden rounded-lg border border-border bg-card">
                    <div class="p-6">
                        <div class="flex flex-col gap-4">
                            <div class="flex gap-1">
                                @for($i = 0; $i < 5; $i++)
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="{{ $i < $testimonial['rating'] ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 {{ $i < $testimonial['rating'] ? 'fill-yellow-500 text-yellow-500' : 'text-muted-foreground' }}">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                @endfor
                            </div>

                            <blockquote class="text-muted-foreground">
                                &ldquo;{{ $testimonial['quote'] }}&rdquo;
                            </blockquote>

                            <div class="mt-2 flex items-center gap-3">
                                <div class="bg-muted h-10 w-10 overflow-hidden rounded-full flex items-center justify-center">
                                    <span class="text-muted-foreground">{{ substr($testimonial['name'], 0, 1) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium">{{ $testimonial['name'] }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $testimonial['location'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary py-16 text-primary-foreground">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col items-center justify-between gap-8 lg:flex-row">
                <div class="max-w-xl space-y-4">
                    <h2 class="text-3xl font-bold tracking-tight">
                        Ready to Find Your Next Vehicle?
                    </h2>
                    <p class="text-primary-foreground/90">
                        Visit our showroom or browse our inventory online. Our team is ready to help you find the perfect vehicle that fits your needs and budget.
                    </p>
                </div>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="/vehicles" class="inline-flex h-11 items-center justify-center rounded-md bg-secondary px-8 text-sm font-medium text-secondary-foreground shadow transition-colors hover:bg-secondary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        Browse Inventory
                    </a>
                    <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-md border border-primary-foreground bg-transparent px-8 text-sm font-medium shadow-sm transition-colors hover:bg-primary-foreground hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="module">
    import EmblaCarousel from 'https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/+esm';
    
    document.addEventListener('DOMContentLoaded', function() {
        const emblaNode = document.getElementById('featured-vehicles-embla');
        const prevButton = document.querySelector('.embla__prev');
        const nextButton = document.querySelector('.embla__next');

        if (!emblaNode) return;

        const embla = EmblaCarousel(emblaNode, {
            align: 'start',
            loop: true,
        });

        const updateButtonStates = () => {
            if (prevButton) {
                const canScrollPrev = embla.canScrollPrev();
                prevButton.disabled = !canScrollPrev;
                if (!canScrollPrev) {
                    prevButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            if (nextButton) {
                const canScrollNext = embla.canScrollNext();
                nextButton.disabled = !canScrollNext;
                if (!canScrollNext) {
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        };

        if (prevButton) {
            prevButton.addEventListener('click', () => {
                embla.scrollPrev();
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => {
                embla.scrollNext();
            });
        }

        embla.on('select', updateButtonStates);
        embla.on('reInit', updateButtonStates);
        updateButtonStates();
    });

    // Searchable Dropdowns Functionality
    function initSearchableDropdowns() {
        const dropdowns = document.querySelectorAll('[data-dropdown]');
        
        dropdowns.forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            const searchInput = dropdown.querySelector('.dropdown-search');
            const options = dropdown.querySelectorAll('.dropdown-option');
            const hiddenInput = dropdown.querySelector('.dropdown-input');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            const dropdownType = dropdown.getAttribute('data-dropdown');
            
            // Toggle menu
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                // Close all other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.querySelector('.dropdown-menu').classList.add('hidden');
                    }
                });
                menu.classList.toggle('hidden');
                if (!menu.classList.contains('hidden') && searchInput) {
                    setTimeout(() => searchInput.focus(), 50);
                }
            });
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            option.style.display = '';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });
            }
            
            // Option selection (only for regular dropdowns, not range sliders)
            if (dropdownType !== 'price' && dropdownType !== 'km_driven') {
                options.forEach(option => {
                    option.addEventListener('click', () => {
                        const value = option.getAttribute('data-value');
                        const text = option.getAttribute('data-text') || option.textContent.trim();
                        
                        if (hiddenInput) {
                            hiddenInput.value = value;
                        }
                        
                        if (value === '') {
                            // Reset to default text
                            const defaultTexts = {
                                'category': 'Category',
                                'brand': 'Brand',
                                'model': 'Model',
                                'model_year': 'Model Year',
                                'fuel_type': 'Fuel Type'
                            };
                            selectedText.textContent = defaultTexts[dropdownType] || 'Select';
                        } else {
                            selectedText.textContent = text;
                        }
                        
                        // Filter models based on selected brand
                        if (dropdownType === 'brand') {
                            filterModelsByBrand(value);
                        }
                        
                        menu.classList.add('hidden');
                    });
                });
            }
            
            // Update selected text for price and km_driven when range changes
            if (dropdownType === 'price') {
                const priceFromInput = document.getElementById('price-from-dropdown');
                const priceToInput = document.getElementById('price-to-dropdown');
                
                const updatePriceText = () => {
                    const from = priceFromInput?.value;
                    const to = priceToInput?.value;
                    if (from || to) {
                        const fromText = from ? new Intl.NumberFormat().format(from) : '0';
                        const toText = to ? new Intl.NumberFormat().format(to) : '∞';
                        selectedText.textContent = `${fromText} - ${toText}`;
                    } else {
                        selectedText.textContent = 'Price';
                    }
                };
                
                if (priceFromInput) priceFromInput.addEventListener('input', updatePriceText);
                if (priceToInput) priceToInput.addEventListener('input', updatePriceText);
                updatePriceText();
            }
            
            if (dropdownType === 'km_driven') {
                const kmFromInput = document.getElementById('km-driven-from');
                const kmToInput = document.getElementById('km-driven-to');
                
                const updateKmText = () => {
                    const from = kmFromInput?.value;
                    const to = kmToInput?.value;
                    if (from || to) {
                        const fromText = from ? new Intl.NumberFormat().format(from) : '0';
                        const toText = to ? new Intl.NumberFormat().format(to) : '∞';
                        selectedText.textContent = `${fromText} - ${toText} km`;
                    } else {
                        selectedText.textContent = 'KM Driven';
                    }
                };
                
                if (kmFromInput) kmFromInput.addEventListener('input', updateKmText);
                if (kmToInput) kmToInput.addEventListener('input', updateKmText);
                updateKmText();
            }
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    }
    
    // Filter models by brand
    function filterModelsByBrand(brandId) {
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (!modelDropdown) return;
        
        const modelOptions = modelDropdown.querySelectorAll('.dropdown-option[data-brand-id]');
        const defaultOption = modelDropdown.querySelector('.dropdown-option[data-value=""]');
        
        if (brandId === '') {
            // Show all models
            modelOptions.forEach(option => {
                option.style.display = '';
            });
        } else {
            // Show only models for selected brand
            modelOptions.forEach(option => {
                const optionBrandId = option.getAttribute('data-brand-id');
                if (optionBrandId === brandId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // Reset model selection if current selection doesn't match brand
            const modelInput = modelDropdown.querySelector('.dropdown-input');
            const selectedModelOption = Array.from(modelOptions).find(opt => 
                opt.getAttribute('data-value') === modelInput?.value
            );
            if (selectedModelOption && selectedModelOption.style.display === 'none') {
                if (modelInput) modelInput.value = '';
                const selectedText = modelDropdown.querySelector('.dropdown-selected');
                if (selectedText) selectedText.textContent = 'Model';
            }
        }
    }
    
    // Range Slider for Price and KM Driven dropdowns
    function initDropdownRangeSliders() {
        // Price range slider
        const priceConfig = {
            minSlider: document.getElementById('price-slider-min-dropdown'),
            maxSlider: document.getElementById('price-slider-max-dropdown'),
            minInput: document.getElementById('price-from-dropdown'),
            maxInput: document.getElementById('price-to-dropdown'),
            minHandle: document.getElementById('price-handle-min-dropdown'),
            maxHandle: document.getElementById('price-handle-max-dropdown'),
            track: document.getElementById('price-range-track-dropdown'),
            selectedText: document.querySelector('[data-dropdown="price"] .dropdown-selected'),
            min: 0,
            max: 1000000
        };
        
        // KM Driven range slider
        const kmDrivenConfig = {
            minSlider: document.getElementById('km-driven-slider-min'),
            maxSlider: document.getElementById('km-driven-slider-max'),
            minInput: document.getElementById('km-driven-from'),
            maxInput: document.getElementById('km-driven-to'),
            minHandle: document.getElementById('km-driven-handle-min'),
            maxHandle: document.getElementById('km-driven-handle-max'),
            track: document.getElementById('km-driven-range-track'),
            selectedText: document.querySelector('[data-dropdown="km_driven"] .dropdown-selected'),
            min: 0,
            max: 500000
        };
        
        [priceConfig, kmDrivenConfig].forEach(config => {
            if (!config.minSlider || !config.maxSlider) return;
            
            function updateSlider() {
                const minVal = parseFloat(config.minSlider.value) || config.min;
                const maxVal = parseFloat(config.maxSlider.value) || config.max;
                
                if (minVal > maxVal) {
                    config.minSlider.value = maxVal;
                    config.maxSlider.value = minVal;
                }
                
                const finalMin = Math.min(minVal, maxVal);
                const finalMax = Math.max(minVal, maxVal);
                
                config.minInput.value = finalMin === config.min ? '' : finalMin;
                config.maxInput.value = finalMax === config.max ? '' : finalMax;
                
                const minPercent = ((finalMin - config.min) / (config.max - config.min)) * 100;
                const maxPercent = ((finalMax - config.min) / (config.max - config.min)) * 100;
                
                config.minHandle.style.left = `calc(${minPercent}% - 12px)`;
                config.maxHandle.style.left = `calc(${maxPercent}% - 12px)`;
                
                config.track.style.left = `${minPercent}%`;
                config.track.style.width = `${maxPercent - minPercent}%`;
                
                // Update selected text
                if (config.selectedText) {
                    const from = config.minInput.value;
                    const to = config.maxInput.value;
                    if (from || to) {
                        const fromText = from ? new Intl.NumberFormat().format(from) : '0';
                        const toText = to ? new Intl.NumberFormat().format(to) : '∞';
                        if (config === priceConfig) {
                            config.selectedText.textContent = `${fromText} - ${toText}`;
                        } else {
                            config.selectedText.textContent = `${fromText} - ${toText} km`;
                        }
                    } else {
                        config.selectedText.textContent = config === priceConfig ? 'Price' : 'KM Driven';
                    }
                }
            }
            
            function updateFromInput(input, slider) {
                const value = parseFloat(input.value);
                if (!isNaN(value)) {
                    const clampedValue = Math.max(config.min, Math.min(config.max, value));
                    slider.value = clampedValue;
                    input.value = clampedValue === config.min ? '' : clampedValue;
                    updateSlider();
                }
            }
            
            updateSlider();
            
            config.minSlider.addEventListener('input', updateSlider);
            config.maxSlider.addEventListener('input', updateSlider);
            config.minInput.addEventListener('input', () => updateFromInput(config.minInput, config.minSlider));
            config.maxInput.addEventListener('input', () => updateFromInput(config.maxInput, config.maxSlider));
            
            // Handle drag events for visual handles
            let isDragging = false;
            let activeHandle = null;
            
            [config.minHandle, config.maxHandle].forEach((handle, index) => {
                handle.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    activeHandle = index === 0 ? config.minSlider : config.maxSlider;
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging || !activeHandle) return;
                
                const sliderContainer = activeHandle.closest('.relative');
                if (!sliderContainer) return;
                
                const rect = sliderContainer.getBoundingClientRect();
                const percent = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
                const value = config.min + (percent / 100) * (config.max - config.min);
                const step = parseFloat(activeHandle.step) || 1;
                const steppedValue = Math.round(value / step) * step;
                const clampedValue = Math.max(config.min, Math.min(config.max, steppedValue));
                
                activeHandle.value = clampedValue;
                updateSlider();
            });
            
            document.addEventListener('mouseup', () => {
                isDragging = false;
                activeHandle = null;
            });
        });
    }
    
    // Form submission handler
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Collect all filter values
            const formData = new FormData();
            
            // Get all hidden inputs from dropdowns
            document.querySelectorAll('.dropdown-input').forEach(input => {
                if (input.value) {
                    formData.append(input.name, input.value);
                }
            });
            
            // Get price and km_driven values
            const priceFrom = document.getElementById('price-from-dropdown')?.value;
            const priceTo = document.getElementById('price-to-dropdown')?.value;
            const kmFrom = document.getElementById('km-driven-from')?.value;
            const kmTo = document.getElementById('km-driven-to')?.value;
            
            if (priceFrom) formData.append('price_from', priceFrom);
            if (priceTo) formData.append('price_to', priceTo);
            if (kmFrom) formData.append('km_driven_from', kmFrom);
            if (kmTo) formData.append('km_driven_to', kmTo);
            
            // Build query string
            const params = new URLSearchParams();
            for (const [key, value] of formData.entries()) {
                params.append(key, value);
            }
            
            // Navigate to vehicles page with filters
            window.location.href = '/vehicles' + (params.toString() ? '?' + params.toString() : '');
        });
    }
    
    // Reset filters function
    function resetAllFilters() {
        // Reset all dropdown hidden inputs
        document.querySelectorAll('.dropdown-input').forEach(input => {
            input.value = '';
        });
        
        // Reset all dropdown selected text to default values
        const defaultTexts = {
            'category': 'Category',
            'brand': 'Brand',
            'model': 'Model',
            'model_year': 'Model Year',
            'fuel_type': 'Fuel Type'
        };
        
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            const dropdownType = dropdown.getAttribute('data-dropdown');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            if (selectedText && defaultTexts[dropdownType]) {
                selectedText.textContent = defaultTexts[dropdownType];
            }
        });
        
        // Reset price range
        const priceFromInput = document.getElementById('price-from-dropdown');
        const priceToInput = document.getElementById('price-to-dropdown');
        const priceMinSlider = document.getElementById('price-slider-min-dropdown');
        const priceMaxSlider = document.getElementById('price-slider-max-dropdown');
        const priceSelectedText = document.querySelector('[data-dropdown="price"] .dropdown-selected');
        
        if (priceFromInput) priceFromInput.value = '';
        if (priceToInput) priceToInput.value = '';
        if (priceMinSlider) priceMinSlider.value = '0';
        if (priceMaxSlider) priceMaxSlider.value = '1000000';
        if (priceSelectedText) priceSelectedText.textContent = 'Price';
        
        // Reset KM driven range
        const kmFromInput = document.getElementById('km-driven-from');
        const kmToInput = document.getElementById('km-driven-to');
        const kmMinSlider = document.getElementById('km-driven-slider-min');
        const kmMaxSlider = document.getElementById('km-driven-slider-max');
        const kmSelectedText = document.querySelector('[data-dropdown="km_driven"] .dropdown-selected');
        
        if (kmFromInput) kmFromInput.value = '';
        if (kmToInput) kmToInput.value = '';
        if (kmMinSlider) kmMinSlider.value = '0';
        if (kmMaxSlider) kmMaxSlider.value = '500000';
        if (kmSelectedText) kmSelectedText.textContent = 'KM Driven';
        
        // Update range slider visuals
        if (priceMinSlider && priceMaxSlider) {
            const priceConfig = {
                minSlider: priceMinSlider,
                maxSlider: priceMaxSlider,
                minInput: priceFromInput,
                maxInput: priceToInput,
                minHandle: document.getElementById('price-handle-min-dropdown'),
                maxHandle: document.getElementById('price-handle-max-dropdown'),
                track: document.getElementById('price-range-track-dropdown'),
                min: 0,
                max: 1000000
            };
            
            const updatePriceSlider = () => {
                const minPercent = 0;
                const maxPercent = 100;
                if (priceConfig.minHandle) priceConfig.minHandle.style.left = `calc(${minPercent}% - 12px)`;
                if (priceConfig.maxHandle) priceConfig.maxHandle.style.left = `calc(${maxPercent}% - 12px)`;
                if (priceConfig.track) {
                    priceConfig.track.style.left = `${minPercent}%`;
                    priceConfig.track.style.width = `${maxPercent - minPercent}%`;
                }
            };
            updatePriceSlider();
        }
        
        if (kmMinSlider && kmMaxSlider) {
            const kmConfig = {
                minSlider: kmMinSlider,
                maxSlider: kmMaxSlider,
                minHandle: document.getElementById('km-driven-handle-min'),
                maxHandle: document.getElementById('km-driven-handle-max'),
                track: document.getElementById('km-driven-range-track'),
                min: 0,
                max: 500000
            };
            
            const updateKmSlider = () => {
                const minPercent = 0;
                const maxPercent = 100;
                if (kmConfig.minHandle) kmConfig.minHandle.style.left = `calc(${minPercent}% - 12px)`;
                if (kmConfig.maxHandle) kmConfig.maxHandle.style.left = `calc(${maxPercent}% - 12px)`;
                if (kmConfig.track) {
                    kmConfig.track.style.left = `${minPercent}%`;
                    kmConfig.track.style.width = `${maxPercent - minPercent}%`;
                }
            };
            updateKmSlider();
        }
        
        // Reset model dropdown options visibility (show all models)
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (modelDropdown) {
            const modelOptions = modelDropdown.querySelectorAll('.dropdown-option[data-brand-id]');
            modelOptions.forEach(option => {
                option.style.display = '';
            });
        }
    }
    
    // Reset filters button handler
    const resetFiltersButton = document.getElementById('reset-filters-button');
    if (resetFiltersButton) {
        resetFiltersButton.addEventListener('click', resetAllFilters);
    }
    
    // Initialize everything
    initSearchableDropdowns();
    initDropdownRangeSliders();
</script>
@endsection

