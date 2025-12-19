@extends('layouts.app')

@section('title', 'Vehicle Details | RevoLot')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/embla-carousel@8.0.0/css/embla.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
@endpush

@php
// Placeholder vehicle data - in production, this would come from a database
$serialNo = request()->route('serialNo') ?? request()->segment(2);
$vehicle = [
    'id' => 1,
    'serialNo' => $serialNo,
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
    'vin' => 'JT2BF28K0X0123456',
    'engineNumber' => 'ENG123456789',
    'accidentHistory' => false,
    'blacklistFlags' => [],
    'features' => [
        'Sunroof',
        'Leather Seats',
        'ABS',
        'Dual Airbags',
        'Air Conditioning',
        'Power Steering',
        'Touchscreen Infotainment',
        'Android Auto',
        'Apple CarPlay',
        'Rear Parking Sensors',
        'Cruise Control',
        'Push Button Start',
        'Keyless Entry',
        'Automatic Climate Control',
        'Power Windows',
        'LED Headlamps',
        'Alloy Wheels',
    ],
    'description' => 'This well-maintained Toyota Camry XLE is in excellent condition with low mileage. It comes with all the modern features you expect from a premium sedan. The vehicle has been regularly serviced and is ready for immediate delivery.',
    'images' => [
        'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop',
        'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&h=600&fit=crop',
    ],
    'inventoryDate' => '2024-01-15',
    'pendingWorks' => [
        'Documents pending',
        'Name transfer',
        'Registration certificate transfer',
        'Insurance transfer',
    ],
    'remarks' => 'Vehicle is in excellent condition. All service records are available.',
];

function formatCurrency($amount) {
    return '₹' . number_format($amount, 0, '.', ',');
}

function getDateWithRelative($date) {
    $dateObj = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($dateObj);
    
    $formatted = $dateObj->format('F j, Y');
    
    if ($diff->days == 0) {
        $relative = 'today';
    } elseif ($diff->days == 1) {
        $relative = 'yesterday';
    } elseif ($diff->days < 7) {
        $relative = $diff->days . ' days ago';
    } elseif ($diff->days < 30) {
        $weeks = floor($diff->days / 7);
        $relative = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff->days < 365) {
        $months = floor($diff->days / 30);
        $relative = $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff->days / 365);
        $relative = $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
    
    return $formatted . ' (' . $relative . ')';
}

function getDaysInInventory($date) {
    $dateObj = new DateTime($date);
    $now = new DateTime();
    $diff = $now->diff($dateObj);
    return $diff->days;
}

function getStatusVariant($status) {
    switch (strtolower($status)) {
        case 'available':
            return 'default';
        case 'sold':
            return 'destructive';
        case 'reserved':
            return 'secondary';
        case 'pending':
            return 'outline';
        default:
            return 'secondary';
    }
}

function getConditionColor($condition) {
    switch (strtolower($condition)) {
        case 'excellent':
            return 'text-green-600 dark:text-green-400';
        case 'good':
            return 'text-blue-600 dark:text-blue-400';
        case 'fair':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'poor':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-muted-foreground';
    }
}

// For now, we'll show all info (no auth check) - set to true to show internal info like pending works
$canViewInternalInfo = true;
$isAdminOrDealer = false;
@endphp

