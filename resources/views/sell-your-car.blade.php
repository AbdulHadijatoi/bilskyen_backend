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
    <form id="vehicle-form" method="POST" action="{{ route('sell-your-car.store') }}" enctype="multipart/form-data">
        @csrf

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
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('title') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('title')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="registration" class="text-sm font-medium">Registration *</label>
                        <input type="text" id="registration" name="registration" value="{{ old('registration') }}" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('registration') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('registration')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="vin" class="text-sm font-medium">VIN</label>
                        <input type="text" id="vin" name="vin" value="{{ old('vin') }}"
                            class="flex h-10 w-full rounded-md border {{ $errors->has('vin') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                        @error('vin')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium">Price (DKK) *</label>
                        <input type="number" id="price" name="price" value="{{ old('price') }}" required min="0"
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
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
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
                                <option value="{{ $type->id }}" {{ old('listing_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
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
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
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
                                <option value="{{ $year->id }}" {{ old('model_year_id') == $year->id ? 'selected' : '' }}>
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
                                <option value="{{ $fuelType->id }}" {{ old('fuel_type_id') == $fuelType->id ? 'selected' : '' }}>
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
                        <input type="number" id="mileage" name="mileage" value="{{ old('mileage') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium">Kilometers Driven</label>
                        <input type="number" id="km_driven" name="km_driven" value="{{ old('km_driven') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="battery_capacity" class="text-sm font-medium">Battery Capacity (kWh)</label>
                        <input type="number" id="battery_capacity" name="battery_capacity" value="{{ old('battery_capacity') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_power" class="text-sm font-medium">Engine Power (HP)</label>
                        <input type="number" id="engine_power" name="engine_power" value="{{ old('engine_power') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="towing_weight" class="text-sm font-medium">Towing Weight (kg)</label>
                        <input type="number" id="towing_weight" name="towing_weight" value="{{ old('towing_weight') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="ownership_tax" class="text-sm font-medium">Ownership Tax (DKK)</label>
                        <input type="number" id="ownership_tax" name="ownership_tax" value="{{ old('ownership_tax') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="first_registration_date" class="text-sm font-medium">First Registration Date</label>
                        <input type="date" id="first_registration_date" name="first_registration_date" value="{{ old('first_registration_date') }}"
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
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm">{{ old('description') }}</textarea>
                    </div>

                    <div class="space-y-2">
                        <label for="type_id" class="text-sm font-medium">Type</label>
                        <select id="type_id" name="type_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Type</option>
                            @foreach($lookupData['types'] as $type)
                                <option value="{{ $type->id }}" {{ old('type_id') == $type->id ? 'selected' : '' }}>
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
                                <option value="{{ $use->id }}" {{ old('use_id') == $use->id ? 'selected' : '' }}>
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
                                <option value="{{ $color->id }}" {{ old('color_id') == $color->id ? 'selected' : '' }}>
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
                                <option value="{{ $bodyType->id }}" {{ old('body_type_id') == $bodyType->id ? 'selected' : '' }}>
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
                                <option value="{{ $priceType->id }}" {{ old('price_type_id') == $priceType->id ? 'selected' : '' }}>
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
                                <option value="{{ $condition->id }}" {{ old('condition_id') == $condition->id ? 'selected' : '' }}>
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
                                <option value="{{ $gearType->id }}" {{ old('gear_type_id') == $gearType->id ? 'selected' : '' }}>
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
                                <option value="{{ $salesType->id }}" {{ old('sales_type_id') == $salesType->id ? 'selected' : '' }}>
                                    {{ $salesType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Additional detailed fields -->
                    <div class="space-y-2">
                        <label for="version" class="text-sm font-medium">Version</label>
                        <input type="text" id="version" name="version" value="{{ old('version') }}"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vin_location" class="text-sm font-medium">VIN Location</label>
                        <input type="text" id="vin_location" name="vin_location" value="{{ old('vin_location') }}"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="total_weight" class="text-sm font-medium">Total Weight (kg)</label>
                        <input type="number" id="total_weight" name="total_weight" value="{{ old('total_weight') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vehicle_weight" class="text-sm font-medium">Vehicle Weight (kg)</label>
                        <input type="number" id="vehicle_weight" name="vehicle_weight" value="{{ old('vehicle_weight') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_displacement" class="text-sm font-medium">Engine Displacement (cc)</label>
                        <input type="number" id="engine_displacement" name="engine_displacement" value="{{ old('engine_displacement') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_cylinders" class="text-sm font-medium">Engine Cylinders</label>
                        <input type="number" id="engine_cylinders" name="engine_cylinders" value="{{ old('engine_cylinders') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="doors" class="text-sm font-medium">Doors</label>
                        <input type="number" id="doors" name="doors" value="{{ old('doors') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="minimum_seats" class="text-sm font-medium">Minimum Seats</label>
                        <input type="number" id="minimum_seats" name="minimum_seats" value="{{ old('minimum_seats') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="maximum_seats" class="text-sm font-medium">Maximum Seats</label>
                        <input type="number" id="maximum_seats" name="maximum_seats" value="{{ old('maximum_seats') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="top_speed" class="text-sm font-medium">Top Speed (km/h)</label>
                        <input type="number" id="top_speed" name="top_speed" value="{{ old('top_speed') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_efficiency" class="text-sm font-medium">Fuel Efficiency (L/100km)</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" value="{{ old('fuel_efficiency') }}" step="0.01" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="airbags" class="text-sm font-medium">Airbags</label>
                        <input type="number" id="airbags" name="airbags" value="{{ old('airbags') }}" min="0"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="ncap_five" class="text-sm font-medium">NCAP 5-Star</label>
                        <select id="ncap_five" name="ncap_five"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select</option>
                            <option value="1" {{ old('ncap_five') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('ncap_five') == '0' ? 'selected' : '' }}>No</option>
                        </select>
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
                                {{ in_array($equip->id, old('equipment_ids', [])) ? 'checked' : '' }}
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
                    <div class="space-y-2">
                        <label for="vehicle_list_status_id" class="text-sm font-medium">Listing Status *</label>
                        <select id="vehicle_list_status_id" name="vehicle_list_status_id" required
                            class="flex h-10 w-full rounded-md border {{ $errors->has('vehicle_list_status_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Status</option>
                            @foreach($lookupData['vehicleListStatuses'] as $status)
                                <option value="{{ $status->id }}" {{ old('vehicle_list_status_id', 1) == $status->id ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_list_status_id')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="published_at" class="text-sm font-medium">Published At</label>
                        <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at') }}"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

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
<script>
    // Collapsible sections
    function toggleSection(sectionId) {
        const content = document.getElementById(sectionId + '-content');
        const header = content.previousElementSibling;
        const icon = header.querySelector('.section-icon');
        
        content.classList.toggle('active');
        icon.classList.toggle('rotated');
    }

    // Load models when brand is selected
    document.getElementById('brand_id')?.addEventListener('change', function() {
        const brandId = this.value;
        const modelSelect = document.getElementById('model_id');
        
        if (!brandId) {
            modelSelect.innerHTML = '<option value="">Select Model</option>';
            return;
        }

        fetch(`/api/v1/models?brand_id=${brandId}`)
            .then(response => response.json())
            .then(data => {
                modelSelect.innerHTML = '<option value="">Select Model</option>';
                if (data.data && Array.isArray(data.data)) {
                    data.data.forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading models:', error));
    });

    // Registration lookup
    const lookupBtn = document.getElementById('lookup-btn');
    const registrationInput = document.getElementById('registration-lookup');
    const vehicleForm = document.getElementById('vehicle-form');
    const lookupError = document.getElementById('lookup-error');
    const lookupLoading = document.getElementById('lookup-loading');

    function performLookup() {
        const registration = registrationInput.value.trim();
        
        if (!registration) {
            lookupError.textContent = 'Please enter a license plate number';
            lookupError.classList.add('text-red-600');
            return;
        }

        lookupError.textContent = '';
        lookupError.classList.remove('text-red-600');
        lookupLoading.classList.remove('hidden');
        lookupBtn.disabled = true;
        lookupBtn.textContent = 'Loading...';

        fetch('/api/v1/nummerplade/vehicle-by-registration', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ 
                registration: registration,
                advanced: true // Request advanced data for more complete information
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            lookupLoading.classList.add('hidden');
            lookupBtn.disabled = false;
            lookupBtn.textContent = 'Sell Your Car';

            console.log('API Response:', data); // Debug log

            if (data.status === 'error' || !data.data) {
                let errorMessage = data.message || 'Failed to fetch vehicle information';
                
                // Check if it's a timeout error
                if (data.errors && data.errors.code === 'TIMEOUT') {
                    errorMessage = 'The vehicle lookup is taking longer than expected. Please try again in a moment, or you can fill in the form manually.';
                } else if (data.errors && data.errors.retryable) {
                    errorMessage = 'The vehicle lookup service is temporarily unavailable. Please try again in a moment, or you can fill in the form manually.';
                }
                
                lookupError.textContent = errorMessage;
                lookupError.classList.add('text-red-600');
                
                // Show the form even on error so user can fill manually
                vehicleForm.classList.remove('hidden');
                return;
            }

            // Prefill form with API data
            // The API response structure is: { data: { data: {...vehicle data...} } } }
            let vehicleData = null;
            
            console.log('Full API Response:', data); // Debug log
            console.log('Response keys:', Object.keys(data)); // Debug log
            
            // Handle nested data structure: data.data.data
            if (data.data && data.data.data) {
                // Nested structure: { data: { data: {...vehicle data...} } }
                vehicleData = data.data.data;
                console.log('Using data.data.data (nested structure)');
            } else if (data.data) {
                // Standard response format: { data: {...} }
                vehicleData = data.data;
                console.log('Using data.data');
            } else if (data.vehicle) {
                vehicleData = data.vehicle;
                console.log('Using data.vehicle');
            } else if (Array.isArray(data) && data.length > 0) {
                vehicleData = data[0];
                console.log('Using array[0]');
            } else if (data.status === 'success' && data.data) {
                vehicleData = data.data;
                console.log('Using data.data (with status)');
            } else if (typeof data === 'object' && !data.status && !data.errors) {
                // If data itself is the vehicle object (no wrapper)
                vehicleData = data;
                console.log('Using data directly');
            }
            
            console.log('Extracted Vehicle Data:', vehicleData); // Debug log
            console.log('Vehicle Data keys:', vehicleData ? Object.keys(vehicleData) : 'null'); // Debug log
            
            if (!vehicleData || typeof vehicleData !== 'object') {
                const errorMsg = 'No vehicle data found in API response. Response structure: ' + JSON.stringify(data).substring(0, 500);
                lookupError.textContent = errorMsg;
                lookupError.classList.add('text-red-600');
                console.error('Failed to extract vehicle data:', data);
                vehicleForm.classList.remove('hidden');
                return;
            }
            
            // Show form before prefilling
            vehicleForm.classList.remove('hidden');
            
            // Small delay to ensure form is rendered
            setTimeout(() => {
                prefillForm(vehicleData);
                // Scroll to form after prefilling
                vehicleForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        })
        .catch(error => {
            lookupLoading.classList.add('hidden');
            lookupBtn.disabled = false;
            lookupBtn.textContent = 'Sell Your Car';
            lookupError.textContent = 'An error occurred while fetching vehicle information';
            lookupError.classList.add('text-red-600');
            console.error('Lookup error:', error);
            
            // Show form even on error so user can fill manually
            vehicleForm.classList.remove('hidden');
        });
    }

    lookupBtn.addEventListener('click', performLookup);
    registrationInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performLookup();
        }
    });

    // Prefill form with API data
    function prefillForm(apiData) {
        console.log('PrefillForm called with:', apiData); // Debug log
        console.log('All API data keys:', Object.keys(apiData)); // Debug log
        
        // Make sure form is visible
        vehicleForm.classList.remove('hidden');
        
        // Helper function to safely set field value
        function setFieldValue(fieldId, value) {
            const field = document.getElementById(fieldId);
            if (!field) {
                console.warn(`Field not found: ${fieldId}`);
                return false;
            }
            if (value !== null && value !== undefined && value !== '') {
                field.value = value;
                console.log(`Set ${fieldId} = ${value}`);
                return true;
            }
            return false;
        }
        
        // Helper function to safely set select value by text match
        function setSelectByText(selectId, textValue) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.warn(`Select not found: ${selectId}`);
                return false;
            }
            if (!textValue) return false;
            
            const text = String(textValue).toLowerCase().trim();
            for (let option of select.options) {
                if (option.value && option.text.trim().toLowerCase() === text) {
                    select.value = option.value;
                    console.log(`Set ${selectId} = ${option.value} (matched: ${text})`);
                    return true;
                }
            }
            console.warn(`No match found in ${selectId} for: ${text}`);
            return false;
        }
        
        // Test if form fields exist
        console.log('Testing form fields existence:');
        console.log('registration field:', document.getElementById('registration') ? 'EXISTS' : 'NOT FOUND');
        console.log('vin field:', document.getElementById('vin') ? 'EXISTS' : 'NOT FOUND');
        console.log('title field:', document.getElementById('title') ? 'EXISTS' : 'NOT FOUND');
        
        // Basic fields - handle both camelCase and snake_case
        const registration = apiData.registration || apiData.registration_number || apiData.reg || apiData.plate || apiData.license_plate;
        console.log('Registration value:', registration);
        if (registration) {
            setFieldValue('registration', registration);
        }
        
        const vin = apiData.vin || apiData.chassis_number || apiData.chassis || apiData.chassisNumber;
        console.log('VIN value:', vin);
        if (vin) {
            setFieldValue('vin', vin);
        }
        
        // Title - try multiple field names
        const title = apiData.title || 
                     apiData.name || 
                     (apiData.make && apiData.model ? `${apiData.make} ${apiData.model}` : null) ||
                     (apiData.brand && apiData.model ? `${apiData.brand} ${apiData.model}` : null) ||
                     (apiData.manufacturer && apiData.model ? `${apiData.manufacturer} ${apiData.model}` : null);
        console.log('Title value:', title);
        if (title) {
            setFieldValue('title', String(title).trim());
        }

        // Map brand - handle both camelCase and snake_case (API uses "brand" not "make")
        const brandName = apiData.brand || apiData.make || apiData.manufacturer || apiData.make_name;
        console.log('Brand name:', brandName);
        if (brandName) {
            const brandSelect = document.getElementById('brand_id');
            let brandFound = false;
            
            // Try to find brand in dropdown
            if (setSelectByText('brand_id', brandName)) {
                brandSelect.dispatchEvent(new Event('change'));
                brandFound = true;
                
                // Wait for models to load, then select the model
                setTimeout(() => {
                    const modelName = apiData.model || apiData.model_name || apiData.modelName;
                    if (modelName) {
                        const modelSelect = document.getElementById('model_id');
                        
                        // First try to find existing option
                        if (!setSelectByText('model_id', modelName)) {
                            // If not found, store model_name for backend to handle
                            let hiddenInput = document.getElementById('model_name_hidden');
                            if (!hiddenInput) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.id = 'model_name_hidden';
                                hiddenInput.name = 'model_name';
                                document.getElementById('vehicle-form').appendChild(hiddenInput);
                            }
                            hiddenInput.value = modelName;
                        }
                    }
                }, 500);
            }
            
            // If brand not found in dropdown, store brand_name for backend to create it
            if (!brandFound) {
                let brandHiddenInput = document.getElementById('brand_name_hidden');
                if (!brandHiddenInput) {
                    brandHiddenInput = document.createElement('input');
                    brandHiddenInput.type = 'hidden';
                    brandHiddenInput.id = 'brand_name_hidden';
                    brandHiddenInput.name = 'brand_name';
                    document.getElementById('vehicle-form').appendChild(brandHiddenInput);
                }
                brandHiddenInput.value = brandName;
                
                // Also store model_name if provided (will be created after brand is created)
                const modelName = apiData.model || apiData.model_name || apiData.modelName;
                if (modelName) {
                    let hiddenInput = document.getElementById('model_name_hidden');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.id = 'model_name_hidden';
                        hiddenInput.name = 'model_name';
                        document.getElementById('vehicle-form').appendChild(hiddenInput);
                    }
                    hiddenInput.value = modelName;
                }
            }
        }

        // Map fuel type - handle both camelCase and snake_case, and nested objects
        const fuelType = apiData.fuel_type || apiData.fuelType || apiData.fuel || apiData.fuelTypeName || 
                        (apiData.fuel_type && typeof apiData.fuel_type === 'object' ? apiData.fuel_type.name : null);
        if (fuelType) {
            setSelectByText('fuel_type_id', fuelType);
        }

        // Map model year - extract from first_registration_date if model_year is null
        let year = apiData.model_year || apiData.year || apiData.modelYear || apiData.registration_year;
        
        // If model_year is null, try to extract from first_registration_date
        if (!year && apiData.first_registration_date) {
            const dateStr = apiData.first_registration_date;
            const yearMatch = dateStr.match(/^(\d{4})/);
            if (yearMatch) {
                year = yearMatch[1];
            }
        }
        
        console.log('Model year:', year);
        if (year) {
            const yearStr = String(year);
            const yearSelect = document.getElementById('model_year_id');
            
            // First try to find existing option
            if (!setSelectByText('model_year_id', yearStr)) {
                // If not found, store model_year_name for backend to handle
                let hiddenInput = document.getElementById('model_year_name_hidden');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'model_year_name_hidden';
                    hiddenInput.name = 'model_year_name';
                    document.getElementById('vehicle-form').appendChild(hiddenInput);
                }
                hiddenInput.value = yearStr;
            }
        }

        // Map category - handle both camelCase and snake_case
        const categoryName = apiData.category || apiData.vehicleType || apiData.vehicle_type || apiData.category_name;
        if (categoryName) {
            setSelectByText('category_id', categoryName);
        }

        // Numeric fields - handle both camelCase and snake_case
        const price = apiData.price || apiData.price_dkk || apiData.list_price || apiData.priceDkk;
        console.log('Price value:', price);
        if (price) {
            setFieldValue('price', price);
        }
        
        const mileage = apiData.mileage || apiData.km || apiData.odometer || apiData.odometer_reading || apiData.kmDriven;
        console.log('Mileage value:', mileage);
        if (mileage) {
            setFieldValue('mileage', mileage);
            setFieldValue('km_driven', mileage);
        }
        
        const batteryCapacity = apiData.batteryCapacity || apiData.battery_capacity || apiData.battery_kwh;
        if (batteryCapacity) setFieldValue('battery_capacity', batteryCapacity);
        
        const enginePower = apiData.enginePower || apiData.engine_power || apiData.power_hp || apiData.horsepower;
        if (enginePower) setFieldValue('engine_power', enginePower);
        
        const towingWeight = apiData.towingWeight || apiData.towing_weight || apiData.max_towing_weight;
        if (towingWeight) setFieldValue('towing_weight', towingWeight);
        
        const ownershipTax = apiData.ownershipTax || apiData.ownership_tax || apiData.registration_tax;
        if (ownershipTax) setFieldValue('ownership_tax', ownershipTax);
        
        // First registration date - handle multiple formats
        const regDate = apiData.firstRegistrationDate || apiData.first_registration_date || apiData.registration_date || apiData.first_reg_date;
        if (regDate) {
            try {
                const date = new Date(regDate);
                if (!isNaN(date.getTime())) {
                    setFieldValue('first_registration_date', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid date format:', regDate);
            }
        }

        // Map body type - handle both camelCase and snake_case, and nested objects
        let bodyType = null;
        if (apiData.body_type) {
            if (typeof apiData.body_type === 'object' && apiData.body_type.name) {
                bodyType = apiData.body_type.name;
            } else {
                bodyType = apiData.body_type;
            }
        } else {
            bodyType = apiData.bodyType || apiData.body_style || apiData.vehicle_body;
        }
        if (bodyType) {
            setSelectByText('body_type_id', bodyType);
        }

        // Map color - handle both camelCase and snake_case, and nested objects
        let color = null;
        if (apiData.color) {
            if (typeof apiData.color === 'object' && apiData.color.name) {
                color = apiData.color.name;
            } else {
                color = apiData.color;
            }
        } else {
            color = apiData.colour || apiData.paint_color || apiData.exterior_color;
        }
        if (color) {
            setSelectByText('color_id', color);
        }
        
        // Map use - handle nested objects
        let use = null;
        if (apiData.use) {
            if (typeof apiData.use === 'object' && apiData.use.name) {
                use = apiData.use.name;
            } else {
                use = apiData.use;
            }
        }
        if (use) {
            setSelectByText('use_id', use);
        }

        // Additional detailed fields from API - handle both camelCase and snake_case
        setFieldValue('description', apiData.description || apiData.notes || apiData.comments);
        setFieldValue('vin_location', apiData.vin_location || apiData.vinLocation);
        setFieldValue('version', apiData.version);
        setFieldValue('type_name', apiData.type_name || apiData.typeName);
        setFieldValue('engine_displacement', apiData.engine_displacement || apiData.engineDisplacement || apiData.displacement || apiData.displacement_cc);
        setFieldValue('engine_cylinders', apiData.engine_cylinders || apiData.engineCylinders || apiData.cylinders);
        setFieldValue('doors', apiData.doors || apiData.number_of_doors);
        
        const seats = apiData.seats || apiData.number_of_seats || apiData.seating_capacity || apiData.minimum_seats || apiData.maximum_seats;
        if (seats) {
            setFieldValue('minimum_seats', apiData.minimum_seats || seats);
            setFieldValue('maximum_seats', apiData.maximum_seats || seats);
        }
        
        setFieldValue('top_speed', apiData.top_speed || apiData.topSpeed || apiData.max_speed);
        setFieldValue('fuel_efficiency', apiData.fuel_efficiency || apiData.fuelEfficiency || apiData.consumption || apiData.fuel_consumption);
        setFieldValue('airbags', apiData.airbags || apiData.number_of_airbags);
        setFieldValue('total_weight', apiData.total_weight || apiData.totalWeight);
        setFieldValue('vehicle_weight', apiData.vehicle_weight || apiData.vehicleWeight);
        setFieldValue('towing_weight', apiData.towing_weight || apiData.towingWeight);
        
        // Handle ncap_five (boolean)
        if (apiData.ncap_five !== undefined) {
            setFieldValue('ncap_five', apiData.ncap_five ? '1' : '0');
        } else if (apiData.ncapFive !== undefined) {
            setFieldValue('ncap_five', apiData.ncapFive ? '1' : '0');
        }
        
        // Handle equipment array - check equipment checkboxes
        if (apiData.equipment && Array.isArray(apiData.equipment)) {
            apiData.equipment.forEach(function(equip) {
                const equipId = equip.id || equip.equipment_id;
                if (equipId) {
                    const checkbox = document.querySelector(`input[name="equipment_ids[]"][value="${equipId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log(`Checked equipment: ${equipId}`);
                    }
                }
            });
        }
        
        console.log('Form prefilling completed'); // Debug log
    }
</script>
@endpush
@endsection

