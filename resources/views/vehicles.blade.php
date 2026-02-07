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
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-none  focus:bg-none">
            <!-- Search Input -->
            <form class="flex w-full sm:flex-1 focus:bg-none bg-none" method="GET" action="/vehicles" id="search-form">
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
                        class="flex h-10 w-full rounded-md pl-9 pr-2.5 py-1.5 text-sm placeholder:text-muted-foreground focus-visible:outline-none"
                        autocomplete="off"
                    />
                </div>
            </form>
            
        </div>
    </div>

    <!-- Mobile Filter Toggle Button -->
            <button 
        id="mobile-filter-toggle"
                type="button" 
        class="lg:hidden fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-all hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        aria-label="Toggle filters"
            >
        <!-- Filter Icon (shown when sidebar is closed) -->
        <svg id="mobile-filter-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <line x1="4" x2="20" y1="21" y2="21"></line>
                    <line x1="4" x2="20" y1="7" y2="7"></line>
                    <line x1="4" x2="20" y1="3" y2="3"></line>
                    <line x1="4" x2="20" y1="11" y2="11"></line>
                    <line x1="4" x2="20" y1="15" y2="15"></line>
                </svg>
        <!-- Close Icon (shown when sidebar is open) -->
        <svg id="mobile-close-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 hidden">
            <path d="M18 6 6 18"></path>
            <path d="m6 6 12 12"></path>
        </svg>
            </button>

    <!-- Filters + Sort/View/Layout -->
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 items-start">
        <!-- Filter Sidebar -->
        <aside
            id="filter-sidebar"
            class="hidden lg:block fixed lg:relative inset-0 lg:inset-auto z-40 lg:z-auto overflow-y-auto lg:overflow-visible bg-background lg:bg-card p-4 lg:p-4 shadow-lg lg:shadow-sm space-y-6 lg:rounded-lg w-full lg:w-72 xl:w-80 shrink-0"
        >
            <!-- Condition Filter -->
            <div class="space-y-3">
                <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Condition
                </p>
                <div class="inline-flex items-center gap-1 p-1 rounded-full bg-gray-150">
                    <label class="condition-radio-label inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium cursor-pointer transition-all @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') bg-white text-foreground font-semibold shadow-sm @else bg-gray-150 text-muted-foreground hover:text-foreground @endif">
                        <input 
                            type="radio" 
                            name="condition_id" 
                            value=""
                            class="sr-only peer condition-radio"
                            @if(!isset($currentFilters['condition_id']) || $currentFilters['condition_id'] == '') checked @endif
                        >
                        <span>All</span>
                    </label>
                    @foreach($filterOptions['conditions'] as $condition)
                        <label class="condition-radio-label inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium cursor-pointer transition-all @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition->id) bg-white text-foreground font-semibold shadow-sm @else bg-gray-150 text-muted-foreground hover:text-foreground @endif">
                            <input 
                                type="radio" 
                                name="condition_id" 
                                value="{{ $condition->id }}"
                                class="sr-only peer condition-radio"
                                @if(isset($currentFilters['condition_id']) && $currentFilters['condition_id'] == $condition->id) checked @endif
                            >
                            <span>{{ $condition->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Listing Type: Purchase / Leasing -->
            <div class="space-y-3">
                <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Listing Type
                </p>
                <div class="inline-flex items-center gap-1 p-1 rounded-full bg-gray-150">
                    @php
                        $purchaseType = $filterOptions['listingTypes']->firstWhere('name', 'Purchase');
                        $leasingType = $filterOptions['listingTypes']->firstWhere('name', 'Leasing');
                        $selectedListingTypes = isset($currentFilters['listing_type_id']) ? (is_array($currentFilters['listing_type_id']) ? $currentFilters['listing_type_id'] : [$currentFilters['listing_type_id']]) : [];
                        $isPurchaseActive = $purchaseType && in_array($purchaseType->id, $selectedListingTypes);
                        $isLeasingActive = $leasingType && in_array($leasingType->id, $selectedListingTypes);
                    @endphp
                    @if($purchaseType)
                        <label class="listing-type-checkbox-label inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium cursor-pointer transition-all @if($isPurchaseActive) bg-white text-foreground font-semibold shadow-sm @else bg-gray-150 text-muted-foreground hover:text-foreground @endif">
                                <input 
                                    type="checkbox" 
                                name="listing_type_id[]" 
                                    value="{{ $purchaseType->id }}"
                                class="sr-only peer listing-type-checkbox"
                                    @if($isPurchaseActive) checked @endif
                                >
                                <span>Purchase</span>
                        </label>
                    @endif
                    @if($leasingType)
                        <label class="listing-type-checkbox-label inline-flex items-center px-4 py-1.5 rounded-full text-sm font-medium cursor-pointer transition-all @if($isLeasingActive) bg-white text-foreground font-semibold shadow-sm @else bg-gray-150 text-muted-foreground hover:text-foreground @endif">
                                <input 
                                    type="checkbox" 
                                name="listing_type_id[]" 
                                    value="{{ $leasingType->id }}"
                                class="sr-only peer listing-type-checkbox"
                                    @if($isLeasingActive) checked @endif
                                >
                                <span>Leasing</span>
                        </label>
                    @endif
                </div>
            </div>
            
            <!-- Price Range -->
            <div class="space-y-4">
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Price Range</label>
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
                                class="w-full h-10 rounded-sm border border-input bg-background pl-12 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
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
                                class="w-full h-10 rounded-sm border border-input bg-background pl-12 pr-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 transition-colors"
                            >
                        </div>
                    </div>
                </div>
                <!-- Range Slider -->
                <div class="relative px-2 py-4">
                    <div class="relative h-2 bg-muted rounded-full">
                        <div id="price-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                            value="{{ $currentFilters['price_to'] ?? 0 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="price-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="price-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Type, Brand, Model Section -->
                <div>
                <!-- Section Heading -->
                <div class="flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-foreground">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6.75V8.25H8.25v10.5ZM6 10.5a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1 0-1.5h.75a.75.75 0 0 1 .75.75ZM6 15a.75.75 0 0 1-.75.75h-.75a.75.75 0 0 1 0-1.5h.75A.75.75 0 0 1 6 15Z" />
                    </svg>
                    <h3 class="text-sm text-foreground">Type, Brand, Model</h3>
                </div>
                

                <!-- Brand Filter Row -->
                <div class="flex items-center justify-between py-2">
                    <label class="text-sm text-muted-foreground">Brand</label>
                    <div class="relative inline-block">
                        <select 
                            name="brand_id"
                            id="brand-select"
                            class="appearance-none bg-transparent border-none text-sm text-foreground font-medium pr-7 pl-4 py-1.5 text-right cursor-pointer focus:outline-none focus:ring-0 rounded-md transition-colors min-w-[120px]"
                        >
                            <option value="">All</option>
                            @foreach($filterOptions['brands'] as $brand)
                            <option value="{{ $brand->id }}" @if(isset($currentFilters['brand_id']) && $currentFilters['brand_id'] == $brand->id) selected @endif>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute right-1 top-1/2 -translate-y-1/2 w-4 h-4 text-foreground pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>

                <!-- Model Filter Row -->
                <div class="flex items-center justify-between py-2">
                    <label class="text-sm text-muted-foreground">Model</label>
                    <div class="relative inline-block">
                    <select 
                        name="model_id" 
                        id="model-select"
                            class="appearance-none bg-transparent border-none text-sm text-foreground font-medium pr-7 pl-4 py-1.5 text-right cursor-pointer focus:outline-none focus:ring-0 rounded-md transition-colors min-w-[120px] disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(!isset($currentFilters['brand_id']) || empty($currentFilters['brand_id'])) disabled @endif
                    >
                            <option value="">@if(!isset($currentFilters['brand_id']) || empty($currentFilters['brand_id'])) Model @else All @endif</option>
                        @foreach($filterOptions['models'] as $model)
                            <option value="{{ $model->id }}" data-brand-id="{{ $model->brand_id }}" @if(isset($currentFilters['model_id']) && $currentFilters['model_id'] == $model->id) selected @endif>{{ $model->name }}</option>
                        @endforeach
                    </select>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute right-1 top-1/2 -translate-y-1/2 w-4 h-4 text-foreground pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                </div>
            </div>

                <!-- Body Style Filter Row -->
                <div class="flex items-center justify-between py-2">
                    <label class="text-sm text-muted-foreground">Body Style</label>
                    <div class="relative inline-block">
                <select 
                    name="category_id" 
                            class="appearance-none bg-transparent border-none text-sm text-foreground font-medium pr-7 pl-4 py-1.5 text-right cursor-pointer focus:outline-none focus:ring-0 rounded-md transition-colors min-w-[120px]"
                >
                            <option value="">All</option>
                    @foreach($filterOptions['categories'] as $category)
                        <option value="{{ $category->id }}" @if(isset($currentFilters['category_id']) && $currentFilters['category_id'] == $category->id) selected @endif>{{ $category->name }}</option>
                    @endforeach
                </select>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="absolute right-1 top-1/2 -translate-y-1/2 w-4 h-4 text-foreground pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Owner Tax Range -->
            <div class="space-y-4">
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Owner Tax</label>
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
                        <div id="owner-tax-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                            value="{{ $currentFilters['ownership_tax_to'] ?? 0 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="owner-tax-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="owner-tax-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Model Year Range -->
            <div class="space-y-4">
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Model Year</label>
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
                        <div id="year-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                            value="{{ $currentFilters['year_to'] ?? date('Y') + 1 }}"
                            class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                        >
                        <div id="year-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        <div id="year-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="space-y-5">
                <!-- Mileage Range -->
                <div class="space-y-4">
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Mileage (km)</label>
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
                            <div id="mileage-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                                value="{{ $currentFilters['mileage_to'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="mileage-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="mileage-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Price Type -->
                <div>
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Price Type</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($filterOptions['priceTypes'] as $priceType)
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['price_type_id']) && (is_array($currentFilters['price_type_id']) ? in_array($priceType->id, $currentFilters['price_type_id']) : $currentFilters['price_type_id'] == $priceType->id)) bg-accent border-foreground @endif">
                                <input 
                                    type="checkbox" 
                        name="price_type_id[]" 
                                    value="{{ $priceType->id }}"
                                    class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['price_type_id']) && (is_array($currentFilters['price_type_id']) ? in_array($priceType->id, $currentFilters['price_type_id']) : $currentFilters['price_type_id'] == $priceType->id)) checked @endif
                                >
                                <span>{{ $priceType->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Vehicle Body Type -->
                <div>
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Body Type</label>
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
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['body_type_id']) && (is_array($currentFilters['body_type_id']) ? in_array($bodyType->id, $currentFilters['body_type_id']) : $currentFilters['body_type_id'] == $bodyType->id)) bg-accent border-foreground @endif">
                                    <input 
                                        type="checkbox" 
                                        name="body_type_id[]" 
                                        value="{{ $bodyType->id }}"
                                        class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Fuel Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($filterOptions['fuelTypes'] as $fuelType)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['fuel_type_id']) && (is_array($currentFilters['fuel_type_id']) ? in_array($fuelType->id, $currentFilters['fuel_type_id']) : $currentFilters['fuel_type_id'] == $fuelType->id)) bg-accent border-foreground @endif">
                            <input 
                                type="checkbox" 
                                name="fuel_type_id[]" 
                                value="{{ $fuelType->id }}"
                                class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                @if(isset($currentFilters['fuel_type_id']) && (is_array($currentFilters['fuel_type_id']) ? in_array($fuelType->id, $currentFilters['fuel_type_id']) : $currentFilters['fuel_type_id'] == $fuelType->id)) checked @endif
                            >
                            <span>{{ $fuelType->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Gear Type -->
            <div>
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Gear Type</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($filterOptions['gearTypes'] as $gearType)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['gear_type_id']) && (is_array($currentFilters['gear_type_id']) ? in_array($gearType->id, $currentFilters['gear_type_id']) : $currentFilters['gear_type_id'] == $gearType->id)) bg-accent border-foreground @endif">
                            <input 
                                type="checkbox" 
                                name="gear_type_id[]" 
                                value="{{ $gearType->id }}"
                                class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                @if(isset($currentFilters['gear_type_id']) && (is_array($currentFilters['gear_type_id']) ? in_array($gearType->id, $currentFilters['gear_type_id']) : $currentFilters['gear_type_id'] == $gearType->id)) checked @endif
                            >
                            <span>{{ $gearType->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Drive Wheels -->
            <div>
                <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Drive Wheels</label>
                <div class="flex flex-wrap gap-2">
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('fwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'fwd')) bg-accent border-foreground @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="fwd"
                            class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('fwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'fwd')) checked @endif
                        >
                        <span>FWD</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('rwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'rwd')) bg-accent border-foreground @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="rwd"
                            class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('rwd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'rwd')) checked @endif
                        >
                        <span>RWD</span>
                    </label>
                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['drive_axles']) && (is_array($currentFilters['drive_axles']) ? in_array('awd', $currentFilters['drive_axles']) : $currentFilters['drive_axles'] == 'awd')) bg-accent border-foreground @endif">
                        <input 
                            type="checkbox" 
                            name="drive_axles[]" 
                            value="awd"
                            class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">First Registration Year</label>
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
                            <div id="first-reg-year-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                            <div id="first-reg-year-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="first-reg-year-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Seller Type & Sales Type -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Seller Type -->
                    <div>
                        <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Seller Type</label>
                        <div class="flex flex-col gap-2">
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('dealer', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'dealer')) bg-accent border-foreground @endif">
                                <input 
                                    type="checkbox" 
                                    name="seller_type[]" 
                                    value="dealer"
                                    class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('dealer', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'dealer')) checked @endif
                                >
                                <span>Dealer</span>
                            </label>
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('private', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'private')) bg-accent border-foreground @endif">
                                <input 
                                    type="checkbox" 
                                    name="seller_type[]" 
                                    value="private"
                                    class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @if(isset($currentFilters['seller_type']) && (is_array($currentFilters['seller_type']) ? in_array('private', $currentFilters['seller_type']) : $currentFilters['seller_type'] == 'private')) checked @endif
                                >
                                <span>Private</span>
                            </label>
                        </div>
                    </div>

                    <!-- Sales Type -->
                    <div>
                        <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Sales Type</label>
                        <div class="flex flex-col gap-2">
                            @foreach($filterOptions['salesTypes'] as $salesType)
                                <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['sales_type_id']) && (is_array($currentFilters['sales_type_id']) ? in_array($salesType->id, $currentFilters['sales_type_id']) : $currentFilters['sales_type_id'] == $salesType->id)) bg-accent border-foreground @endif">
                                    <input 
                                        type="checkbox" 
                                        name="sales_type_id[]" 
                                        value="{{ $salesType->id }}"
                                        class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Seller Distance (km)</label>
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
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Horsepower (HP)</label>
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
                            <div id="horsepower-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                                value="{{ $currentFilters['engine_power_to'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="horsepower-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="horsepower-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Battery & Charging (EV) -->
            <div class="space-y-5">
                <!-- Battery Capacity -->
                <div class="space-y-4">
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Battery Capacity (kWh)</label>
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
                            <div id="battery-capacity-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                                value="{{ $currentFilters['battery_capacity_to'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="battery-capacity-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="battery-capacity-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Range (km) -->
                <div class="space-y-4">
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">Range (km)</label>
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
                            <div id="range-km-range-track" class="absolute h-2 bg-gray-600 rounded-full"></div>
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
                                value="{{ $currentFilters['range_km_to'] ?? 0 }}"
                                class="absolute w-full h-2 opacity-0 cursor-pointer z-20"
                            >
                            <div id="range-km-handle-min" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                            <div id="range-km-handle-max" class="absolute w-5 h-5 bg-gray-600 rounded-full border-2 border-background shadow-lg -top-1.5 cursor-grab active:cursor-grabbing z-30 transition-transform hover:scale-110"></div>
                        </div>
                    </div>
                </div>

                <!-- Charging Type -->
                <div>
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Charging Type</label>
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
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Euro Norm</label>
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
                        <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Doors (Min)</label>
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
                        <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Seats (Min)</label>
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
                    <label class="text-xs font-semibold tracking-wide text-muted-foreground uppercase mb-3 block">Equipment</label>
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
                                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input @if(isset($currentFilters['equipment_ids']) && (is_array($currentFilters['equipment_ids']) ? in_array($equipment->id, $currentFilters['equipment_ids']) : $currentFilters['equipment_ids'] == $equipment->id)) bg-accent border-foreground @endif">
                                            <input 
                                                type="checkbox" 
                                                name="equipment_ids[]" 
                                                value="{{ $equipment->id }}"
                                                class="h-4 w-4 rounded border-input text-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
        </aside>

        <!-- Sort, view toggle and results -->
        <div class="flex-1 flex flex-col gap-4">
            <!-- Results count, filter chips, and reset button -->
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Applied Filters Chips -->
                        <div id="applied-filters-container" class="flex flex-wrap gap-2">
                            <!-- Filter chips will be rendered here via JavaScript -->
                    <!-- Reset Button (only visible when filters are applied) -->
                    <button
                        id="filter-reset-button-main"
                        type="button"
                                class="hidden inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1.5 text-xs text-foreground transition-colors hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        Reset Filters
                    </button>
                        </div>
                    </div>
            </div>
        </div>

            <div id="sort-and-condition-controls" class="flex items-center justify-between gap-4">
            <div class="text-xs flex items-center gap-2">
                <p id="results-count" class="text-xs text-foreground">
                    <strong>{{ number_format($vehicles->total()) }}</strong> 
                    results
                </p>
                <span>|</span>
                <!-- Sort Dropdown Container -->
                <div class="relative text-xs font-medium">
            <button
                type="button"
                id="sort-button"
                        class="inline-flex h-8 items-center justify-center"
                    >
                        <span class="mr-2">Sort by</span>
                        <strong>
                <span id="sort-button-text">
                    @php
                        $sortLabels = [
                            'best_match' => 'Best Match',
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
                        $currentSort = request()->query('sort', 'best_match');
                    @endphp
                    {{ $sortLabels[$currentSort] ?? 'Best Match' }}
                </span>
                        </strong>
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
                    <button type="button" class="sort-option w-full px-3 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground transition-colors @if(request()->query('sort') == 'best_match' || !request()->has('sort')) bg-accent @endif" data-sort="best_match">
                        Best Match
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
            </div>
            
            <!-- Sort and View Toggle -->
            <div class="flex items-center gap-2">
            <!-- Sort Dropdown -->
            <div class="relative text-xs font-medium">
                
                
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
    </div>
    
    <!-- Vehicle Grid/List -->
    <div id="vehicle-container" class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3" data-view="card">
        @forelse($vehicles as $vehicle)
        <div class="flex flex-col rounded-2xl bg-card overflow-hidden p-0 cursor-pointer h-full">
            <a href="/vehicles/{{ $vehicle->id }}" class="block flex-1">
                <!-- Vehicle Image -->
                <div class="relative aspect-[2/1.5] overflow-hidden p-3 pb-0">
                    <img
                        src="{{ $vehicle->images->first()?->thumbnail_url ?? '/placeholder-vehicle.jpg' }}"
                        alt="{{ $vehicle->brand_name }} {{ $vehicle->model_name }}"
                        class="h-full w-full object-cover rounded-md"
                    />
                    @if($vehicle->dealer_id)
                    <!-- Dealer Label - Top Left -->
                    <span class="absolute top-4 left-4 z-10 inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                        Dealer
                </span>
                    @else
                    <!-- Private Label - Top Left -->
                    <span class="absolute top-4 left-4 z-10 inline-flex items-center rounded-md bg-orange-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                        Private
                    </span>
                    @endif
                    <!-- Heart Icon - Top Right -->
                    <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle->id }}, event); return false;" aria-label="Add to favorites">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 {{ $vehicle->dealer_id ? 'text-blue-600' : 'text-orange-600' }} hover:text-red-500 transition-colors heart-icon" data-vehicle-id="{{ $vehicle->id }}" data-dealer-id="{{ $vehicle->dealer_id ?? '' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
            </button>
        </div>
                
                <!-- Vehicle Details -->
                <div class="p-3 space-y-1">
                    <div class="flex flex-col gap-1">
                        <h3 class="flex items-center gap-2 text-xs">
                            {{ $vehicle->title }}
                        </h3>
                        @if($vehicle->version)
                        <p class="text-muted-foreground text-xs font-normal">
                            {{ $vehicle->version }}
                        </p>
                        @endif
                        <p class="text-lg font-bold">
                            {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                        </p>
    </div>

                    <div class="flex flex-wrap gap-1 text-xs font-light">
                        @if($vehicle->mileage || $vehicle->km_driven)
                        <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->mileage ?? $vehicle->km_driven ?? 0) }} km</span>
                        @endif
                        @if($vehicle->engine_power_hp)
                        <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->engine_power_hp, 0) }} HP</span>
                        @endif
                        @if($vehicle->first_registration_date)
                        <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ \Carbon\Carbon::parse($vehicle->first_registration_date)->format('M Y') }}</span>
                        @endif
                        @if($vehicle->fuel_type_name)
                        <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->fuel_type_name }}</span>
                        @endif
                        @if($vehicle->gear_type_name)
                        <span class="inline-flex items-center rounded-lg border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->gear_type_name }}</span>
                        @endif
