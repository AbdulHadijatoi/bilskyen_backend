@extends('layouts.app')

@section('title', __('messages.navigation.home') . ' | Bilskyen')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
<style>
    .featured-vehicles-scroll-container {
        scrollbar-width: thin;
        scrollbar-color: hsl(var(--muted-foreground) / 0.3) transparent;
        scroll-behavior: smooth;
        scroll-snap-type: x mandatory;
        scroll-padding-left: 1rem; /* Account for container left padding in snap */
        scroll-padding-right: 1rem; /* Account for container right padding in snap */
        -webkit-overflow-scrolling: touch;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar {
        height: 8px;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-thumb {
        background-color: hsl(var(--muted-foreground) / 0.3);
        border-radius: 4px;
    }
    
    .featured-vehicles-scroll-container::-webkit-scrollbar-thumb:hover {
        background-color: hsl(var(--muted-foreground) / 0.5);
    }
    
    /* Hide scrollbar on large screens */
    @media (min-width: 768px) {
        .featured-vehicles-scroll-container {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .featured-vehicles-scroll-container::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
    }
    
    /* Multi-select dropdown: show checkmark when option is selected */
    .dropdown-option.selected .dropdown-option-check {
        opacity: 1;
    }
    
    /* Navigation buttons */
    .featured-vehicles-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: hsl(var(--background));
        border: 1px solid hsl(var(--border));
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: hsl(var(--foreground));
    }
    
    .featured-vehicles-nav-btn:hover {
        background-color: hsl(var(--accent));
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .featured-vehicles-nav-btn:active {
        transform: translateY(-50%) scale(0.95);
    }
    
    .featured-vehicles-nav-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .featured-vehicles-nav-btn-left {
        left: -64px;
    }
    
    .featured-vehicles-nav-btn-right {
        right: -64px;
    }
    
    @media (min-width: 1024px) {
        .featured-vehicles-nav-btn-left {
            left: -80px;
        }
        
        .featured-vehicles-nav-btn-right {
            right: -80px;
        }
    }
    
    @media (min-width: 1280px) {
        .featured-vehicles-nav-btn-left {
            left: -96px;
        }
        
        .featured-vehicles-nav-btn-right {
            right: -96px;
        }
    }
    
    .featured-vehicles-scroll-container > div {
        display: flex;
    }
    
    .featured-vehicle-card {
        scroll-snap-align: start;
        scroll-snap-stop: always;
        /* Mobile: 1 item per row, smaller size but still one at a time */
        /* Use 85% of viewport width minus padding for a more compact look */
        flex: 0 0 calc(85vw - 2rem);
        min-width: calc(85vw - 2rem);
        max-width: calc(85vw - 2rem);
    }
    
    @media (min-width: 640px) {
        .featured-vehicle-card {
            /* Tablet: 2 items per row, account for container padding (1rem each) and gap (1.5rem total, 0.75rem per item) */
            flex: 0 0 calc(50vw - 1rem - 0.75rem);
            min-width: calc(50vw - 1rem - 0.75rem);
            max-width: calc(50vw - 1rem - 0.75rem);
        }
    }
    
    @media (min-width: 768px) {
        .featured-vehicles-scroll-container > div {
            /* Set flex container width to match scroll container's inner width */
            /* Scroll container extends with -mx-4 px-4, so inner width = viewport width */
            /* But we need to account for scroll container padding (px-4 = 1rem each side) */
            width: calc(100vw - 2rem);
        }
        
        .featured-vehicle-card {
            /* Desktop: 3 items per row - exactly fit 3 items */
            /* Flex container width = 100vw - 2rem (scroll padding) */
            /* Flex container has gap-6 (1.5rem) between items, so 2 gaps = 3rem total */
            /* Formula: (flex container width - 3rem gaps) / 3 items */
            /* = ((100vw - 2rem) - 3rem) / 3 = (100vw - 5rem) / 3 */
            flex: 0 0 calc((100vw - 5rem) / 3);
            min-width: calc((100vw - 5rem) / 3);
            max-width: calc((100vw - 5rem) / 3);
        }
    }
    
    @media (min-width: 1024px) {
        .featured-vehicles-scroll-container > div {
            width: calc(100vw - 2rem);
        }
        
        .featured-vehicle-card {
            flex: 0 0 calc((100vw - 5rem) / 3);
            min-width: calc((100vw - 5rem) / 3);
            max-width: calc((100vw - 5rem) / 3);
        }
    }
    
    @media (min-width: 1280px) {
        .featured-vehicles-scroll-container > div {
            /* On large screens, limit to container max-width (1280px) + parent padding (2rem each = 4rem) */
            /* Scroll container extends, so: min(100vw, 1280px + 4rem) - 2rem scroll padding */
            width: calc(min(100vw, 1316px) - 2rem);
        }
        
        .featured-vehicle-card {
            /* Available width: (min(100vw, 1316px) - 2rem) - 3rem gaps */
            flex: 0 0 calc((min(100vw, 1316px) - 5rem) / 3);
            min-width: calc((min(100vw, 1316px) - 5rem) / 3);
            max-width: calc((min(100vw, 1316px) - 5rem) / 3);
        }
    }
</style>
@endpush

@section('content')
<div class="flex min-h-screen flex-col pt-0">
    <!-- Search Section -->
    <section class="relative bg-gray-100 py-12 md:py-16">
    
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-6">
                <h1 class="text-4xl font-bold tracking-tighter md:text-6xl">
                    {{ $homePageContent['search_title'] ?? __('messages.pages.home.title') }}
                </h1>
            </div>
            <div class="rounded-lg bg-card p-4 md:p-6 shadow-lg">
                <p class="text-muted-foreground text-base md:text-lg mb-4">
                    {{ $homePageContent['search_description'] ?? __('messages.pages.home.description') }}
                </p>

                <div class="flex flex-col gap-4">
                <!-- First Row: 4 Dropdown Fields (equal width) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Brand Dropdown (multi-select) -->
                    <div class="relative flex-1" data-dropdown="brand" data-multiselect="true">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">{{ __('messages.forms.brand') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="{{ __('messages.forms.search_brand') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_brands') }}</button>
                                @foreach($filterOptions['brands'] as $brand)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="{{ $brand->id }}" data-text="{{ $brand->name }}"><span class="dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0">✓</span>{{ $brand->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="dropdown-values-container" data-name="brand_id[]"></div>
                        </div>

                    <!-- Model Dropdown (multi-select) -->
                    <div class="relative flex-1" data-dropdown="model" data-multiselect="true">
                        <button type="button" id="model-dropdown-button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <span class="dropdown-selected">{{ __('messages.forms.model') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="{{ __('messages.forms.search_model') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_models') }}</button>
                                @foreach($filterOptions['models'] as $model)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="{{ $model->id }}" data-text="{{ $model->name }}" data-brand-id="{{ $model->brand_id }}"><span class="dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0">✓</span>{{ $model->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="dropdown-values-container" data-name="model_id[]"></div>
                        </div>

                    <!-- Model Year Dropdown (multi-select) -->
                    <div class="relative flex-1" data-dropdown="model_year" data-multiselect="true">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">{{ __('messages.forms.model_year') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="{{ __('messages.forms.search_year') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_years') }}</button>
                                @foreach($filterOptions['modelYears'] as $modelYear)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="{{ $modelYear->id }}" data-text="{{ $modelYear->name }}"><span class="dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0">✓</span>{{ $modelYear->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="dropdown-values-container" data-name="model_year_id[]"></div>
                        </div>

                    <!-- Fuel Type Dropdown (multi-select) -->
                    <div class="relative flex-1" data-dropdown="fuel_type" data-multiselect="true">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">{{ __('messages.forms.fuel_type') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[200px] rounded-md border border-border bg-background shadow-lg max-h-[300px] overflow-hidden">
                            <div class="p-2 border-b border-border">
                                <input type="text" placeholder="{{ __('messages.forms.search_fuel_type') }}" class="dropdown-search w-full h-8 rounded-md border border-input bg-background px-2 py-1 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" autocomplete="off">
                            </div>
                            <div class="dropdown-options overflow-y-auto max-h-[250px]">
                                <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="" data-text="">{{ __('messages.forms.all_fuel_types') }}</button>
                                @foreach($filterOptions['fuelTypes'] as $fuelType)
                                    <button type="button" class="dropdown-option w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground flex items-center gap-2" data-value="{{ $fuelType->id }}" data-text="{{ $fuelType->name }}"><span class="dropdown-option-check opacity-0 w-4 h-4 flex-shrink-0">✓</span>{{ $fuelType->name }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="dropdown-values-container" data-name="fuel_type_id[]"></div>
                    </div>
                        </div>

                <!-- Second Row: Price, KM Driven + Search Button (equal width) -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Price Dropdown (with range slider) -->
                    <div class="relative flex-1" data-dropdown="price">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">{{ __('messages.forms.price') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[300px] rounded-md border border-border bg-background shadow-lg p-3 sm:p-4">
                            <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2.5 block">{{ __('messages.forms.price_range') }}</label>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex-1">
                                    <input type="number" id="price-from-dropdown" name="price_from" placeholder="{{ __('messages.forms.min') }}" min="0" max="5000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                                <span class="text-muted-foreground/60 text-sm">–</span>
                                <div class="flex-1">
                                    <input type="number" id="price-to-dropdown" name="price_to" placeholder="{{ __('messages.forms.max') }}" min="0" max="5000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                            </div>
                            <div class="relative px-1">
                                <div class="relative h-0.5 bg-muted rounded-full">
                                    <div id="price-range-track-dropdown" class="absolute h-0.5 bg-primary rounded-full"></div>
                                    <input type="range" id="price-slider-min-dropdown" min="0" max="5000000" step="1000" value="0" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-10">
                                    <input type="range" id="price-slider-max-dropdown" min="0" max="5000000" step="1000" value="5000000" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-20">
                                    <div id="price-handle-min-dropdown" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                    <div id="price-handle-max-dropdown" class="absolute w-6 h-6 bg-primary rounded-full border-2 border-background shadow-sm -top-2.5 cursor-pointer z-30"></div>
                                </div>
                            </div>
                        </div>
                        </div>

                    <!-- KM Driven Dropdown (with range slider) -->
                    <div class="relative flex-1" data-dropdown="km_driven">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="dropdown-selected">{{ __('messages.forms.km_driven') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4 opacity-50">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu absolute z-50 mt-1 hidden w-full sm:min-w-[300px] rounded-md border border-border bg-background shadow-lg p-3 sm:p-4">
                            <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2.5 block">{{ __('messages.forms.km_driven_range') }}</label>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="flex-1">
                                    <input type="number" id="km-driven-from" name="km_driven_from" placeholder="{{ __('messages.forms.min') }}" min="0" max="2000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                                <span class="text-muted-foreground/60 text-sm">–</span>
                                <div class="flex-1">
                                    <input type="number" id="km-driven-to" name="km_driven_to" placeholder="{{ __('messages.forms.max') }}" min="0" max="2000000" value="" class="w-full h-9 border-0 border-b border-border bg-transparent px-0 py-2 text-sm placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:border-primary focus-visible:ring-0 transition-colors">
                                </div>
                            </div>
                            <div class="relative px-1">
                                <div class="relative h-0.5 bg-muted rounded-full">
                                    <div id="km-driven-range-track" class="absolute h-0.5 bg-primary rounded-full"></div>
                                    <input type="range" id="km-driven-slider-min" min="0" max="2000000" step="1000" value="0" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-10">
                                    <input type="range" id="km-driven-slider-max" min="0" max="2000000" step="1000" value="2000000" class="absolute w-full h-0.5 opacity-0 cursor-pointer z-20">
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
                                {{ __('messages.common.search') }}
                        </button>
                    </form>
                </div>

                <!-- Third Row: Reset Filters + Advanced Filters -->
                <div class="flex flex-col sm:flex-row justify-start sm:justify-end items-center gap-3 sm:gap-4">
                    <button type="button" id="reset-filters-button" class="text-sm text-muted-foreground hover:text-foreground transition-colors underline bg-transparent border-0 p-0 cursor-pointer">
                        {{ __('messages.pages.vehicles.reset_filters') }}
                    </button>
                    <a href="/vehicles" class="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        {{ __('messages.pages.vehicles.advanced_filters') }}
                    </a>
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
                    {{ $homePageContent['hero_description'] ?? __('messages.pages.home.hero_description') }}
                </p>
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="/vehicles" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        {{ __('messages.pages.home.browse_vehicles') }}
                    </a>
                    <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-md border border-input bg-background px-8 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                        {{ __('messages.pages.footer.contact_us') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Vehicles Section -->
    <section class="py-16 bg-muted">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex flex-col gap-8">
                <div class="space-y-2">
                    <h2 class="text-3xl font-bold tracking-tight">
                        {{ $homePageContent['featured_vehicles_title'] ?? __('messages.pages.home.featured_vehicles_title') }}
                    </h2>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['featured_vehicles_description'] ?? __('messages.pages.home.featured_vehicles_description') }}
                    </p>
                </div>
                
                <!-- Featured Vehicles Horizontal Scroll -->
                <div class="relative">
                    @if(isset($featuredVehicles) && $featuredVehicles->count() > 0)
                    <!-- Navigation Arrows (Desktop Only) -->
                    <button 
                        id="featured-vehicles-prev" 
                        class="featured-vehicles-nav-btn featured-vehicles-nav-btn-left hidden md:flex"
                        aria-label="{{ __('messages.pages.vehicles.detail.previous_vehicles') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button 
                        id="featured-vehicles-next" 
                        class="featured-vehicles-nav-btn featured-vehicles-nav-btn-right hidden md:flex"
                        aria-label="{{ __('messages.pages.vehicles.detail.next_vehicles') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </button>
                    
                    <div class="featured-vehicles-scroll-container overflow-x-auto pb-4 -mx-4 scroll-smooth snap-x snap-mandatory" id="featured-vehicles-scroll">
                        <div class="flex gap-3">
                            @foreach($featuredVehicles as $vehicle)
                        <div class="featured-vehicle-card flex flex-col rounded-2xl bg-card overflow-hidden p-0 cursor-pointer h-full flex-shrink-0">
                            <a href="/vehicles/{{ $vehicle['slug'] ?? $vehicle['id'] }}" class="block flex-1">
                                <!-- Vehicle Image -->
                                <div class="relative aspect-[2/1.5] overflow-hidden p-3 pb-0">
                                    <img
                                        src="{{ $vehicle['image'] }}"
                                        alt="{{ $vehicle['title'] }}"
                                        class="h-full w-full object-cover rounded-md"
                                    />
                                    <div class="absolute top-4 left-4 z-10 flex flex-row flex-wrap items-center gap-1.5">
                                    @if(isset($vehicle['dealer_id']) && $vehicle['dealer_id'])
                                    <!-- Dealer Label - Top Left -->
                                    <span class="inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                        {{ __('messages.pages.vehicles.dealer') }}
                                    </span>
                                    @else
                                    <!-- Private Label - Top Left -->
                                    <span class="inline-flex items-center rounded-md bg-orange-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                        {{ __('messages.pages.vehicles.private') }}
                                    </span>
                                    @endif
                                    @if(!empty($vehicle['sales_type_name']))
                                    <span class="inline-flex items-center rounded-md bg-green-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                        {{ $vehicle['sales_type_name'] }}
                                    </span>
                                    @endif
                                    </div>
                                    <!-- Heart Icon - Top Right -->
                                    <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle['id'] }}, event); return false;" aria-label="{{ __('messages.forms.add_to_favorites') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 {{ isset($vehicle['dealer_id']) && $vehicle['dealer_id'] ? 'text-blue-600' : 'text-orange-600' }} hover:text-red-500 transition-colors heart-icon" data-vehicle-id="{{ $vehicle['id'] }}" data-dealer-id="{{ $vehicle['dealer_id'] ?? '' }}">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Vehicle Details -->
                                <div class="p-3 space-y-1">
                                    <div class="flex flex-col gap-1">
                                        <h3 class="flex items-center gap-2 text-xs">
                                            {{ $vehicle['title'] }}
                                        </h3>
                                        @if(!empty($vehicle['version']))
                                        <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                                            {{ $vehicle['version'] }}
                                        </p>
                                        @endif
                                        <p class="text-lg font-bold">
                                            {{ \App\Helpers\FormatHelper::formatCurrency($vehicle['price'] ?? null) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-1 text-xs font-light">
                                        @if($vehicle['km_driven'])
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle['km_driven'], 0, '.', ',') }} km</span>
                                        @endif
                                        @if($vehicle['engine_power_hp'])
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format(round($vehicle['engine_power_hp']), 0) }} HP</span>
                                        @endif
                                        @if($vehicle['first_registration_date'])
                                        @php
                                            $regDate = \Carbon\Carbon::parse($vehicle['first_registration_date']);
                                        @endphp
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $regDate->format('M Y') }}</span>
                                        @endif
                                        @if($vehicle['fuel_type_name'])
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle['fuel_type_name'] }}</span>
                                        @endif
                                        @if($vehicle['gear_type_name'])
                                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle['gear_type_name'] }}</span>
                                        @endif
                                    </div>

                                </div>
                            </a>
                            
                            <!-- Card Footer -->
                            <div class="mt-auto" onclick="event.stopPropagation()">
                                @if((isset($vehicle['seller_address']) && $vehicle['seller_address']) || (isset($vehicle['seller_postcode']) && $vehicle['seller_postcode']))
                                <div class="px-3 pt-3 pb-2">
                                    <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span class="truncate text-right">
                                            @if(isset($vehicle['seller_address']) && $vehicle['seller_address']){{ $vehicle['seller_address'] }}@endif
                                            @if(isset($vehicle['seller_address']) && $vehicle['seller_address'] && isset($vehicle['seller_postcode']) && $vehicle['seller_postcode']), @endif
                                            @if(isset($vehicle['seller_postcode']) && $vehicle['seller_postcode']){{ $vehicle['seller_postcode'] }}@endif
                                        </span>
                                    </div>
                                </div>
                                @endif
                                <!-- Vehicle Actions -->
                                <div class="p-3 pt-0">
                                    <div class="flex w-full flex-col gap-2 sm:flex-row">
                                        <a href="/vehicles/{{ $vehicle['slug'] ?? $vehicle['id'] }}" class="flex-1" onclick="event.stopPropagation()">
                                            <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                                {{ __('messages.pages.vehicles.view_details') }}
                                            </button>
                                        </a>
                                        <button 
                                            type="button"
                                            onclick="event.stopPropagation(); openEnquiryDialog('enquiry', '{{ $vehicle['slug'] ?? $vehicle['id'] }}')"
                                            class="flex-1 inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                                        >
                                            {{ __('messages.pages.vehicles.enquire') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                            @endforeach
                        </div>
                    </div>
                    <!-- Enquiry Dialogs for Featured Vehicles -->
                    @foreach($featuredVehicles as $vehicle)
                        <x-enquiry-dialog type="enquiry" :vehicle="$vehicle" />
                    @endforeach

                    <!-- Login Dialog -->
                    <x-login-dialog />
                    <div class="mt-4 flex justify-center">
                        <a href="/vehicles" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                            {{ __('messages.pages.home.view_all_vehicles') }}
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                                <path d="M5 12h14M12 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                    @else
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <p class="text-muted-foreground">{{ __('messages.pages.home.no_featured_vehicles') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['stats_title'] ?? __('messages.pages.home.why_choose_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['stats_description'] ?? __('messages.pages.home.why_choose_description') }}
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
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_1_value'] ?? '100+' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_1_title'] ?? 'Quality Vehicles' }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_1_description'] ?? 'Thoroughly inspected vehicles in our inventory' }}</p>
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
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_2_value'] ?? '500+' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_2_title'] ?? 'Happy Customers' }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_2_description'] ?? 'Satisfied customers who found their perfect vehicle' }}</p>
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
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_3_value'] ?? '15+' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_3_title'] ?? 'Years of Experience' }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_3_description'] ?? 'Years serving our community with integrity' }}</p>
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
                        <h3 class="text-xl font-bold">{{ $homePageContent['stat_4_value'] ?? '98%' }}</h3>
                        <p class="mb-2 font-medium">{{ $homePageContent['stat_4_title'] ?? 'Satisfaction Rate' }}</p>
                        <p class="text-sm text-muted-foreground">{{ $homePageContent['stat_4_description'] ?? 'Customer satisfaction based on reviews' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-muted">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['features_title'] ?? __('messages.pages.home.our_services_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['features_description'] ?? __('messages.pages.home.our_services_description') }}
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
                    <h3 class="mb-2 text-xl font-bold">{{ $homePageContent['feature_1_title'] ?? 'Financing Options' }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_1_description'] ?? 'We work with multiple lenders to find the best financing solutions for your budget.' }}
                    </p>
                </div>
                <div class="bg-card flex flex-col items-center rounded-lg border border-border p-6 text-center">
                    <div class="mb-4 rounded-full bg-primary/10 p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-8 w-8 text-primary">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold">{{ $homePageContent['feature_2_title'] ?? 'Vehicle Warranty' }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_2_description'] ?? 'Extended warranty options to protect your investment and give you peace of mind.' }}
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
                    <h3 class="mb-2 text-xl font-bold">{{ $homePageContent['feature_3_title'] ?? 'Service Department' }}</h3>
                    <p class="text-muted-foreground">
                        {{ $homePageContent['feature_3_description'] ?? 'Professional maintenance and repair services to keep your vehicle in top condition.' }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="mb-2 text-3xl font-bold tracking-tight">
                    {{ $homePageContent['testimonials_title'] ?? __('messages.pages.home.testimonials_title') }}
                </h2>
                <p class="mx-auto max-w-2xl text-muted-foreground">
                    {{ $homePageContent['testimonials_description'] ?? __('messages.pages.home.testimonials_description') }}
                </p>
            </div>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $testimonials = [
                        [
                            'name' => $homePageContent['testimonial_1_name'] ?? 'John Davis',
                            'location' => $homePageContent['testimonial_1_location'] ?? 'Copenhagen, Denmark',
                            'quote' => $homePageContent['testimonial_1_quote'] ?? 'The team at Bilskyen made buying a car so easy. They were transparent about pricing and helped me find the perfect vehicle for my family.',
                            'rating' => (int)($homePageContent['testimonial_1_rating'] ?? 5)
                        ],
                        [
                            'name' => $homePageContent['testimonial_2_name'] ?? 'Priya Sharma',
                            'location' => $homePageContent['testimonial_2_location'] ?? 'Aarhus, Denmark',
                            'quote' => $homePageContent['testimonial_2_quote'] ?? 'I was impressed with their knowledge and no-pressure approach. I got a great deal on my new car and would definitely recommend them.',
                            'rating' => (int)($homePageContent['testimonial_2_rating'] ?? 5)
                        ],
                        [
                            'name' => $homePageContent['testimonial_3_name'] ?? 'Ahmed Khan',
                            'location' => $homePageContent['testimonial_3_location'] ?? 'Odense, Denmark',
                            'quote' => $homePageContent['testimonial_3_quote'] ?? 'The financing options they provided were better than I expected. The entire process was smooth and I drove away very happy.',
                            'rating' => (int)($homePageContent['testimonial_3_rating'] ?? 4)
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
    const DEFAULT_DROPDOWN_LABELS = {
        'brand': '{{ __('messages.forms.brand') }}',
        'model': '{{ __('messages.forms.model') }}',
        'model_year': '{{ __('messages.forms.model_year') }}',
        'fuel_type': '{{ __('messages.forms.fuel_type') }}'
    };

    function getMultiSelectValues(dropdown) {
        const options = dropdown.querySelectorAll('.dropdown-option[data-value]:not([data-value=""])');
        return Array.from(options).filter(opt => opt.classList.contains('selected')).map(opt => opt.getAttribute('data-value'));
    }

    function syncMultiSelectInputs(dropdown) {
        const container = dropdown.querySelector('.dropdown-values-container');
        if (!container) return;
        const name = container.getAttribute('data-name');
        const values = getMultiSelectValues(dropdown);
        container.innerHTML = '';
        values.forEach(v => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = v;
            container.appendChild(input);
        });
    }

    function updateMultiSelectButtonText(dropdown) {
        const selectedText = dropdown.querySelector('.dropdown-selected');
        const dropdownType = dropdown.getAttribute('data-dropdown');
        const options = dropdown.querySelectorAll('.dropdown-option[data-value]:not([data-value=""])');
        const selected = Array.from(options).filter(opt => opt.classList.contains('selected'));
        const labels = selected.map(opt => opt.getAttribute('data-text') || opt.textContent.trim());
        const defaultLabel = DEFAULT_DROPDOWN_LABELS[dropdownType] || '{{ __('messages.common.select') }}';
        if (labels.length === 0) {
            selectedText.textContent = defaultLabel;
        } else if (labels.length <= 2) {
            selectedText.textContent = labels.join(', ');
        } else {
            selectedText.textContent = defaultLabel + ' (' + labels.length + ')';
        }
    }

    function initSearchableDropdowns() {
        const dropdowns = document.querySelectorAll('[data-dropdown]');
        
        dropdowns.forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            const searchInput = dropdown.querySelector('.dropdown-search');
            const options = dropdown.querySelectorAll('.dropdown-option');
            const valuesContainer = dropdown.querySelector('.dropdown-values-container');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            const dropdownType = dropdown.getAttribute('data-dropdown');
            const isMultiSelect = dropdown.getAttribute('data-multiselect') === 'true';
            
            // Toggle menu
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                if (button.disabled) return;
                dropdowns.forEach(d => {
                    if (d !== dropdown) d.querySelector('.dropdown-menu').classList.add('hidden');
                });
                menu.classList.toggle('hidden');
                if (!menu.classList.contains('hidden')) {
                    if (dropdownType === 'model') {
                        const brandDropdown = document.querySelector('[data-dropdown="brand"]');
                        const brandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
                        filterModelsByBrand(brandIds);
                    }
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.dispatchEvent(new Event('input'));
                        setTimeout(() => searchInput.focus(), 50);
                    }
                }
            });
            
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    if (dropdownType === 'model') {
                        const brandDropdown = document.querySelector('[data-dropdown="brand"]');
                        const brandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
                        filterModelsByBrand(brandIds);
                    } else {
                        options.forEach(option => {
                            const text = (option.getAttribute('data-text') || option.textContent).toLowerCase();
                            option.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    }
                });
            }
            
            if (dropdownType !== 'price' && dropdownType !== 'km_driven') {
                options.forEach(option => {
                    option.addEventListener('click', () => {
                        const value = option.getAttribute('data-value');
                        const text = option.getAttribute('data-text') || option.textContent.trim();
                        
                        if (isMultiSelect) {
                            if (value === '') {
                                options.forEach(opt => opt.classList.remove('selected'));
                                syncMultiSelectInputs(dropdown);
                                updateMultiSelectButtonText(dropdown);
                                if (dropdownType === 'brand') filterModelsByBrand([]);
                                menu.classList.add('hidden');
                                return;
                            }
                            option.classList.toggle('selected');
                            syncMultiSelectInputs(dropdown);
                            updateMultiSelectButtonText(dropdown);
                            if (dropdownType === 'brand') {
                                const brandIds = getMultiSelectValues(dropdown);
                                filterModelsByBrand(brandIds);
                            }
                            return;
                        }
                        
                        if (valuesContainer) {
                            valuesContainer.innerHTML = '';
                            if (value) {
                                const name = valuesContainer.getAttribute('data-name');
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = name;
                                input.value = value;
                                valuesContainer.appendChild(input);
                            }
                        }
                        
                        if (value === '') {
                            selectedText.textContent = DEFAULT_DROPDOWN_LABELS[dropdownType] || '{{ __('messages.common.select') }}';
                        } else {
                            selectedText.textContent = text;
                        }
                        if (dropdownType === 'brand') filterModelsByBrand(value);
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
                        selectedText.textContent = '{{ __('messages.forms.price') }}';
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
                        selectedText.textContent = '{{ __('messages.forms.km_driven') }}';
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
    
    // Filter models by brand(s)
    function filterModelsByBrand(brandIds) {
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (!modelDropdown) return;
        
        const modelButton = document.getElementById('model-dropdown-button');
        const modelOptions = modelDropdown.querySelectorAll('.dropdown-option[data-brand-id]');
        const defaultOption = modelDropdown.querySelector('.dropdown-option[data-value=""]');
        const selectedText = modelDropdown.querySelector('.dropdown-selected');
        const searchInput = modelDropdown.querySelector('.dropdown-search');
        const searchTerm = searchInput?.value.toLowerCase() || '';
        const ids = Array.isArray(brandIds) ? brandIds : (brandIds === '' || !brandIds ? [] : [String(brandIds)]);
        
        if (ids.length === 0) {
            if (modelButton) modelButton.disabled = true;
            if (selectedText) selectedText.textContent = '{{ __('messages.forms.model') }}';
            syncMultiSelectInputs(modelDropdown);
            modelDropdown.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
            updateMultiSelectButtonText(modelDropdown);
            modelOptions.forEach(option => option.style.display = 'none');
            const menu = modelDropdown.querySelector('.dropdown-menu');
            if (menu) menu.classList.add('hidden');
            return;
        }
        
        if (modelButton) modelButton.disabled = false;
        if (selectedText && getMultiSelectValues(modelDropdown).length === 0) {
            selectedText.textContent = '{{ __('messages.forms.model') }}';
        }
        
        const idSet = new Set(ids.map(String));
        modelOptions.forEach(option => {
            const optionBrandId = option.getAttribute('data-brand-id');
            const text = (option.getAttribute('data-text') || option.textContent).toLowerCase();
            const matchesBrand = idSet.has(optionBrandId);
            const matchesSearch = !searchTerm || text.includes(searchTerm);
            option.style.display = (matchesBrand && matchesSearch) ? '' : 'none';
        });
        
        if (defaultOption) defaultOption.style.display = '';
        
        const currentValues = getMultiSelectValues(modelDropdown);
        if (currentValues.length > 0) {
            const toRemove = [];
            modelOptions.forEach(opt => {
                if (opt.classList.contains('selected')) {
                    const optionBrandId = opt.getAttribute('data-brand-id');
                    if (!idSet.has(optionBrandId)) toRemove.push(opt);
                }
            });
            toRemove.forEach(opt => opt.classList.remove('selected'));
            if (toRemove.length) {
                syncMultiSelectInputs(modelDropdown);
                updateMultiSelectButtonText(modelDropdown);
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
            max: 5000000
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
            max: 2000000
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
                        config.selectedText.textContent = config === priceConfig ? '{{ __('messages.forms.price') }}' : '{{ __('messages.forms.km_driven') }}';
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
            const params = new URLSearchParams();
            
            document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
                const valuesContainer = dropdown.querySelector('.dropdown-values-container');
                if (valuesContainer) {
                    valuesContainer.querySelectorAll('input[type="hidden"]').forEach(input => {
                        if (input.name && input.value) params.append(input.name, input.value);
                    });
                }
            });
            
            const priceFrom = document.getElementById('price-from-dropdown')?.value;
            const priceTo = document.getElementById('price-to-dropdown')?.value;
            const kmFrom = document.getElementById('km-driven-from')?.value;
            const kmTo = document.getElementById('km-driven-to')?.value;
            if (priceFrom) params.append('price_from', priceFrom);
            if (priceTo) params.append('price_to', priceTo);
            if (kmFrom) params.append('km_driven_from', kmFrom);
            if (kmTo) params.append('km_driven_to', kmTo);
            
            window.location.href = '/vehicles' + (params.toString() ? '?' + params.toString() : '');
        });
    }
    
    // Reset filters function
    function resetAllFilters() {
        const defaultTexts = DEFAULT_DROPDOWN_LABELS;
        
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            const dropdownType = dropdown.getAttribute('data-dropdown');
            const selectedText = dropdown.querySelector('.dropdown-selected');
            const valuesContainer = dropdown.querySelector('.dropdown-values-container');
            const options = dropdown.querySelectorAll('.dropdown-option');
            
            if (valuesContainer) {
                valuesContainer.innerHTML = '';
            }
            options.forEach(opt => opt.classList.remove('selected'));
            if (selectedText && defaultTexts[dropdownType]) {
                selectedText.textContent = defaultTexts[dropdownType];
            }
        });
        
        const modelDropdown = document.querySelector('[data-dropdown="model"]');
        if (modelDropdown) {
            const modelSearchInput = modelDropdown.querySelector('.dropdown-search');
            if (modelSearchInput) modelSearchInput.value = '';
            filterModelsByBrand([]);
        }
        const priceFromInput = document.getElementById('price-from-dropdown');
        const priceToInput = document.getElementById('price-to-dropdown');
        const priceMinSlider = document.getElementById('price-slider-min-dropdown');
        const priceMaxSlider = document.getElementById('price-slider-max-dropdown');
        const priceSelectedText = document.querySelector('[data-dropdown="price"] .dropdown-selected');
        
        if (priceFromInput) priceFromInput.value = '';
        if (priceToInput) priceToInput.value = '';
        if (priceMinSlider) priceMinSlider.value = '0';
        if (priceMaxSlider) priceMaxSlider.value = '5000000';
        if (priceSelectedText) priceSelectedText.textContent = '{{ __('messages.forms.price') }}';
        
        // Reset KM driven range
        const kmFromInput = document.getElementById('km-driven-from');
        const kmToInput = document.getElementById('km-driven-to');
        const kmMinSlider = document.getElementById('km-driven-slider-min');
        const kmMaxSlider = document.getElementById('km-driven-slider-max');
        const kmSelectedText = document.querySelector('[data-dropdown="km_driven"] .dropdown-selected');
        
        if (kmFromInput) kmFromInput.value = '';
        if (kmToInput) kmToInput.value = '';
        if (kmMinSlider) kmMinSlider.value = '0';
        if (kmMaxSlider) kmMaxSlider.value = '2000000';
        if (kmSelectedText) kmSelectedText.textContent = '{{ __('messages.forms.km_driven') }}';
        
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
                max: 5000000
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
                max: 2000000
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
    }
    
    // Reset filters button handler
    const resetFiltersButton = document.getElementById('reset-filters-button');
    if (resetFiltersButton) {
        resetFiltersButton.addEventListener('click', resetAllFilters);
    }
    
    // Initialize everything
    initSearchableDropdowns();
    initDropdownRangeSliders();
    
    const brandDropdown = document.querySelector('[data-dropdown="brand"]');
    const initialBrandIds = brandDropdown ? getMultiSelectValues(brandDropdown) : [];
    filterModelsByBrand(initialBrandIds);
    </script>
    
    <!-- Featured Vehicles Navigation Script -->
    <script>
        (function() {
            const scrollContainer = document.getElementById('featured-vehicles-scroll');
            const prevBtn = document.getElementById('featured-vehicles-prev');
            const nextBtn = document.getElementById('featured-vehicles-next');
            
            if (!scrollContainer || !prevBtn || !nextBtn) return;
            
            // Calculate scroll amount (width of one card + gap)
            const getScrollAmount = () => {
                const firstCard = scrollContainer.querySelector('.featured-vehicle-card');
                if (!firstCard) return scrollContainer.clientWidth;
                const cardWidth = firstCard.offsetWidth;
                const gap = 24; // gap-6 = 1.5rem = 24px
                return cardWidth + gap;
            };
            
            // Update button states based on scroll position
            const updateButtonStates = () => {
                const { scrollLeft, scrollWidth, clientWidth } = scrollContainer;
                const isAtStart = scrollLeft <= 1;
                const isAtEnd = scrollLeft >= scrollWidth - clientWidth - 1;
                
                prevBtn.disabled = isAtStart;
                nextBtn.disabled = isAtEnd;
            };
            
            // Scroll functions
            const scrollLeft = () => {
                const scrollAmount = getScrollAmount();
                scrollContainer.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            };
            
            const scrollRight = () => {
                const scrollAmount = getScrollAmount();
                scrollContainer.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            };
            
            // Event listeners
            prevBtn.addEventListener('click', scrollLeft);
            nextBtn.addEventListener('click', scrollRight);
            
            // Update button states on scroll
            scrollContainer.addEventListener('scroll', updateButtonStates);
            
            // Update button states on resize
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(updateButtonStates, 100);
            });
            
            // Initial state update
            updateButtonStates();
            
            // Update after images load (in case cards resize)
            const images = scrollContainer.querySelectorAll('img');
            let loadedImages = 0;
            images.forEach(img => {
                if (img.complete) {
                    loadedImages++;
                } else {
                    img.addEventListener('load', () => {
                        loadedImages++;
                        if (loadedImages === images.length) {
                            setTimeout(updateButtonStates, 100);
                        }
                    });
                }
            });
            if (loadedImages === images.length) {
                setTimeout(updateButtonStates, 100);
            }
        })();
    </script>

@push('scripts')
<script>
    (function() {
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
        
        // Load favorite status for all vehicles in batch
        async function checkFavoritesBatch() {
            const heartIcons = document.querySelectorAll('.heart-icon');
            if (heartIcons.length === 0) return;

            // Collect all vehicle IDs
            const vehicleIds = [];
            const iconMap = new Map(); // Map vehicle ID to icon element
            
            heartIcons.forEach(icon => {
                const vehicleId = icon.getAttribute('data-vehicle-id');
                if (vehicleId) {
                    vehicleIds.push(parseInt(vehicleId));
                    iconMap.set(parseInt(vehicleId), icon);
                }
            });

            if (vehicleIds.length === 0) return;

            try {
                // Make single batch API call
                const response = await fetch('/favorites/check-batch', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ vehicle_ids: vehicleIds })
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success' && data.data) {
                        // Update icons based on batch response
                        Object.keys(data.data).forEach(vehicleIdStr => {
                            const vehicleId = parseInt(vehicleIdStr);
                            const isFavorited = data.data[vehicleId];
                            const icon = iconMap.get(vehicleId);
                            
                            if (icon && isFavorited) {
                                icon.classList.add('filled');
                                icon.classList.remove('text-gray-700');
                                icon.classList.add('text-red-500');
                                const path = icon.querySelector('path');
                                if (path) {
                                    path.setAttribute('fill', 'currentColor');
                                }
                            }
                        });
                    }
                }
            } catch (error) {
                // Silently fail if auth check fails or user is not authenticated
                console.debug('Favorite check failed (user may not be authenticated):', error);
            }
        }
        
        // Toggle favorite function
        window.toggleFavorite = async function(vehicleId, event) {
            // Prevent any default behavior and stop propagation
            if (event) {
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
            }
            
            // Check if user is authenticated
            if (!isUserAuthenticated()) {
                // Open login dialog with callback to favorite after login
                if (window.openLoginDialog) {
                    window.openLoginDialog(() => {
                        // After successful login, automatically favorite the vehicle
                        window.toggleFavorite(vehicleId, event);
                    });
                }
                return false;
            }
            
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            try {
                const heartIcon = document.querySelector(`.heart-icon[data-vehicle-id="${vehicleId}"]`);
                if (!heartIcon) {
                    console.error('Heart icon not found for vehicle:', vehicleId);
                    return false;
                }
                
                const path = heartIcon.querySelector('path');
                const isFavorited = heartIcon.classList.contains('filled') || path?.getAttribute('fill') === 'currentColor';

                if (isFavorited) {
                    // Remove from favorites
                    const response = await fetch(`/favorites/${vehicleId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (response.ok) {
                        const data = await response.json().catch(() => ({}));
                        heartIcon.classList.remove('filled');
                        heartIcon.classList.remove('text-red-500');
                        // Restore original color based on dealer status
                        const dealerId = heartIcon.getAttribute('data-dealer-id');
                        if (dealerId && dealerId !== '') {
                            heartIcon.classList.add('text-blue-600');
                            heartIcon.classList.remove('text-orange-600');
                        } else {
                            heartIcon.classList.add('text-orange-600');
                            heartIcon.classList.remove('text-blue-600');
                        }
                        if (path) path.setAttribute('fill', 'none');
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.messages.removed_from_favorites') }}', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('{{ __('messages.errors.please_login') }}', 'error');
                            }
                            // Open login dialog instead of redirecting
                            if (window.openLoginDialog) {
                                window.openLoginDialog(() => {
                                    window.toggleFavorite(vehicleId, event);
                                });
                            } else {
                                setTimeout(() => {
                                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                                }, 1500);
                            }
                            return false;
                        }
                        const data = await response.json().catch(() => ({}));
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_create_vehicle') }}', 'error');
                        }
                    }
                } else {
                    // Add to favorites
                    const response = await fetch('/favorites', {
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

                    if (response.ok) {
                        const data = await response.json().catch(() => ({}));
                        heartIcon.classList.add('filled');
                        heartIcon.classList.remove('text-blue-600', 'text-orange-600');
                        heartIcon.classList.add('text-red-500');
                        if (path) path.setAttribute('fill', 'currentColor');
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.messages.saved_to_favorites') }}', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('{{ __('messages.errors.please_login_to_save') }}', 'error');
                            }
                            // Open login dialog instead of redirecting
                            if (window.openLoginDialog) {
                                window.openLoginDialog(() => {
                                    window.toggleFavorite(vehicleId, event);
                                });
                            } else {
                                setTimeout(() => {
                                    window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                                }, 1500);
                            }
                            return false;
                        }
                        const data = await response.json().catch(() => ({}));
                        if (window.showSnackbar) {
                            window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_create_vehicle') }}', 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                if (window.showSnackbar) {
                    window.showSnackbar('{{ __('messages.errors.failed_to_create_vehicle') }}', 'error');
                }
            }
            
            return false;
        };
        
        // openEnquiryDialog is available globally from layouts/app.blade.php
        // No authentication check needed - guests can submit enquiries
        
        // Load favorite status on page load
        (async function() {
            await checkFavoritesBatch();
        })();
    })();
</script>
@endpush
@endsection

