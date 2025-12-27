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
        <div id="lookup-loading" class="hidden mt-4 text-sm text-muted-foreground">
            Loading vehicle information...
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

        fetch('/api/v1/nummerplade/vehicle-by-registration', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ registration: registration })
        })
        .then(response => response.json())
        .then(data => {
            lookupLoading.classList.add('hidden');
            lookupBtn.disabled = false;

            if (data.status === 'error' || !data.data) {
                lookupError.textContent = data.message || 'Failed to fetch vehicle information';
                lookupError.classList.add('text-red-600');
                return;
            }

            // Prefill form with API data
            prefillForm(data.data);
            
            // Show form
            vehicleForm.classList.remove('hidden');
            
            // Scroll to form
            vehicleForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(error => {
            lookupLoading.classList.add('hidden');
            lookupBtn.disabled = false;
            lookupError.textContent = 'An error occurred while fetching vehicle information';
            lookupError.classList.add('text-red-600');
            console.error('Lookup error:', error);
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
        // Basic fields
        if (apiData.registration) document.getElementById('registration').value = apiData.registration;
        if (apiData.vin) document.getElementById('vin').value = apiData.vin;
        if (apiData.title || (apiData.make && apiData.model)) {
            document.getElementById('title').value = apiData.title || `${apiData.make || ''} ${apiData.model || ''}`.trim();
        }

        // Map brand
        if (apiData.make || apiData.brand) {
            const brandName = apiData.make || apiData.brand;
            const brandSelect = document.getElementById('brand_id');
            for (let option of brandSelect.options) {
                if (option.text.trim().toLowerCase() === brandName.toLowerCase()) {
                    brandSelect.value = option.value;
                    brandSelect.dispatchEvent(new Event('change'));
                    
                    // Wait for models to load, then select the model
                    setTimeout(() => {
                        if (apiData.model || apiData.modelName) {
                            const modelName = apiData.model || apiData.modelName;
                            const modelSelect = document.getElementById('model_id');
                            
                            // First try to find existing option
                            let found = false;
                            for (let modelOption of modelSelect.options) {
                                if (modelOption.text.trim().toLowerCase() === modelName.toLowerCase()) {
                                    modelSelect.value = modelOption.value;
                                    found = true;
                                    break;
                                }
                            }
                            
                            // If not found, we'll need to create it on the backend
                            // Store model_name for backend to handle
                            if (!found && modelName) {
                                // Create a hidden input to pass model_name to backend
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
                    break;
                }
            }
        }

        // Map fuel type
        if (apiData.fuelType) {
            const fuelSelect = document.getElementById('fuel_type_id');
            for (let option of fuelSelect.options) {
                if (option.text.trim().toLowerCase() === apiData.fuelType.toLowerCase()) {
                    fuelSelect.value = option.value;
                    break;
                }
            }
        }

        // Map model year
        if (apiData.year || apiData.modelYear || apiData.model_year) {
            const year = String(apiData.year || apiData.modelYear || apiData.model_year);
            const yearSelect = document.getElementById('model_year_id');
            
            // First try to find existing option
            let found = false;
            for (let option of yearSelect.options) {
                if (option.text === year) {
                    yearSelect.value = option.value;
                    found = true;
                    break;
                }
            }
            
            // If not found, store model_year_name for backend to handle
            if (!found && year) {
                let hiddenInput = document.getElementById('model_year_name_hidden');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'model_year_name_hidden';
                    hiddenInput.name = 'model_year_name';
                    document.getElementById('vehicle-form').appendChild(hiddenInput);
                }
                hiddenInput.value = year;
            }
        }

        // Map category
        if (apiData.category || apiData.vehicleType) {
            const categoryName = apiData.category || apiData.vehicleType;
            const categorySelect = document.getElementById('category_id');
            for (let option of categorySelect.options) {
                if (option.text.trim().toLowerCase() === categoryName.toLowerCase()) {
                    categorySelect.value = option.value;
                    break;
                }
            }
        }

        // Numeric fields
        if (apiData.price) document.getElementById('price').value = apiData.price;
        if (apiData.mileage) {
            document.getElementById('mileage').value = apiData.mileage;
            document.getElementById('km_driven').value = apiData.mileage;
        }
        if (apiData.batteryCapacity) document.getElementById('battery_capacity').value = apiData.batteryCapacity;
        if (apiData.enginePower) document.getElementById('engine_power').value = apiData.enginePower;
        if (apiData.towingWeight) document.getElementById('towing_weight').value = apiData.towingWeight;
        if (apiData.ownershipTax) document.getElementById('ownership_tax').value = apiData.ownershipTax;
        if (apiData.firstRegistrationDate) {
            const date = new Date(apiData.firstRegistrationDate);
            document.getElementById('first_registration_date').value = date.toISOString().split('T')[0];
        }

        // Map body type
        if (apiData.bodyType) {
            const bodyTypeSelect = document.getElementById('body_type_id');
            for (let option of bodyTypeSelect.options) {
                if (option.text.trim().toLowerCase() === apiData.bodyType.toLowerCase()) {
                    bodyTypeSelect.value = option.value;
                    break;
                }
            }
        }

        // Map color
        if (apiData.color) {
            const colorSelect = document.getElementById('color_id');
            for (let option of colorSelect.options) {
                if (option.text.trim().toLowerCase() === apiData.color.toLowerCase()) {
                    colorSelect.value = option.value;
                    break;
                }
            }
        }

        // Additional detailed fields from API
        if (apiData.description) document.getElementById('description').value = apiData.description;
        if (apiData.engineDisplacement) document.getElementById('engine_displacement').value = apiData.engineDisplacement;
        if (apiData.engineCylinders) document.getElementById('engine_cylinders').value = apiData.engineCylinders;
        if (apiData.doors) document.getElementById('doors').value = apiData.doors;
        if (apiData.seats) {
            document.getElementById('minimum_seats').value = apiData.seats;
            document.getElementById('maximum_seats').value = apiData.seats;
        }
        if (apiData.topSpeed) document.getElementById('top_speed').value = apiData.topSpeed;
        if (apiData.fuelEfficiency) document.getElementById('fuel_efficiency').value = apiData.fuelEfficiency;
        if (apiData.airbags) document.getElementById('airbags').value = apiData.airbags;
        if (apiData.ncapFive !== undefined) {
            document.getElementById('ncap_five').value = apiData.ncapFive ? '1' : '0';
        }
    }
</script>
@endpush
@endsection

