@extends('layouts.app')

@section('title', 'Sell Your Car - Bilskyen')

@push('styles')
<style>
    .collapsible-section {
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    
    .collapsible-header {
        padding: 1rem;
        background-color: var(--muted);
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }
    
    .collapsible-header:hover {
        background-color: var(--accent);
    }
    
    .collapsible-content {
        padding: 1.5rem;
        display: none;
    }
    
    .collapsible-content.active {
        display: block;
    }
    
    .section-icon {
        transition: transform 0.2s;
    }
    
    .section-icon.rotated {
        transform: rotate(180deg);
    }
</style>
@endpush

@section('content')
<div class="container py-4 md:py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight mb-2">
            Sell your car on Denmark's largest car market
        </h1>
        <p class="text-muted-foreground max-w-2xl">
            Enter your car's license plate and we'll help you with the rest.
        </p>
    </div>

    @if(session('success'))
        <div class="w-full rounded-md border border-green-200 bg-green-50 p-4 text-green-800 mb-6">
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="w-full rounded-md border border-red-200 bg-red-50 p-4 text-red-800 mb-6">
            <p class="text-sm font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Registration Lookup Section -->
    <div class="mb-8 rounded-lg border border-border bg-card p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label for="registration-lookup" class="block text-sm font-medium mb-2">
                    License Plate Number
                </label>
                <input
                    type="text"
                    id="registration-lookup"
                    placeholder="Enter your car's license plate"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                <p class="text-xs text-muted-foreground mt-1" id="lookup-error"></p>
            </div>
            <div class="flex items-end">
                <button
                    type="button"
                    id="lookup-btn"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-6 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    Sell Your Car
                </button>
            </div>
        </div>
        <div id="lookup-loading" class="hidden mt-4">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading vehicle information...</span>
            </div>
        </div>
    </div>

    <!-- Vehicle Form -->
    <form id="vehicle-form" data-action="{{ route('sell-your-car.store') }}" enctype="multipart/form-data" class="hidden">
        @csrf

        <!-- Error Display Container (for AJAX validation errors) -->
        <div id="form-errors-top" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-4 text-red-800 mb-6"></div>

        <!-- Basic Information Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('basic-info')">
                <h2 class="text-lg font-semibold">Basic Information</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content active" id="basic-info-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="title" class="text-sm font-medium">Title *</label>
                        <input type="text" id="title" name="title" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('title') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('title')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="registration" class="text-sm font-medium">Registration *</label>
                        <input type="text" id="registration" name="registration" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('registration') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('registration')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="vin" class="text-sm font-medium">VIN</label>
                        <input type="text" id="vin" name="vin"
                            class="flex h-10 w-full rounded-md border {{ $errors->has('vin') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('vin')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium">Price (DKK) *</label>
                        <input type="number" id="price" name="price" required min="0"
                            class="flex h-10 w-full rounded-md border {{ $errors->has('price') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('price')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="location_id" class="text-sm font-medium">Location *</label>
                        <select id="location_id" name="location_id" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('location_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Location</option>
                            @foreach($lookupData['locations'] as $location)
                                <option value="{{ $location->id }}">
                                    {{ $location->city }}, {{ $location->postcode }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="listing_type_id" class="text-sm font-medium">Listing Type</label>
                        <select id="listing_type_id" name="listing_type_id"
                            class="flex h-10 w-full rounded-md border {{ $errors->has('listing_type_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Type</option>
                            @foreach($lookupData['listingTypes'] as $type)
                                <option value="{{ $type->id }}" {{ $type->name === 'Purchase' ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-muted-foreground mt-1">
                            <strong>Purchase:</strong> You are selling the vehicle directly. 
                            <strong>Leasing:</strong> You are offering the vehicle for lease/rental.
                        </p>
                        @error('listing_type_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Specifications Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('vehicle-specs')">
                <h2 class="text-lg font-semibold">Vehicle Specifications</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="vehicle-specs-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="category_id" class="text-sm font-medium">Category</label>
                        <select id="category_id" name="category_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Category</option>
                            @foreach($lookupData['categories'] as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="brand_id" class="text-sm font-medium">Brand</label>
                        <select id="brand_id" name="brand_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Brand</option>
                            @foreach($lookupData['brands'] as $brand)
                                <option value="{{ $brand->id }}">
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="model_id" class="text-sm font-medium">Model</label>
                        <select id="model_id" name="model_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Model</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="model_year_id" class="text-sm font-medium">Model Year</label>
                        <select id="model_year_id" name="model_year_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Year</option>
                            @foreach($lookupData['modelYears'] as $year)
                                <option value="{{ $year->id }}">
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_type_id" class="text-sm font-medium">Fuel Type *</label>
                        <select id="fuel_type_id" name="fuel_type_id" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('fuel_type_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Fuel Type</option>
                            @foreach($lookupData['fuelTypes'] as $fuelType)
                                <option value="{{ $fuelType->id }}">
                                    {{ $fuelType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('fuel_type_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="mileage" class="text-sm font-medium">Mileage (km)</label>
                        <input type="number" id="mileage" name="mileage" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium">Kilometers Driven</label>
                        <input type="number" id="km_driven" name="km_driven" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="battery_capacity" class="text-sm font-medium">Battery Capacity (kWh)</label>
                        <input type="number" id="battery_capacity" name="battery_capacity" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_power" class="text-sm font-medium">Engine Power (HP)</label>
                        <input type="number" id="engine_power" name="engine_power" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="towing_weight" class="text-sm font-medium">Towing Weight (kg)</label>
                        <input type="number" id="towing_weight" name="towing_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="ownership_tax" class="text-sm font-medium">Ownership Tax (DKK)</label>
                        <input type="number" id="ownership_tax" name="ownership_tax" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="first_registration_date" class="text-sm font-medium">First Registration Date</label>
                        <input type="date" id="first_registration_date" name="first_registration_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Specifications Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('detailed-specs')">
                <h2 class="text-lg font-semibold">Detailed Specifications</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="detailed-specs-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="description" class="text-sm font-medium">Description</label>
                        <textarea id="description" name="description" rows="4"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label for="type_id" class="text-sm font-medium">Type</label>
                        <select id="type_id" name="type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Type</option>
                            @foreach($lookupData['types'] as $type)
                                <option value="{{ $type->id }}">
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="use_id" class="text-sm font-medium">Use</label>
                        <select id="use_id" name="use_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Use</option>
                            @foreach($lookupData['uses'] as $use)
                                <option value="{{ $use->id }}">
                                    {{ $use->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="color_id" class="text-sm font-medium">Color</label>
                        <select id="color_id" name="color_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Color</option>
                            @foreach($lookupData['colors'] as $color)
                                <option value="{{ $color->id }}">
                                    {{ $color->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="body_type_id" class="text-sm font-medium">Body Type</label>
                        <select id="body_type_id" name="body_type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Body Type</option>
                            @foreach($lookupData['bodyTypes'] as $bodyType)
                                <option value="{{ $bodyType->id }}">
                                    {{ $bodyType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="price_type_id" class="text-sm font-medium">Price Type</label>
                        <select id="price_type_id" name="price_type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Price Type</option>
                            @foreach($lookupData['priceTypes'] as $priceType)
                                <option value="{{ $priceType->id }}">
                                    {{ $priceType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="condition_id" class="text-sm font-medium">Condition</label>
                        <select id="condition_id" name="condition_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Condition</option>
                            @foreach($lookupData['conditions'] as $condition)
                                <option value="{{ $condition->id }}">
                                    {{ $condition->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="gear_type_id" class="text-sm font-medium">Gear Type</label>
                        <select id="gear_type_id" name="gear_type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Gear Type</option>
                            @foreach($lookupData['gearTypes'] as $gearType)
                                <option value="{{ $gearType->id }}">
                                    {{ $gearType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="sales_type_id" class="text-sm font-medium">Sales Type</label>
                        <select id="sales_type_id" name="sales_type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Sales Type</option>
                            @foreach($lookupData['salesTypes'] as $salesType)
                                <option value="{{ $salesType->id }}">
                                    {{ $salesType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Additional detailed fields -->
                    <div class="space-y-2">
                        <label for="version" class="text-sm font-medium">Version</label>
                        <input type="text" id="version" name="version"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vin_location" class="text-sm font-medium">VIN Location</label>
                        <input type="text" id="vin_location" name="vin_location"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vehicle_external_id" class="text-sm font-medium">Vehicle External ID</label>
                        <input type="text" id="vehicle_external_id" name="vehicle_external_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="total_weight" class="text-sm font-medium">Total Weight (kg)</label>
                        <input type="number" id="total_weight" name="total_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vehicle_weight" class="text-sm font-medium">Vehicle Weight (kg)</label>
                        <input type="number" id="vehicle_weight" name="vehicle_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="technical_total_weight" class="text-sm font-medium">Technical Total Weight (kg)</label>
                        <input type="number" id="technical_total_weight" name="technical_total_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="minimum_weight" class="text-sm font-medium">Minimum Weight (kg)</label>
                        <input type="number" id="minimum_weight" name="minimum_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="gross_combination_weight" class="text-sm font-medium">Gross Combination Weight (kg)</label>
                        <input type="number" id="gross_combination_weight" name="gross_combination_weight" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="towing_weight_brakes" class="text-sm font-medium">Towing Weight with Brakes (kg)</label>
                        <input type="number" id="towing_weight_brakes" name="towing_weight_brakes" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="coupling" class="text-sm font-medium">Coupling</label>
                        <select id="coupling" name="coupling"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="engine_displacement" class="text-sm font-medium">Engine Displacement (cc)</label>
                        <input type="number" id="engine_displacement" name="engine_displacement" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_code" class="text-sm font-medium">Engine Code</label>
                        <input type="text" id="engine_code" name="engine_code"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_cylinders" class="text-sm font-medium">Engine Cylinders</label>
                        <input type="number" id="engine_cylinders" name="engine_cylinders" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="doors" class="text-sm font-medium">Doors</label>
                        <input type="number" id="doors" name="doors" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="minimum_seats" class="text-sm font-medium">Minimum Seats</label>
                        <input type="number" id="minimum_seats" name="minimum_seats" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="maximum_seats" class="text-sm font-medium">Maximum Seats</label>
                        <input type="number" id="maximum_seats" name="maximum_seats" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="top_speed" class="text-sm font-medium">Top Speed (km/h)</label>
                        <input type="number" id="top_speed" name="top_speed" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_efficiency" class="text-sm font-medium">Fuel Efficiency (L/100km)</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" step="0.01" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="airbags" class="text-sm font-medium">Airbags</label>
                        <input type="number" id="airbags" name="airbags" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="ncap_five" class="text-sm font-medium">NCAP 5-Star</label>
                        <select id="ncap_five" name="ncap_five"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="integrated_child_seats" class="text-sm font-medium">Integrated Child Seats</label>
                        <input type="number" id="integrated_child_seats" name="integrated_child_seats" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="seat_belt_alarms" class="text-sm font-medium">Seat Belt Alarms</label>
                        <input type="number" id="seat_belt_alarms" name="seat_belt_alarms" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="euronorm" class="text-sm font-medium">Euro Norm</label>
                        <input type="text" id="euronorm" name="euronorm"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="wheels" class="text-sm font-medium">Wheels</label>
                        <input type="text" id="wheels" name="wheels"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="axles" class="text-sm font-medium">Axles</label>
                        <input type="text" id="axles" name="axles"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="drive_axles" class="text-sm font-medium">Drive Axles</label>
                        <input type="text" id="drive_axles" name="drive_axles"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="wheelbase" class="text-sm font-medium">Wheelbase (mm)</label>
                        <input type="number" id="wheelbase" name="wheelbase" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="type_approval_code" class="text-sm font-medium">Type Approval Code</label>
                        <input type="text" id="type_approval_code" name="type_approval_code"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="category" class="text-sm font-medium">Category (String)</label>
                        <input type="text" id="category" name="category"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="extra_equipment" class="text-sm font-medium">Extra Equipment</label>
                        <textarea id="extra_equipment" name="extra_equipment" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label for="dispensations" class="text-sm font-medium">Dispensations</label>
                        <textarea id="dispensations" name="dispensations" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label for="permits" class="text-sm font-medium">Permits</label>
                        <textarea id="permits" name="permits" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration & Status Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('registration-status')">
                <h2 class="text-lg font-semibold">Registration & Status</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="registration-status-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="registration_status" class="text-sm font-medium">Registration Status</label>
                        <input type="text" id="registration_status" name="registration_status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="registration_status_updated_date" class="text-sm font-medium">Registration Status Updated Date</label>
                        <input type="date" id="registration_status_updated_date" name="registration_status_updated_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="expire_date" class="text-sm font-medium">Expire Date</label>
                        <input type="date" id="expire_date" name="expire_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="status_updated_date" class="text-sm font-medium">Status Updated Date</label>
                        <input type="date" id="status_updated_date" name="status_updated_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Inspection Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('inspection')">
                <h2 class="text-lg font-semibold">Inspection Details</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="inspection-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="last_inspection_date" class="text-sm font-medium">Last Inspection Date</label>
                        <input type="date" id="last_inspection_date" name="last_inspection_date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="last_inspection_result" class="text-sm font-medium">Last Inspection Result</label>
                        <input type="text" id="last_inspection_result" name="last_inspection_result"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="last_inspection_odometer" class="text-sm font-medium">Last Inspection Odometer (km)</label>
                        <input type="number" id="last_inspection_odometer" name="last_inspection_odometer" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Leasing Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('leasing')">
                <h2 class="text-lg font-semibold">Leasing Information</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="leasing-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label for="leasing_period_start" class="text-sm font-medium">Leasing Period Start</label>
                        <input type="date" id="leasing_period_start" name="leasing_period_start"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="leasing_period_end" class="text-sm font-medium">Leasing Period End</label>
                        <input type="date" id="leasing_period_end" name="leasing_period_end"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment & Features Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('equipment')">
                <h2 class="text-lg font-semibold">Equipment & Features</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="equipment-content">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($lookupData['equipment'] as $equip)
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="equipment_ids[]" value="{{ $equip->id }}"
                                class="rounded border-input">
                            <span class="text-sm">{{ $equip->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Listing Details Section -->
        <div class="collapsible-section">
            <div class="collapsible-header" onclick="toggleSection('listing-details')">
                <h2 class="text-lg font-semibold">Listing Details</h2>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="collapsible-content" id="listing-details-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- vehicle_list_status_id and published_at are set by backend, hidden from form -->
                    <input type="hidden" name="vehicle_list_status_id" value="{{ \App\Constants\VehicleListStatus::PUBLISHED }}">
                    <input type="hidden" name="published_at" value="">

                    <div class="space-y-2 md:col-span-2">
                        <label for="images" class="text-sm font-medium">Vehicle Images</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <p class="text-xs text-muted-foreground">You can select multiple images</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-6 flex justify-end">
            <button
                type="submit"
                class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
            >
                Save Vehicle Listing
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/sell-your-car-form.js') }}"></script>
@endpush
@endsection