</div>

                </div>
            </a>
            
            <!-- Card Footer -->
            <div class="mt-auto" onclick="event.stopPropagation()">
                @if($vehicle->seller_address || $vehicle->seller_postcode)
                <div class="px-3 pt-3 pb-2">
                    <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span class="truncate text-right">
                            @if($vehicle->seller_address){{ $vehicle->seller_address }}@endif
                            @if($vehicle->seller_address && $vehicle->seller_postcode), @endif
                            @if($vehicle->seller_postcode){{ $vehicle->seller_postcode }}@endif
                        </span>
                    </div>
                </div>
                @endif
                <!-- Vehicle Actions -->
                <div class="p-3 pt-0">
                    <div class="flex w-full flex-col gap-2 sm:flex-row">
                        <a href="/vehicles/{{ $vehicle->id }}" class="flex-1" onclick="event.stopPropagation()">
                            <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                View Details
                            </button>
                        </a>
                        <button 
                            type="button"
                            onclick="event.stopPropagation(); openEnquiryDialog('enquiry', {{ $vehicle->id }})"
                            class="flex-1 inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                        >
                            Enquire
                        </button>
                    </div>
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

    <!-- Enquiry Dialogs for Vehicles -->
    @foreach($vehicles as $vehicle)
        <x-enquiry-dialog type="enquiry" :vehicle="$vehicle" />
    @endforeach

    <!-- Login Dialog -->
    <x-login-dialog />

    <!-- Pagination -->
    <div id="pagination-container" class="mt-8 flex items-center justify-center gap-2">
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


