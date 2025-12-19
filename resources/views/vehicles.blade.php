@extends('layouts.app')

@section('title', 'Vehicles | RevoLot')

@php
// Placeholder vehicle data - in production, this would come from a database
$vehicles = [
    [
        'id' => 1,
        'serialNo' => 1,
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
        'images' => ['https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop'],
    ],
    [
        'id' => 2,
        'serialNo' => 2,
        'make' => 'Honda',
        'model' => 'Civic',
        'variant' => 'VX',
        'year' => 2021,
        'listingPrice' => 1800000,
        'transmissionType' => 'Manual',
        'color' => 'White',
        'vehicleType' => 'Sedan',
        'odometer' => 25000,
        'fuelType' => 'Petrol',
        'ownershipCount' => 1,
        'status' => 'Available',
        'condition' => 'Good',
        'registrationNumber' => 'KL-02-CD-5678',
        'images' => ['https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop'],
    ],
    [
        'id' => 3,
        'serialNo' => 3,
        'make' => 'Maruti',
        'model' => 'Swift',
        'variant' => 'ZXI',
        'year' => 2023,
        'listingPrice' => 850000,
        'transmissionType' => 'Automatic',
        'color' => 'Red',
        'vehicleType' => 'Hatchback',
        'odometer' => 8000,
        'fuelType' => 'Petrol',
        'ownershipCount' => 1,
        'status' => 'Available',
        'condition' => 'Excellent',
        'registrationNumber' => 'KL-03-EF-9012',
        'images' => ['https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop'],
    ],
    [
        'id' => 4,
        'serialNo' => 4,
        'make' => 'Hyundai',
        'model' => 'Creta',
        'variant' => 'SX',
        'year' => 2022,
        'listingPrice' => 1650000,
        'transmissionType' => 'Automatic',
        'color' => 'Black',
        'vehicleType' => 'SUV',
        'odometer' => 20000,
        'fuelType' => 'Diesel',
        'ownershipCount' => 1,
        'status' => 'Available',
        'condition' => 'Excellent',
        'registrationNumber' => 'KL-04-GH-3456',
        'images' => ['https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'],
    ],
    [
        'id' => 5,
        'serialNo' => 5,
        'make' => 'Mahindra',
        'model' => 'XUV700',
        'variant' => 'AX7',
        'year' => 2023,
        'listingPrice' => 2200000,
        'transmissionType' => 'Automatic',
        'color' => 'Blue',
        'vehicleType' => 'SUV',
        'odometer' => 12000,
        'fuelType' => 'Diesel',
        'ownershipCount' => 1,
        'status' => 'Available',
        'condition' => 'Excellent',
        'registrationNumber' => 'KL-05-IJ-7890',
        'images' => ['https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'],
    ],
    [
        'id' => 6,
        'serialNo' => 6,
        'make' => 'Tata',
        'model' => 'Nexon',
        'variant' => 'XZ+',
        'year' => 2022,
        'listingPrice' => 1400000,
        'transmissionType' => 'Manual',
        'color' => 'Orange',
        'vehicleType' => 'SUV',
        'odometer' => 18000,
        'fuelType' => 'Electric',
        'ownershipCount' => 1,
        'status' => 'Available',
        'condition' => 'Good',
        'registrationNumber' => 'KL-06-KL-1357',
        'images' => ['https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'],
    ],
];

// Format currency helper
function formatCurrency($amount) {
    return '₹' . number_format($amount, 0, '.', ',');
}
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
        <button type="button" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
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
                    src="{{ $vehicle['images'][0] }}"
                    alt="{{ $vehicle['make'] }} {{ $vehicle['model'] }}"
                    class="h-full w-full object-cover transition-transform hover:scale-105"
                />
                <span class="absolute top-2 right-2 z-10 rounded-md bg-secondary px-2 py-0.5 text-xs font-semibold text-secondary-foreground">
                    {{ $vehicle['registrationNumber'] }}
                </span>
            </div>
            
            <!-- Vehicle Details -->
            <div class="px-4 py-4 space-y-4">
                <div class="flex flex-col gap-1">
                    <h3 class="flex items-center gap-2 text-xl font-bold">
                        {{ $vehicle['make'] }} {{ $vehicle['model'] }}
                    </h3>
                    <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                        {{ $vehicle['variant'] }}
                    </p>
                    <p class="text-primary text-2xl font-medium">
                        {{ formatCurrency($vehicle['listingPrice']) }}
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
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span>{{ number_format($vehicle['odometer']) }} km</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="2" x2="22" y1="2" y2="2"></line>
                            <line x1="6" x2="6" y1="6" y2="22"></line>
                            <line x1="18" x2="18" y1="6" y2="22"></line>
                            <line x1="2" x2="22" y1="22" y2="22"></line>
                        </svg>
                        <span>{{ $vehicle['fuelType'] }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>
                            {{ $vehicle['ownershipCount'] }} Owner{{ $vehicle['ownershipCount'] > 1 ? 's' : '' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>{{ $vehicle['status'] }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            <path d="M18.63 13A17.888 17.888 0 0 1 18 8"></path>
                            <path d="M6.26 6.26A5.86 5.86 0 0 0 6 8c0 7-3 9-3 9s14 0 17-5c.34-.94.56-1.92.73-2.92"></path>
                            <path d="M2 2l20 20"></path>
                            <path d="M22 8A10 10 0 0 0 9.04 4.32"></path>
                        </svg>
                        <span>{{ $vehicle['condition'] }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Vehicle Actions -->
            <div class="mt-auto p-4 pt-2">
                <div class="flex w-full flex-col gap-2 sm:flex-row">
                    <a href="/vehicles/{{ $vehicle['serialNo'] }}" class="flex-1">
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
@endsection