@section('content')
<div class="container space-y-8 py-6">
    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="flex items-center gap-3">
                    <h1 class="text-foreground text-3xl font-bold tracking-tight">
                        {{ $vehicle['year'] }} {{ $vehicle['make'] }} {{ $vehicle['model'] }}
                        @if($vehicle['variant'])
                        <span class="text-muted-foreground">
                            {{ $vehicle['variant'] }}
                        </span>
                        @endif
                    </h1>
                </div>
                <p class="text-muted-foreground text-xl">
                    Registration: <span class="text-foreground font-mono">{{ $vehicle['registrationNumber'] }}</span>
                </p>
            </div>
            <div class="flex flex-col items-start gap-3 lg:items-end">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency($vehicle['listingPrice']) }}
                </p>
                <span class="inline-flex items-center rounded-md border border-border bg-secondary px-2 py-1 text-sm font-semibold text-secondary-foreground transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                    {{ $vehicle['status'] }}
                </span>
            </div>
        </div>
        <div class="border-t border-border"></div>
    </div>

    <!-- Images Carousel Section -->
    @if(isset($vehicle['images']) && count($vehicle['images']) > 0)
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.7 5c-.3-.9-1.2-1.5-2.2-1.5h-3c-.6 0-1 .4-1 1s.4 1 1 1h3c.2 0 .4.2.4.4l1.2 3.6c-.1 0-.2-.1-.3-.1H5c-.2 0-.4.1-.5.2L3.4 5c-.1-.2-.3-.4-.5-.4H2"></path>
                <path d="M3 17h14"></path>
                <path d="M5 17V9h14v8"></path>
            </svg>
            <h2 class="text-foreground text-xl font-semibold">
                Photos ({{ count($vehicle['images']) }})
            </h2>
        </div>

            <div class="relative">
            <div class="embla overflow-hidden" id="vehicle-images-carousel">
                <div class="embla__container flex">
                    @foreach($vehicle['images'] as $index => $image)
                    <div class="embla__slide flex-shrink-0 basis-full md:basis-1/2 lg:basis-1/3">
                        <a href="{{ $image }}" class="glightbox" data-gallery="vehicle-gallery" data-glightbox="title: Vehicle photo {{ $index + 1 }}">
                            <div class="border-border bg-muted/50 relative aspect-square cursor-pointer overflow-hidden rounded-lg border transition-all hover:shadow-md mr-4">
                                <img
                                    src="{{ $image }}"
                                    alt="Vehicle photo {{ $index + 1 }}"
                                    class="h-full w-full object-cover transition-transform hover:scale-105"
                                />
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @if(count($vehicle['images']) > 3)
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
        <div class="space-y-8 lg:col-span-2">
            <!-- Basic Information -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                        <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.7 5c-.3-.9-1.2-1.5-2.2-1.5h-3c-.6 0-1 .4-1 1s.4 1 1 1h3c.2 0 .4.2.4.4l1.2 3.6c-.1 0-.2-.1-.3-.1H5c-.2 0-.4.1-.5.2L3.4 5c-.1-.2-.3-.4-.5-.4H2"></path>
                        <path d="M3 17h14"></path>
                        <path d="M5 17V9h14v8"></path>
                    </svg>
                    <h2 class="text-foreground text-xl font-semibold">
                        Vehicle Information
                    </h2>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Make</label>
                        <p class="text-foreground font-medium">{{ $vehicle['make'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Model</label>
                        <p class="text-foreground font-medium">{{ $vehicle['model'] }}</p>
                    </div>
                    @if($vehicle['variant'])
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Variant</label>
                        <p class="text-foreground font-medium">{{ $vehicle['variant'] }}</p>
                    </div>
                    @endif
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Year</label>
                        <p class="text-foreground font-medium">{{ $vehicle['year'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Vehicle Type</label>
                        <p class="text-foreground font-medium">{{ $vehicle['vehicleType'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Color</label>
                        <p class="text-foreground font-medium">{{ $vehicle['color'] }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-border"></div>

            <!-- Technical Specifications -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                        <path d="M12 3v18"></path>
                        <path d="M3 12h18"></path>
                    </svg>
                    <h2 class="text-foreground text-xl font-semibold">
                        Technical Specifications
                    </h2>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    @if($canViewInternalInfo)
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">VIN</label>
                        <p class="text-foreground font-mono text-sm font-medium">{{ $vehicle['vin'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Engine Number</label>
                        <p class="text-foreground font-mono text-sm font-medium">{{ $vehicle['engineNumber'] }}</p>
                    </div>
                    @endif
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Transmission</label>
                        <p class="text-foreground font-medium">{{ $vehicle['transmissionType'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Fuel Type</label>
                        <p class="text-foreground font-medium">{{ $vehicle['fuelType'] }}</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Odometer</label>
                        <p class="text-foreground font-medium">{{ number_format($vehicle['odometer']) }} km</p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Ownership Count</label>
                        <p class="text-foreground font-medium">{{ $vehicle['ownershipCount'] }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-border"></div>

            <!-- Condition & History -->
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                        <path d="M21 3a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                        <path d="M3 3v5h5"></path>
                        <path d="M21 21v-5h-5"></path>
                    </svg>
                    <h2 class="text-foreground text-xl font-semibold">
                        Condition & History
                    </h2>
                </div>
                <div class="space-y-6">
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Condition</label>
                        <p class="font-semibold {{ getConditionColor($vehicle['condition']) }}">
                            {{ $vehicle['condition'] }}
                        </p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-muted-foreground text-sm font-medium">Accident History</label>
                        <p class="text-foreground font-medium">{{ $vehicle['accidentHistory'] ? 'Yes' : 'No' }}</p>
                    </div>
                    @if($canViewInternalInfo && isset($vehicle['blacklistFlags']) && count($vehicle['blacklistFlags']) > 0)
                    <div class="space-y-2">
                        <label class="text-muted-foreground text-sm font-medium">Blacklist Flags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($vehicle['blacklistFlags'] as $flag)
                            <span class="inline-flex items-center rounded-md border border-destructive bg-destructive px-2 py-1 text-sm font-semibold text-destructive-foreground transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                {{ $flag }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Features -->
            @if(isset($vehicle['features']) && count($vehicle['features']) > 0)
            <div class="border-t border-border"></div>
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted-foreground h-5 w-5">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    <h2 class="text-foreground text-xl font-semibold">
                        Features
                    </h2>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($vehicle['features'] as $feature)
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-green-500 dark:text-green-400">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="text-foreground font-medium">{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Description -->
            @if(isset($vehicle['description']) && $vehicle['description'])
            <div class="border-t border-border"></div>
            <div class="space-y-4">
                <h2 class="text-foreground text-xl font-semibold">Description</h2>
                <p class="text-foreground leading-relaxed">{{ $vehicle['description'] }}</p>
            </div>
            @endif
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Pricing -->
            <div class="rounded-lg bg-green-50 p-6 dark:bg-green-950/30">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-green-600 dark:text-green-400">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v12"></path>
                        <path d="M15 9H9a3 3 0 0 0 0 6h6a3 3 0 0 0 0-6z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-green-800 dark:text-green-300">
                        Pricing
                    </h2>
                </div>
                <div class="space-y-2">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ formatCurrency($vehicle['listingPrice']) }}
                    </p>
                    <p class="text-sm text-green-700 dark:text-green-300">
                        Listed Price
                    </p>
                </div>
            </div>

            <!-- Edit Action - Only for admin/dealer -->
            @if($isAdminOrDealer)
            <div class="bg-muted/50 rounded-lg p-6">
                <h2 class="text-foreground mb-4 text-xl font-semibold">Actions</h2>
                <a href="/dealer/vehicles/edit-vehicle/{{ $vehicle['serialNo'] }}" class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground shadow-xs transition-all hover:bg-secondary/80 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Vehicle
                </a>
            </div>
            @endif

            <!-- Listing Information -->
            <div class="rounded-lg bg-blue-50 p-6 dark:bg-blue-950/30">
                <div class="mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-blue-600 dark:text-blue-400">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                        <line x1="16" x2="16" y1="2" y2="6"></line>
                        <line x1="8" x2="8" y1="2" y2="6"></line>
                        <line x1="3" x2="21" y1="10" y2="10"></line>
                    </svg>
                    <h2 class="text-xl font-semibold text-blue-800 dark:text-blue-300">
                        {{ $canViewInternalInfo ? 'Inventory Information' : 'Listing Information' }}
                    </h2>
                </div>
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            {{ $canViewInternalInfo ? 'Added to Inventory' : 'Added to Listing' }}
                        </label>
                        <p class="text-sm text-blue-900 dark:text-blue-200">
                            {{ getDateWithRelative($vehicle['inventoryDate']) }}
                        </p>
                    </div>
                    @if($canViewInternalInfo)
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            Days in Inventory
                        </label>
                        <p class="font-semibold text-blue-900 dark:text-blue-200">
                            {{ getDaysInInventory($vehicle['inventoryDate']) }} days
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Pending Works - Only for admin/dealer -->
            @if($canViewInternalInfo && isset($vehicle['pendingWorks']) && count($vehicle['pendingWorks']) > 0)
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
                    @foreach($vehicle['pendingWorks'] as $work)
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-600 dark:text-yellow-400">
                            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                            <path d="M12 9v4"></path>
                            <path d="M12 17h.01"></path>
                        </svg>
                        <span class="text-sm text-yellow-800 dark:text-yellow-200">{{ $work }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Remarks - Only for admin/dealer -->
            @if($canViewInternalInfo && isset($vehicle['remarks']) && $vehicle['remarks'])
            <div class="bg-muted/50 rounded-lg p-6">
                <h2 class="text-foreground mb-4 text-xl font-semibold">Internal Remarks</h2>
                <p class="text-foreground text-sm leading-relaxed">{{ $vehicle['remarks'] }}</p>
            </div>
            @endif

            <!-- Public Contact Information - Only for non-authenticated users -->
            @if(!$isAdminOrDealer)
            <div class="bg-muted/50 rounded-lg p-6">
                <h2 class="text-foreground mb-4 text-xl font-semibold">Interested?</h2>
                <p class="text-muted-foreground mb-4 text-sm leading-relaxed">
                    Contact us for more information about this vehicle, including pricing, financing options, and scheduling a test drive.
                </p>
                <div class="text-muted-foreground text-sm">
                    <p>• Request detailed vehicle history</p>
                    <p>• Schedule inspection</p>
                    <p>• Discuss financing options</p>
                    <p>• Arrange test drive</p>
                </div>
            </div>
            @endif
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