@push('styles')
<style>
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
        gap: 1rem;
        position: relative;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper h3 {
        font-size: 1.125rem;
        font-weight: 700;
        line-height: 1.3;
        margin: 0;
        color: hsl(var(--foreground));
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper .text-muted-foreground {
        font-size: 0.75rem;
        color: hsl(var(--muted-foreground));
        margin-top: -0.375rem;
        font-weight: 400;
    }
    
    #vehicle-container[data-view="list"] .vehicle-content-wrapper .text-primary {
        font-size: 1.125rem;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
        color: hsl(var(--primary));
    }
    
    #vehicle-container[data-view="list"] .vehicle-item .mt-auto {
        margin-top: auto;
        padding: 1rem;
        padding-top: 0.5rem;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    #vehicle-container[data-view="list"] .vehicle-actions-section button,
    #vehicle-container[data-view="list"] .vehicle-actions-section a button {
        height: 2.25rem;
        padding: 0 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        
        #vehicle-container[data-view="list"] .vehicle-item .mt-auto {
            padding: 1rem;
            padding-top: 0.5rem;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section {
            flex-direction: column;
            width: 100%;
        }
        
        #vehicle-container[data-view="list"] .vehicle-actions-section button,
        #vehicle-container[data-view="list"] .vehicle-actions-section a button {
            width: 100%;
        }
    }
    
    /* Condition filter pill-style tabs */
    .condition-radio-label {
        border: none !important;
    }
    
    .condition-radio-label.bg-white {
        background-color: rgb(255 255 255) !important;
        color: hsl(var(--foreground)) !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
    .condition-radio-label.bg-gray-150 {
        background-color: rgb(236 237 240) !important;
        color: hsl(var(--muted-foreground)) !important;
    }
    
    .condition-radio-label.bg-gray-150:hover {
        color: hsl(var(--foreground)) !important;
        }
    
    /* Listing Type filter pill-style tabs */
    .listing-type-checkbox-label {
        border: none !important;
    }
    
    .listing-type-checkbox-label.bg-white {
        background-color: rgb(255 255 255) !important;
        color: hsl(var(--foreground)) !important;
        font-weight: 600 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
    .listing-type-checkbox-label.bg-gray-150 {
        background-color: rgb(236 237 240) !important;
        color: hsl(var(--muted-foreground)) !important;
    }
    
    .listing-type-checkbox-label.bg-gray-150:hover {
        color: hsl(var(--foreground)) !important;
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
    
    /* Condition filter responsive styles */
    @media (max-width: 768px) {
        #sort-and-condition-controls {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        #sort-and-condition-controls > div:first-child {
            width: 100%;
        }
        
        #sort-and-condition-controls > div:first-child > div {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }
        
        #sort-and-condition-controls > div:first-child > label {
            margin-bottom: 0.5rem;
        }
    }
    
    @media (max-width: 640px) {
        #sort-and-condition-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        #sort-and-condition-controls > div:last-child {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function() {
        // Constants
        const vehicleContainer = document.getElementById('vehicle-container');
        const vehicleGrid = vehicleContainer; // Keep for backward compatibility
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const sortButton = document.getElementById('sort-button');
        const sortDropdown = document.getElementById('sort-dropdown');
        const sortButtonText = document.getElementById('sort-button-text');
        const sortOptions = document.querySelectorAll('.sort-option');
        const filterSidebar = document.getElementById('filter-sidebar');
        const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
        const filterResetButtonMain = document.getElementById('filter-reset-button-main');
        const viewToggleRadios = document.querySelectorAll('input[name="view-toggle"]');
        
        // View state
        let currentView = localStorage.getItem('vehicleView') || 'card';
        
        // Check if device is mobile (screen width <= 640px)
        function isMobile() {
            return window.innerWidth <= 640;
        }
        
        // Force card view on mobile
        if (isMobile()) {
            currentView = 'card';
            localStorage.setItem('vehicleView', 'card');
        }
        
        const sortLabels = {
            'best_match': 'Best Match',
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
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(amount) + ' kr.';
        }
        
        // Render single vehicle card
        function renderVehicleCard(vehicle) {
            const imageUrl = vehicle.thumbnail_url || vehicle.image_url || '/placeholder-vehicle.jpg';
            
            // Build location string from seller_address and seller_postcode
            let locationText = '';
            if (vehicle.seller_address || vehicle.seller_postcode) {
                const parts = [];
                if (vehicle.seller_address) parts.push(vehicle.seller_address);
                if (vehicle.seller_postcode) parts.push(vehicle.seller_postcode);
                locationText = parts.join(', ');
            }
            
            return `
                <div class="flex flex-col rounded-2xl bg-card overflow-hidden p-0 cursor-pointer h-full">
                    <a href="/vehicles/${vehicle.id}" class="block flex-1">
                        <!-- Vehicle Image -->
                        <div class="relative aspect-[2/1.5] overflow-hidden p-3 pb-0">
                            <img
                                src="${imageUrl}"
                                alt="${vehicle.title || ''}"
                                class="h-full w-full object-cover rounded-md"
                            />
                            ${vehicle.dealer_id ? `
                            <!-- Dealer Label - Top Left -->
                            <span class="absolute top-4 left-4 z-10 inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                Dealer
                            </span>
                            ` : `
                            <!-- Private Label - Top Left -->
                            <span class="absolute top-4 left-4 z-10 inline-flex items-center rounded-md bg-orange-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                Private
                            </span>
                            `}
                            <!-- Heart Icon - Top Right -->
                            <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(${vehicle.id}, event); return false;" aria-label="Add to favorites">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 ${vehicle.dealer_id ? 'text-blue-600' : 'text-orange-600'} hover:text-red-500 transition-colors heart-icon" data-vehicle-id="${vehicle.id}" data-dealer-id="${vehicle.dealer_id || ''}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Vehicle Details -->
                        <div class="p-3 space-y-1">
                            <div class="flex flex-col gap-1">
                                <h3 class="flex items-center gap-2 text-xs">
                                    ${vehicle.title || ''}
                                </h3>
                                ${vehicle.version ? `
                                <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                                    ${vehicle.version}
                                </p>
                                ` : ''}
                                <p class="text-lg font-bold">
                                    ${formatCurrency(vehicle.price)}
                                </p>
                            </div>

                            <div class="-mt-2 flex flex-wrap gap-1 text-xs font-light">
                                ${vehicle.mileage || vehicle.km_driven ? `
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${new Intl.NumberFormat('da-DK').format(vehicle.mileage || vehicle.km_driven || 0)} km</span>
                                ` : ''}
                                ${vehicle.engine_power_hp ? `
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${Math.round(vehicle.engine_power_hp)} HP</span>
                                ` : ''}
                                ${vehicle.first_registration_date ? `
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${new Date(vehicle.first_registration_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</span>
                                ` : ''}
                                ${vehicle.fuel_type_name ? `
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${vehicle.fuel_type_name}</span>
                                ` : ''}
                                ${vehicle.gear_type_name ? `
                                <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${vehicle.gear_type_name}</span>
                                ` : ''}
                            </div>

                        </div>
                    </a>
                    
                    <!-- Card Footer -->
                    <div class="mt-auto" onclick="event.stopPropagation()">
                        ${locationText ? `
                        <div class="px-3 pt-3 pb-2">
                            <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="truncate text-right">${locationText}</span>
                            </div>
                        </div>
                        ` : ''}
                        <!-- Vehicle Actions -->
                        <div class="p-3 pt-0">
                            <div class="flex w-full flex-col gap-2 sm:flex-row">
                                <a href="/vehicles/${vehicle.id}" class="flex-1" onclick="event.stopPropagation()">
                                    <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                        View Details
                                    </button>
                                </a>
                                <button class="inline-flex h-9 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border" onclick="event.stopPropagation(); handleEnquire(${vehicle.id}, event);">
                                    Enquire
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Render single vehicle list item - Compact design matching card view styles
        function renderVehicleListItem(vehicle) {
            const details = vehicle.details || {};
            const imageUrl = vehicle.image_url || '/placeholder-vehicle.jpg';
            
            // Build location string from seller_address and seller_postcode
            let locationText = '';
            if (vehicle.seller_address || vehicle.seller_postcode) {
                const parts = [];
                if (vehicle.seller_address) parts.push(vehicle.seller_address);
                if (vehicle.seller_postcode) parts.push(vehicle.seller_postcode);
                locationText = parts.join(', ');
            }
            
            // Build badges (same as card view)
            const badges = [];
            if (vehicle.mileage || vehicle.km_driven) {
                badges.push(`${new Intl.NumberFormat('da-DK').format(vehicle.mileage || vehicle.km_driven || 0)} km`);
            }
            if (vehicle.engine_power_hp) {
                badges.push(`${Math.round(vehicle.engine_power_hp)} HP`);
            }
            if (vehicle.first_registration_date) {
                const regDate = new Date(vehicle.first_registration_date);
                badges.push(regDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
            }
            if (vehicle.fuel_type_name) {
                badges.push(vehicle.fuel_type_name);
            }
            if (vehicle.gear_type_name) {
                badges.push(vehicle.gear_type_name);
            }
            
            return `
                <div class="vehicle-item relative bg-white rounded-lg">

                    <a href="/vehicles/${vehicle.id}" class="block flex-1">
                        <!-- Vehicle Image -->
                        <div class="vehicle-image-container relative">
                            <img
                                src="${imageUrl}"
                                alt="${vehicle.title || ''}"
                                class="h-full w-full object-cover"
                            />
                            
                            <span>
                            ${vehicle.dealer_id ? `
                                <!-- Dealer Label - Top Left of List Item -->
                                <span class="vehicle-dealer-label absolute top-2 left-2 z-20 inline-flex items-center rounded-md bg-blue-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                    Dealer
                                </span>
                                ` : `
                                <!-- Private Label - Top Left of List Item -->
                                <span class="vehicle-dealer-label absolute top-2 left-2 z-20 inline-flex items-center rounded-md bg-orange-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                    Private
                                </span>
                                `}
                            </span>

                            <!-- Heart Icon - Top Right -->
                            <button type="button" class="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(${vehicle.id}, event); return false;" aria-label="Add to favorites">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 ${vehicle.dealer_id ? 'text-blue-600' : 'text-orange-600'} hover:text-red-500 transition-colors heart-icon" data-vehicle-id="${vehicle.id}" data-dealer-id="${vehicle.dealer_id || ''}">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Vehicle Content -->
                        <div class="vehicle-content-wrapper">
                            <!-- Header Section -->
                            <div class="flex flex-col gap-4">
                                <span class="flex items-center gap-2 text-sm font-semibold">
                                    ${vehicle.title || ''}
                                </span>
                                
                                    ${badges.length > 0 ? `
                            <div class="-mt-2 flex flex-wrap gap-1 text-xs font-light">
                                ${badges.map(badge => `<span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">${badge}</span>`).join('')}
                            </div>
                            ` : ''}

                                <p class="text-sm font-semibold">
                                    Price: ${formatCurrency(vehicle.price)}
                                </p>

                                <span>
                                    ${vehicle.version ? `<p class="text-muted-foreground -mt-1.5 text-xs font-normal"><span>Version:</span> <strong>${vehicle.version}</strong></p>` : ''}
                                </span>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Card Footer -->
                    <div class="mt-auto" onclick="event.stopPropagation()">
                        ${locationText ? `
                        <div class="px-4 pt-3 pb-2">
                            <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 flex-shrink-0">
                                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span class="truncate text-right">${locationText}</span>
                            </div>
                        </div>
                        ` : ''}
                        <!-- Vehicle Actions -->
                        <div class="vehicle-actions-section">
                            <div class="flex w-full flex-col gap-2 sm:flex-row">
                                <a href="/vehicles/${vehicle.id}" class="flex-1" onclick="event.stopPropagation()">
                                    <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                        View Details
                            </button>
                        </a>
                                <button 
                                    type="button"
                                    onclick="event.stopPropagation(); openEnquiryDialog('enquiry', ${vehicle.id})"
                                    class="flex-1 inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border"
                                >
                                Enquire
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Render vehicle grid or list
        function renderVehicleGrid(vehicles) {
            if (!vehicleContainer) return;
            
            if (vehicles.length === 0) {
                vehicleContainer.innerHTML = `
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
            
            if (currentView === 'list') {
                vehicleContainer.innerHTML = vehicles.map(vehicle => renderVehicleListItem(vehicle)).join('');
            } else {
                vehicleContainer.innerHTML = vehicles.map(vehicle => renderVehicleCard(vehicle)).join('');
            }
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
        
        // Render pagination
        function renderPagination(pagination) {
            console.log('renderPagination called with:', pagination);
            
            // Find pagination container by ID (more reliable)
            let paginationContainer = document.getElementById('pagination-container');
            
            // If container doesn't exist, create it after the vehicle container
            if (!paginationContainer && vehicleContainer) {
                paginationContainer = document.createElement('div');
                paginationContainer.id = 'pagination-container';
                paginationContainer.className = 'mt-8 flex items-center justify-center gap-2';
                vehicleContainer.parentNode.insertBefore(paginationContainer, vehicleContainer.nextSibling);
                console.log('Created pagination container');
            }
            
            if (!paginationContainer) {
                console.warn('Pagination container not found and could not be created');
                return;
            }
            
            // Handle missing or invalid pagination data
            if (!pagination || typeof pagination !== 'object') {
                console.warn('Invalid pagination data:', pagination);
                paginationContainer.innerHTML = '';
                return;
            }
            
            const { current_page, last_page, total } = pagination;
            console.log('Pagination values:', { current_page, last_page, total });
            
            // Convert to numbers and validate pagination values
            const currentPageNum = parseInt(current_page);
            const lastPageNum = parseInt(last_page);
            
            if (isNaN(currentPageNum) || isNaN(lastPageNum) || currentPageNum < 1 || lastPageNum < 1) {
                console.warn('Invalid pagination values:', { current_page, last_page, currentPageNum, lastPageNum });
                paginationContainer.innerHTML = '';
                return;
            }
            
            // Only clear if there's truly only 1 page
            if (lastPageNum <= 1) {
                console.log('Only 1 page, clearing pagination');
                paginationContainer.innerHTML = '';
                return;
            }
            
            console.log('Rendering pagination HTML');
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <button 
                    ${currentPageNum === 1 ? 'disabled' : ''}
                    data-page="${currentPageNum - 1}"
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
            let startPage = Math.max(1, currentPageNum - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(lastPageNum, startPage + maxPagesToShow - 1);
            
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
                        class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring ${i === currentPageNum ? 'bg-accent' : ''}"
                    >
                        ${i}
                    </button>
                `;
            }
            
            if (endPage < lastPageNum) {
                if (endPage < lastPageNum - 1) {
                    paginationHTML += `<span class="px-2 text-muted-foreground">...</span>`;
                }
                paginationHTML += `
                    <button data-page="${lastPageNum}" class="pagination-btn inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        ${lastPageNum}
                    </button>
                `;
            }
            
            // Next button
            paginationHTML += `
                <button 
                    ${currentPageNum === lastPageNum ? 'disabled' : ''}
                    data-page="${currentPageNum + 1}"
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
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12 h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
                    label: `"${filters.search}"`,
                    value: filters.search
                });
            }
            
            // Listing Type (checkboxes - can have multiple)
            if (filters.listing_type_id && Array.isArray(filters.listing_type_id)) {
                filters.listing_type_id.forEach(id => {
                    const name = getLabelText('listing_type_id[]', id);
                    if (name) {
                    chips.push({
                        key: 'listing_type_id',
                            label: name,
                            value: id,
                            isArray: true
                    });
                }
                });
            }
            
            // Brand
            if (filters.brand_id) {
                const brandName = getOptionText('brand_id', filters.brand_id);
                if (brandName) {
                    chips.push({
                        key: 'brand_id',
                        label: brandName,
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
                        label: modelName,
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
                        label: categoryName,
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
                        label: `${from} - ${to} kr.`,
                        value: { from: filters.price_from, to: filters.price_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'price_from',
                        label: `From ${from} kr.`,
                        value: filters.price_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'price_to',
                        label: `Up to ${to} kr.`,
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
                        label: `${from} - ${to}`,
                        value: { from: filters.year_from, to: filters.year_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'year_from',
                        label: `From ${from}`,
                        value: filters.year_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'year_to',
                        label: `Up to ${to}`,
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
                        label: `${from} - ${to} km`,
                        value: { from: filters.mileage_from, to: filters.mileage_to }
                    });
                } else if (from) {
                    chips.push({
                        key: 'mileage_from',
                        label: `From ${from} km`,
                        value: filters.mileage_from
                    });
                } else if (to) {
                    chips.push({
                        key: 'mileage_to',
                        label: `Up to ${to} km`,
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
                        label: conditionName,
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
            
            // Render filter chips HTML
            const chipsHTML = chips.map(chip => `
                <div class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1.5 text-xs text-foreground">
                    <span>${chip.label}</span>
                    <button 
                        type="button"
                        class="filter-chip-remove ml-0.5 rounded-full hover:bg-muted-foreground/20 transition-colors border border-foreground"
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
            
            // Set container HTML with chips and reset button
            container.innerHTML = chipsHTML + `
                <button
                    id="filter-reset-button-main"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1.5 text-xs text-foreground transition-colors hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    Reset Filters
                </button>
            `;
            
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
                        // Uncheck the specific listing type checkbox
                        const checkbox = document.querySelector(`[name="listing_type_id[]"][value="${value}"]`);
                        if (checkbox) {
                            checkbox.checked = false;
                            if (typeof updateListingTypeStyles === 'function') {
                                updateListingTypeStyles();
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
        
        // Update reset button visibility based on active filters
        function updateResetButtonVisibility() {
            const resetButton = document.getElementById('filter-reset-button-main');
            if (!resetButton) return;
            
            const filters = collectFilters();
            const hasFilters = Object.keys(filters).some(key => {
                if (key === 'sort') return false; // Don't count sort as a filter
                const value = filters[key];
                if (Array.isArray(value)) return value.length > 0;
                return value !== null && value !== '' && value !== undefined;
            });
            
            if (hasFilters) {
                resetButton.classList.remove('hidden');
            } else {
                resetButton.classList.add('hidden');
            }
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
                renderVehicleGrid(data.vehicles || []);
                
                // Check favorites for rendered vehicles
                await checkFavoritesBatch();
                
                // Always try to render pagination if it exists - let renderPagination handle validation
                if (data.pagination) {
                    console.log('Rendering pagination:', data.pagination);
                    renderPagination(data.pagination);
                } else {
                    // If pagination is completely missing, log a warning but don't clear existing
                    console.warn('Pagination data missing from API response', data);
                }
                
                // Update results count
                const resultsCount = document.getElementById('results-count');
                const totalCount = data.pagination?.total || 0;
                const formattedCount = new Intl.NumberFormat('en-US').format(totalCount);
                
                if (resultsCount) {
                    resultsCount.textContent = `${formattedCount} results`;
                }
                
                // Update filter chips and reset button visibility
                renderFilterChips();
                updateResetButtonVisibility();
                
                // Update sort button text if sort changed
                if (params.sort !== undefined) {
                    const sortValue = params.sort || 'best_match';
                    if (sortButtonText) {
                        sortButtonText.textContent = sortLabels[sortValue] || 'Best Match';
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
                    const sortLabel = sortLabels[sortValue] || 'Best Match';
                    
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
                    fetchVehicles({ sort: sortValue === 'best_match' ? null : sortValue, page: 1 });
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
        
        // Mobile sidebar toggle
        if (mobileFilterToggle && filterSidebar) {
            const filterIcon = document.getElementById('mobile-filter-icon');
            const closeIcon = document.getElementById('mobile-close-icon');
            
            function updateToggleIcon() {
                const isOpen = !filterSidebar.classList.contains('hidden');
                if (filterIcon && closeIcon) {
                    if (isOpen) {
                        filterIcon.classList.add('hidden');
                        closeIcon.classList.remove('hidden');
                        mobileFilterToggle.setAttribute('aria-label', 'Close filters');
                    } else {
                        filterIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                        mobileFilterToggle.setAttribute('aria-label', 'Open filters');
                    }
                }
            }
            
            mobileFilterToggle.addEventListener('click', () => {
                filterSidebar.classList.toggle('hidden');
                updateToggleIcon();
            });
            
            // Update icon on initial load
            updateToggleIcon();
        }

        // Listing Type functionality for Purchase/Leasing (checkboxes)
        const listingTypeCheckboxes = document.querySelectorAll('input[name="listing_type_id[]"]');
        
        function updateListingTypeStyles() {
            listingTypeCheckboxes.forEach(checkbox => {
                const label = checkbox.closest('.listing-type-checkbox-label');
                if (label) {
                    if (checkbox.checked) {
                        label.classList.add('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                        label.classList.remove('bg-gray-150', 'text-muted-foreground');
                    } else {
                        label.classList.remove('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                        label.classList.add('bg-gray-150', 'text-muted-foreground');
                    }
                }
            });
        }
        
        listingTypeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                // Update styles
                updateListingTypeStyles();
                // Auto-apply filters
                autoApplyFilters();
            });
        });

        // Initialize styles on page load
        updateListingTypeStyles();

        // Brand-Model dependency: Filter models based on selected brand
        const brandSelect = document.getElementById('brand-select');
        const modelSelect = document.getElementById('model-select');
        
        function updateModelOptions() {
            if (!brandSelect || !modelSelect) return;
            
            const selectedBrandId = brandSelect.value;
            const modelOptions = modelSelect.querySelectorAll('option[data-brand-id]');
            const defaultOption = modelSelect.querySelector('option[value=""]');
            
            if (!selectedBrandId || selectedBrandId === '') {
                // No brand selected - disable model dropdown
                modelSelect.disabled = true;
                if (defaultOption) {
                    defaultOption.textContent = 'Model';
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
                    defaultOption.textContent = 'All';
                }
                
                // Show/hide model options based on brand
                modelOptions.forEach(option => {
                    const optionBrandId = option.getAttribute('data-brand-id');
                    if (optionBrandId === selectedBrandId) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Reset model selection if current model is not available for selected brand
                if (modelSelect.value) {
                    const selectedModelOption = modelSelect.options[modelSelect.selectedIndex];
                    if (selectedModelOption && selectedModelOption.getAttribute('data-brand-id') !== selectedBrandId) {
                        modelSelect.value = '';
                    }
                }
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
                        label.classList.add('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                        label.classList.remove('bg-gray-150', 'text-muted-foreground');
                    } else {
                        label.classList.remove('bg-white', 'text-foreground', 'font-semibold', 'shadow-sm');
                        label.classList.add('bg-gray-150', 'text-muted-foreground');
                    }
                }
            });
        }
        
        // Set up condition radio listeners - apply filter when changed
        document.querySelectorAll('input[name="condition_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                updateConditionStyles();
                // Apply filter immediately
                const filters = collectFilters();
                fetchVehicles({ ...filters, page: 1 });
            });
        });
        
        // Initialize condition styles
        updateConditionStyles();
        
        // Equipment collapsible functionality
        function setupEquipmentCollapsible() {
            if (!filterSidebar) return;
            
            const equipmentToggles = filterSidebar.querySelectorAll('.equipment-type-toggle');
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
                
                // Update input fields - clear if at default values (0 for min, max for max)
                if (finalMin === min || finalMin === 0) {
                    minInput.value = '';
                } else {
                    minInput.value = finalMin;
                }
                
                if (finalMax === max) {
                    maxInput.value = '';
                } else {
                    maxInput.value = finalMax;
                }
                
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
                if (!isNaN(value) && value > 0) {
                    const clampedValue = Math.max(min, Math.min(max, value));
                    slider.value = clampedValue;
                    input.value = clampedValue;
                    updateSlider();
                } else if (input.value === '' || input.value === '0') {
                    // Clear slider to default: min for min slider, max for max slider
                    slider.value = (slider === minSlider) ? min : max;
                    input.value = '';
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

        // Reset filters function
        function resetAllFilters() {
            // Reset search input
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Reset all inputs in filter sidebar
            const inputs = filterSidebar.querySelectorAll('input, select');
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
                            if (config.minInput) config.minInput.value = '';
                        } else {
                            input.value = config.max;
                            if (config.maxInput) config.maxInput.value = '';
                        }
                    }
                } else if (input.tagName === 'SELECT' && input.multiple) {
                    // Reset multi-select dropdowns
                    Array.from(input.options).forEach(option => {
                        option.selected = false;
                    });
                } else if (input.tagName === 'SELECT') {
                    // Reset single select dropdowns to first option (usually "All")
                    if (input.options.length > 0) {
                        input.selectedIndex = 0;
                    }
                } else {
                    input.value = '';
                }
            });
            
            // Reset listing type checkboxes (uncheck all)
            const listingTypeCheckboxes = document.querySelectorAll('input[name="listing_type_id[]"]');
            listingTypeCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Reset condition to "All" (empty value)
            const conditionAllRadio = document.querySelector('input[name="condition_id"][value=""]');
            if (conditionAllRadio) {
                conditionAllRadio.checked = true;
            }
            
            // Update listing type styles
            if (typeof updateListingTypeStyles === 'function') {
                updateListingTypeStyles();
            }
            
            // Update condition styles
            if (typeof updateConditionStyles === 'function') {
                updateConditionStyles();
            }
            
            // Reset model options
            if (typeof updateModelOptions === 'function') {
            updateModelOptions();
            }
            
            // Reinitialize sliders after reset
            setTimeout(() => {
                sliderConfigs.forEach(config => initRangeSlider(config));
            }, 50);
            
            // Clear URL parameters and fetch vehicles with empty filters
            const cleanUrl = window.location.pathname;
            window.history.pushState({}, '', cleanUrl);
            
            // Apply the reset by fetching vehicles with no filters (only page 1)
            // This will clear all filter parameters from the URL
            fetchVehicles({ page: 1 });
            
            // Update filter chips (should be empty now) - delay slightly to ensure filters are cleared
            setTimeout(() => {
                renderFilterChips();
                updateResetButtonVisibility();
            }, 100);
        }
        
        // Attach reset handler to initial button
        if (filterResetButtonMain) {
            filterResetButtonMain.addEventListener('click', resetAllFilters);
        }
        
        // Also use event delegation on container for dynamically created buttons
        const appliedFiltersContainer = document.getElementById('applied-filters-container');
        if (appliedFiltersContainer) {
            appliedFiltersContainer.addEventListener('click', (e) => {
                const resetButton = e.target.closest('#filter-reset-button-main');
                if (resetButton) {
                    e.preventDefault();
                    resetAllFilters();
                }
        });
        }

        // Helper function to check if a value should be included in filters
        // Excludes empty strings, 0, and max/default values
        function shouldIncludeFilterValue(value, maxValue = null) {
            if (!value || value === '' || value === '0') return false;
            const numValue = parseFloat(value);
            if (isNaN(numValue)) return false;
            if (numValue === 0) return false;
            // Exclude max values (these are defaults and shouldn't be applied)
            if (maxValue !== null && numValue >= maxValue) return false;
            return true;
        }
        
        // Collect all filter values
        function collectFilters() {
            const filters = {};
            const currentYear = new Date().getFullYear(); // Declare once at the top
            
            // Preserve search and sort parameters from URL
            const urlParams = new URLSearchParams(window.location.search);
            const search = urlParams.get('search');
            if (search) filters.search = search;
            
            const sort = urlParams.get('sort');
            if (sort) filters.sort = sort;
            
            // Basic filters - Listing Type (checkboxes)
            const listingTypeIds = Array.from(document.querySelectorAll('[name="listing_type_id[]"]:checked')).map(cb => cb.value);
            if (listingTypeIds.length > 0) filters.listing_type_id = listingTypeIds;
            
            const categoryId = document.querySelector('[name="category_id"]')?.value;
            if (categoryId) filters.category_id = categoryId;
            
            const brandId = document.querySelector('[name="brand_id"]')?.value;
            if (brandId) filters.brand_id = brandId;
            
            const modelId = document.querySelector('[name="model_id"]')?.value;
            if (modelId) filters.model_id = modelId;
            
            // Price range (exclude 0 and max value 1000000)
            const priceFrom = document.querySelector('[name="price_from"]')?.value;
            if (shouldIncludeFilterValue(priceFrom)) filters.price_from = priceFrom;
            
            const priceTo = document.querySelector('[name="price_to"]')?.value;
            if (shouldIncludeFilterValue(priceTo, 1000000)) filters.price_to = priceTo;
            
            // Owner tax range (exclude 0 and max value 100000)
            const ownershipTaxFrom = document.querySelector('[name="ownership_tax_from"]')?.value;
            if (shouldIncludeFilterValue(ownershipTaxFrom)) filters.ownership_tax_from = ownershipTaxFrom;
            
            const ownershipTaxTo = document.querySelector('[name="ownership_tax_to"]')?.value;
            if (shouldIncludeFilterValue(ownershipTaxTo, 100000)) filters.ownership_tax_to = ownershipTaxTo;
            
            // Year range (exclude default min 1975 and max year)
            const yearFrom = document.querySelector('[name="year_from"]')?.value;
            const yearFromNum = parseFloat(yearFrom);
            if (yearFrom && !isNaN(yearFromNum) && yearFromNum > 1975) {
                filters.year_from = yearFrom;
            }
            
            const yearTo = document.querySelector('[name="year_to"]')?.value;
            const yearToNum = parseFloat(yearTo);
            if (yearTo && !isNaN(yearToNum) && yearToNum < (currentYear + 1)) {
                filters.year_to = yearTo;
            }
            
            // Mileage range (exclude 0 and max value 500000)
            const mileageFrom = document.querySelector('[name="mileage_from"]')?.value;
            if (shouldIncludeFilterValue(mileageFrom)) filters.mileage_from = mileageFrom;
            
            const mileageTo = document.querySelector('[name="mileage_to"]')?.value;
            if (shouldIncludeFilterValue(mileageTo, 500000)) filters.mileage_to = mileageTo;
            
            // Price Type (checkboxes)
            const priceTypeIds = Array.from(document.querySelectorAll('[name="price_type_id[]"]:checked')).map(cb => cb.value);
                if (priceTypeIds.length > 0) filters.price_type_id = priceTypeIds;
            
            const conditionId = document.querySelector('[name="condition_id"]:checked')?.value;
            if (conditionId && conditionId !== '') filters.condition_id = conditionId;
            
            const bodyTypeIds = Array.from(document.querySelectorAll('[name="body_type_id[]"]:checked')).map(cb => cb.value);
            if (bodyTypeIds.length > 0) filters.body_type_id = bodyTypeIds;
            
            const fuelTypeIds = Array.from(document.querySelectorAll('[name="fuel_type_id[]"]:checked')).map(cb => cb.value);
            if (fuelTypeIds.length > 0) filters.fuel_type_id = fuelTypeIds;
            
            const gearTypeIds = Array.from(document.querySelectorAll('[name="gear_type_id[]"]:checked')).map(cb => cb.value);
            if (gearTypeIds.length > 0) filters.gear_type_id = gearTypeIds;
            
            const driveAxles = Array.from(document.querySelectorAll('[name="drive_axles[]"]:checked')).map(cb => cb.value);
            if (driveAxles.length > 0) filters.drive_axles = driveAxles;
            
            // First registration year (exclude 0 and max year)
            const firstRegYearFrom = document.querySelector('[name="first_registration_year_from"]')?.value;
            if (shouldIncludeFilterValue(firstRegYearFrom, 1975)) filters.first_registration_year_from = firstRegYearFrom;
            
            const firstRegYearTo = document.querySelector('[name="first_registration_year_to"]')?.value;
            if (shouldIncludeFilterValue(firstRegYearTo, currentYear)) filters.first_registration_year_to = firstRegYearTo;
            
            // Seller type
            const sellerTypes = Array.from(document.querySelectorAll('[name="seller_type[]"]:checked')).map(cb => cb.value);
            if (sellerTypes.length > 0) filters.seller_type = sellerTypes;
            
            // Sales type
            const salesTypeIds = Array.from(document.querySelectorAll('[name="sales_type_id[]"]:checked')).map(cb => cb.value);
            if (salesTypeIds.length > 0) filters.sales_type_id = salesTypeIds;
            
            // Seller distance (exclude 0)
            const sellerDistance = document.querySelector('[name="seller_distance"]')?.value;
            if (shouldIncludeFilterValue(sellerDistance)) filters.seller_distance = sellerDistance;
            
            // Performance (exclude 0 and max value 1000)
            const enginePowerFrom = document.querySelector('[name="engine_power_from"]')?.value;
            if (shouldIncludeFilterValue(enginePowerFrom)) filters.engine_power_from = enginePowerFrom;
            
            const enginePowerTo = document.querySelector('[name="engine_power_to"]')?.value;
            if (shouldIncludeFilterValue(enginePowerTo, 1000)) filters.engine_power_to = enginePowerTo;
            
            // Battery & Charging (exclude 0 and max value 200)
            const batteryCapacityFrom = document.querySelector('[name="battery_capacity_from"]')?.value;
            if (shouldIncludeFilterValue(batteryCapacityFrom)) filters.battery_capacity_from = batteryCapacityFrom;
            
            const batteryCapacityTo = document.querySelector('[name="battery_capacity_to"]')?.value;
            if (shouldIncludeFilterValue(batteryCapacityTo, 200)) filters.battery_capacity_to = batteryCapacityTo;
            
            // Range km (exclude 0 and max value 1000)
            const rangeKmFrom = document.querySelector('[name="range_km_from"]')?.value;
            if (shouldIncludeFilterValue(rangeKmFrom)) filters.range_km_from = rangeKmFrom;
            
            const rangeKmTo = document.querySelector('[name="range_km_to"]')?.value;
            if (shouldIncludeFilterValue(rangeKmTo, 1000)) filters.range_km_to = rangeKmTo;
            
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
        
        // Set up auto-apply listeners for all filter inputs
        function setupAutoApplyFilters() {
            if (!filterSidebar) return;
            
            // Radio buttons (listing type, condition)
            filterSidebar.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.addEventListener('change', autoApplyFilters);
            });
            
            // Checkboxes (body type, fuel type, gear type, equipment, etc.)
            filterSidebar.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', autoApplyFilters);
            });
            
            // Select dropdowns
            filterSidebar.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', autoApplyFilters);
            });
            
            // Number inputs (with debounce)
            filterSidebar.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(filterDebounceTimer);
                    filterDebounceTimer = setTimeout(() => {
                        autoApplyFilters();
                    }, 800); // Longer debounce for number inputs
                });
            });
            
            // Range sliders (with debounce)
            filterSidebar.querySelectorAll('input[type="range"]').forEach(slider => {
                slider.addEventListener('input', () => {
                    clearTimeout(filterDebounceTimer);
                    filterDebounceTimer = setTimeout(() => {
                        autoApplyFilters();
                    }, 500);
                });
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
            localStorage.setItem('vehicleView', view);
            
            // Update radio button selection
            viewToggleRadios.forEach(radio => {
                radio.checked = radio.value === view;
            });
            
            // Update container data attribute and classes
            vehicleContainer.setAttribute('data-view', view);
            if (view === 'list') {
                vehicleContainer.classList.remove('grid', 'grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-3');
                vehicleContainer.classList.add('flex', 'flex-col');
            } else {
                vehicleContainer.classList.add('grid', 'grid-cols-1', 'sm:grid-cols-2', 'md:grid-cols-3', 'lg:grid-cols-3');
                vehicleContainer.classList.remove('flex', 'flex-col');
            }
            
            // Update view toggle button styles
            updateViewToggleStyles();
        }
        
        // Convert existing card elements to list view (for initial page load)
        function convertCardsToList() {
            if (!vehicleContainer || currentView !== 'list') return;
            
            const cards = vehicleContainer.querySelectorAll('.rounded-lg.border');
            cards.forEach(card => {
                // Check if already converted
                if (card.classList.contains('vehicle-item')) return;
                
                const imageDiv = card.querySelector('.relative.aspect-video');
                const detailsDiv = card.querySelector('.px-4.py-4');
                const actionsDiv = card.querySelector('.mt-auto.p-4');
                
                if (!imageDiv || !detailsDiv) return;
                
                // Extract data
                const img = imageDiv.querySelector('img');
                const title = detailsDiv.querySelector('h3')?.textContent || '';
                const version = detailsDiv.querySelector('.text-muted-foreground.-mt-1\\.5')?.textContent || '';
                const price = detailsDiv.querySelector('.text-primary.text-2xl')?.textContent || '';
                const badges = Array.from(detailsDiv.querySelectorAll('.rounded-md.border')).map(b => b.textContent);
                const specs = Array.from(detailsDiv.querySelectorAll('.grid.grid-cols-2 > div')).map(s => s.innerHTML);
                const viewLink = actionsDiv?.querySelector('a[href^="/vehicles/"]')?.getAttribute('href') || '';
                const vehicleId = viewLink.match(/\/vehicles\/(\d+)/)?.[1] || '';
                
                // Create list item structure - Fixed height compact design
                const listItem = document.createElement('div');
                listItem.className = 'vehicle-item';
                listItem.innerHTML = `
                    <a href="/vehicles/${vehicleId}" class="flex items-center gap-0.625rem flex-1 min-w-0" style="display: flex; align-items: center; gap: 0.625rem; flex: 1; min-width: 0;">
                        <div class="vehicle-image-container relative">
                            ${img ? `<img src="${img.src}" alt="${img.alt}" class="h-full w-full object-cover" />` : ''}
                            <!-- Heart Icon - Top Right -->
                            <button type="button" class="absolute top-4 right-4 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite(${vehicleId}, event); return false;" aria-label="Add to favorites">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-orange-600 hover:text-red-500 transition-colors heart-icon" data-vehicle-id="${vehicleId}" data-dealer-id="">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            </button>
                        </div>
                        <div class="vehicle-content">
                            <div class="vehicle-main-info">
                                <div class="vehicle-title-row">
                                    <div class="vehicle-title-section">
                                        <h3>${title}</h3>
                                        ${version ? `<p class="text-muted-foreground">${version}</p>` : ''}
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.125rem;">
                                    <p class="vehicle-price">${price}</p>
                                    ${badges.length > 0 ? `
                                    <div class="vehicle-badges">
                                        ${badges.map(b => `<span>${b}</span>`).join('')}
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            ${specs.length > 0 ? `
                            <div class="vehicle-specs">
                                ${specs.join('')}
                            </div>
                            ` : ''}
                        </div>
                    </a>
                    <div class="vehicle-actions-section" onclick="event.stopPropagation()">
                        ${vehicleId ? `<a href="/vehicles/${vehicleId}" onclick="event.stopPropagation()"><button class="inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">View</button></a>` : ''}
                        <a href="/vehicles/${vehicleId}/enquire" onclick="event.stopPropagation()">
                            <button class="inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md border border-border bg-background px-2.5 py-1 text-xs font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">Enquire</button>
                        </a>
                    </div>
                `;
                
                // Replace card with list item
                card.replaceWith(listItem);
            });
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
                // Preserve current page from URL to ensure pagination is maintained
                const urlParams = new URLSearchParams(window.location.search);
                const currentPage = parseInt(urlParams.get('page')) || 1;
                // Explicitly pass page parameter as number to ensure it's preserved
                fetchVehicles({ page: currentPage });
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
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentPage = parseInt(urlParams.get('page')) || 1;
                    fetchVehicles({ page: currentPage });
                }
            }, 250);
        });
        
        // Initialize view on page load
        if (currentView) {
            // Force card view on mobile
            if (isMobile()) {
                currentView = 'card';
                localStorage.setItem('vehicleView', 'card');
            }
            setView(currentView);
            // If list view is saved, re-fetch to render in list format (only on desktop)
            if (currentView === 'list' && !isMobile()) {
                // Get current URL params to preserve filters
                const urlParams = new URLSearchParams(window.location.search);
                const page = urlParams.get('page') || 1;
                // Re-fetch to get proper list view rendering
                setTimeout(() => {
                    fetchVehicles({ page: parseInt(page) });
                }, 50);
            }
        }
        
        // Initialize filter chips and reset button visibility on page load
        renderFilterChips();
        updateResetButtonVisibility();
        
        // Initialize auto-apply filters for sidebar
        setupAutoApplyFilters();
        
        // Initialize all range sliders
        setTimeout(() => {
            sliderConfigs.forEach(config => initRangeSlider(config));
            setupEquipmentCollapsible();
            updateConditionStyles();
        }, 100);
        
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
                            window.showSnackbar(data.message || 'Removed from favorites', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('Please login to manage favorites', 'error');
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
                            window.showSnackbar(data.message || 'Failed to remove from favorites', 'error');
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
                            window.showSnackbar(data.message || 'Saved to favorites', 'success');
                        }
                    } else {
                        if (response.status === 401) {
                            if (window.showSnackbar) {
                                window.showSnackbar('Please login to save favorites', 'error');
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
                            window.showSnackbar(data.message || 'Failed to save to favorites', 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                if (window.showSnackbar) {
                    window.showSnackbar('An error occurred. Please try again.', 'error');
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

