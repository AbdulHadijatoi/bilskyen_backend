@extends('layouts.app')

@section('title', 'Bilskyen | Home')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
@endpush

@section('content')
<div class="flex min-h-screen flex-col pt-0">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-b from-muted/60 to-muted py-20 md:py-32">
        <div class="container mx-auto px-4 md:px-6">
            <div class="max-w-4xl space-y-8">
                <h1 class="text-4xl font-bold tracking-tighter md:text-6xl">
                    Find Your Perfect Vehicle at Bilskyen
                </h1>
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

    <!-- Search Section -->
    <section class="relative bg-card py-8">
        <div class="container mx-auto px-4 md:px-6">
            <div class="rounded-lg border border-border p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="flex items-center gap-2 text-2xl font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                        Find Your Vehicle
                    </h2>
                    <p class="text-muted-foreground">
                        Search our inventory to find the perfect match for your needs.
                    </p>
                </div>
                <form class="space-y-6 overflow-auto" onsubmit="event.preventDefault(); window.location.href='/vehicles?' + new URLSearchParams(Object.fromEntries(new FormData(event.target))).toString();">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-2">
                            <label for="make" class="text-sm font-medium leading-none">Make</label>
                            <select id="make" name="make" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">Any Make</option>
                                <option value="Toyota">Toyota</option>
                                <option value="Honda">Honda</option>
                                <option value="Ford">Ford</option>
                                <option value="BMW">BMW</option>
                                <option value="Mercedes-Benz">Mercedes-Benz</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="model" class="text-sm font-medium leading-none">Model</label>
                            <input id="model" name="model" type="text" placeholder="Any Model" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        </div>

                        <div class="space-y-2">
                            <label for="vehicle-type" class="text-sm font-medium leading-none">Vehicle Type</label>
                            <select id="vehicle-type" name="vehicleType" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">Any Type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="SUV">SUV</option>
                                <option value="Hatchback">Hatchback</option>
                                <option value="Coupe">Coupe</option>
                            </select>
                        </div>

                        <div class="space-y-2 md:col-span-2 lg:col-span-1">
                            <label for="min-price" class="text-sm font-medium leading-none">Min Price</label>
                            <select id="min-price" name="minPrice" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">No Min</option>
                                <option value="500000">₹5,00,000</option>
                                <option value="1000000">₹10,00,000</option>
                                <option value="2000000">₹20,00,000</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="max-price" class="text-sm font-medium leading-none">Max Price</label>
                            <select id="max-price" name="maxPrice" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">No Max</option>
                                <option value="2000000">₹20,00,000</option>
                                <option value="5000000">₹50,00,000</option>
                                <option value="10000000">₹1,00,00,000</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="min-year" class="text-sm font-medium leading-none">Min Year</label>
                            <select id="min-year" name="minYear" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">No Min</option>
                                <option value="2015">2015</option>
                                <option value="2018">2018</option>
                                <option value="2020">2020</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label for="max-year" class="text-sm font-medium leading-none">Max Year</label>
                            <select id="max-year" name="maxYear" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="any">No Max</option>
                                <option value="2020">2020</option>
                                <option value="2022">2022</option>
                                <option value="2024">2024</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="search" class="mr-2 text-sm font-medium whitespace-nowrap">Search</label>
                        <div class="flex w-full items-center">
                            <input id="search" name="search" type="text" placeholder="Search vehicles..." class="h-10 w-full rounded-r-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-l-none rounded-r-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                Search
                            </button>
                        </div>
                    </div>
                </form>
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
                                            ₹{{ number_format($vehicle['listingPrice'], 0, '.', ',') }}
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
                            'location' => 'Mumbai, India',
                            'quote' => 'The team at Bilskyen made buying a car so easy. They were transparent about pricing and helped me find the perfect vehicle for my family.',
                            'rating' => 5
                        ],
                        [
                            'name' => 'Priya Sharma',
                            'location' => 'Delhi, India',
                            'quote' => 'I was impressed with their knowledge and no-pressure approach. I got a great deal on my new car and would definitely recommend them.',
                            'rating' => 5
                        ],
                        [
                            'name' => 'Ahmed Khan',
                            'location' => 'Bangalore, India',
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
</script>
@endsection

