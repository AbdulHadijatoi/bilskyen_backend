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
            <h2 id="filter-drawer-title" class="text-xl font-semibold">Filters</h2>
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
            <!-- Make Filter -->
            <div>
                <label class="text-sm font-medium mb-2 block">Make</label>
                <select 
                    name="make" 
                    class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="">All Makes</option>
                    @foreach(\App\Constants\Vehicles::MAKES as $make)
                        <option value="{{ $make }}">{{ $make }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Vehicle Type Filter -->
            <div>
                <label class="text-sm font-medium mb-2 block">Vehicle Type</label>
                <div class="space-y-2">
                    @foreach(['Sedan', 'SUV', 'Hatchback', 'Coupe', 'Convertible', 'Truck', 'Crossover', 'MPV'] as $type)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="vehicle_type[]" 
                                value="{{ $type }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                            >
                            <span class="text-sm">{{ $type }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

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
                            class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                    </div>
                </div>
            </div>

            <!-- Year Range -->
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
                            class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                    </div>
                </div>
            </div>

            <!-- Transmission Type -->
            <div>
                <label class="text-sm font-medium mb-2 block">Transmission Type</label>
                <div class="space-y-2">
                    @foreach(['Manual', 'Automatic', 'CVT', 'AMT', 'DCT'] as $transmission)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="transmission[]" 
                                value="{{ $transmission }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                            >
                            <span class="text-sm">{{ $transmission }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Fuel Type -->
            <div>
                <label class="text-sm font-medium mb-2 block">Fuel Type</label>
                <div class="space-y-2">
                    @foreach(['Petrol', 'Diesel', 'Electric Vehicle (EV)', 'Hybrid Electric Vehicle (HEV)', 'Plug-in Hybrid Electric Vehicle (PHEV)', 'CNG', 'LPG'] as $fuel)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="fuel_type[]" 
                                value="{{ $fuel }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                            >
                            <span class="text-sm">{{ $fuel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Odometer Range -->
            <div>
                <label class="text-sm font-medium mb-2 block">Odometer (km)</label>
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <label for="odometer-from" class="sr-only">Odometer From</label>
                        <input 
                            type="number" 
                            id="odometer-from"
                            name="odometer_from" 
                            placeholder="Min"
                            class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                    </div>
                    <span class="text-muted-foreground">-</span>
                    <div class="flex-1">
                        <label for="odometer-to" class="sr-only">Odometer To</label>
                        <input 
                            type="number" 
                            id="odometer-to"
                            name="odometer_to" 
                            placeholder="Max"
                            class="w-full h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                    </div>
                </div>
            </div>

            <!-- Condition -->
            <div>
                <label class="text-sm font-medium mb-2 block">Condition</label>
                <div class="space-y-2">
                    @foreach(\App\Constants\Vehicles::CONDITIONS as $condition)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="condition[]" 
                                value="{{ $condition }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                            >
                            <span class="text-sm">{{ $condition }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="text-sm font-medium mb-2 block">Status</label>
                <div class="space-y-2">
                    @foreach(['Available', 'Reserved', 'Sold', 'In Service'] as $status)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="status[]" 
                                value="{{ $status }}"
                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring"
                            >
                            <span class="text-sm">{{ $status }}</span>
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
            const form = filterDrawer.querySelector('form') || filterDrawer;
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        });

        // Apply filters (you can customize this to submit the form or update the page)
        filterApplyButton.addEventListener('click', () => {
            // Collect filter values
            const filters = {
                make: filterDrawer.querySelector('[name="make"]')?.value || '',
                vehicle_type: Array.from(filterDrawer.querySelectorAll('[name="vehicle_type[]"]:checked')).map(cb => cb.value),
                price_from: filterDrawer.querySelector('[name="price_from"]')?.value || '',
                price_to: filterDrawer.querySelector('[name="price_to"]')?.value || '',
                year_from: filterDrawer.querySelector('[name="year_from"]')?.value || '',
                year_to: filterDrawer.querySelector('[name="year_to"]')?.value || '',
                transmission: Array.from(filterDrawer.querySelectorAll('[name="transmission[]"]:checked')).map(cb => cb.value),
                fuel_type: Array.from(filterDrawer.querySelectorAll('[name="fuel_type[]"]:checked')).map(cb => cb.value),
                odometer_from: filterDrawer.querySelector('[name="odometer_from"]')?.value || '',
                odometer_to: filterDrawer.querySelector('[name="odometer_to"]')?.value || '',
                condition: Array.from(filterDrawer.querySelectorAll('[name="condition[]"]:checked')).map(cb => cb.value),
                status: Array.from(filterDrawer.querySelectorAll('[name="status[]"]:checked')).map(cb => cb.value),
            };
            
            console.log('Applied filters:', filters);
            // TODO: Implement actual filtering logic here
            // For now, just close the drawer
            closeDrawer();
        });
    })();
</script>
@endpush
@endsection

