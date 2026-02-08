@extends('layouts.app')

@section('title', 'Edit Vehicle - Bilskyen')

@push('styles')
<style>
    /* All styles from sell-your-car.blade.php - copy the entire style section */
    /* Expandable Section Styles */
    .expandable-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        margin-bottom: 3rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .expandable-section:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.05);
    }
    
    .dark .expandable-section:hover {
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .section-header {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--muted);
        transition: background-color 0.2s;
        user-select: none;
    }
    
    .section-header:hover {
        background: var(--accent);
    }
    
    .section-header.active {
        background: var(--muted);
        color: var(--foreground);
    }
    
    .section-title-group {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        flex: 1;
    }
    
    .section-number {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--background);
        color: var(--foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.625rem;
        flex-shrink: 0;
    }
    
    .section-header.active .section-number {
        background: var(--background);
        color: var(--foreground);
    }
    
    .section-title {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--foreground);
        line-height: 1.2;
    }
    
    .section-header.active .section-title {
        color: var(--foreground);
    }
    
    .section-subtitle {
        font-size: 0.625rem;
        color: var(--muted-foreground);
        margin-top: 0.125rem;
        line-height: 1.2;
    }
    
    .section-header.active .section-subtitle {
        color: var(--muted-foreground);
        opacity: 1;
    }
    
    .section-icon {
        width: 16px;
        height: 16px;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }
    
    .section-header.active .section-icon {
        transform: rotate(180deg);
    }
    
    .section-content {
        max-height: 5000px;
        overflow: hidden;
        transition: max-height 0.4s ease, padding 0.3s ease;
        padding: 1rem;
    }
    
    .section-content.expanded {
        max-height: 5000px;
        padding: 1rem;
    }
    
    .section-content.collapsed {
        max-height: 0;
        padding: 0 1rem;
    }
    
    .section-description {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }
    
    /* Required Field Indicator */
    .required-field::after {
        content: ' *';
        color: var(--destructive);
    }
    
    /* Field Help Text */
    .field-help {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }
    
    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.875rem;
    }
    
    @media (min-width: 640px) {
        .form-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (min-width: 1024px) {
        .form-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    /* Month/Year Field Pair */
    .field-pair-inner {
        display: flex;
        gap: 0.5rem;
    }
    
    .field-pair-inner > div {
        flex: 1;
    }
    
    @media (max-width: 640px) {
        .field-pair-inner {
            flex-direction: column;
        }
    }
    
    /* Submit Section */
    .submit-section {
        background: var(--muted);
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-top: 1.25rem;
        text-align: center;
    }
    
    .submit-section h3 {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .submit-section p {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-bottom: 1rem;
    }
    
    .btn-submit {
        padding: 0.75rem 1.5rem;
        font-size: 0.875rem;
        background: var(--primary);
        color: var(--primary-foreground);
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        border: none;
    }
    
    .btn-submit:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.15);
    }
    
    .dark .btn-submit:hover:not(:disabled) {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.4);
    }
    
    /* Field Error */
    .field-error {
        font-size: 0.75rem;
        color: var(--destructive);
        margin-top: 0.25rem;
    }
    
    /* Equipment Section Styles */
    .equipment-type-group {
        margin-bottom: 0.5rem;
    }
    
    .equipment-type-toggle {
        cursor: pointer;
    }
    
    .equipment-type-icon {
        transition: transform 0.2s ease;
    }
    
    .equipment-type-icon.rotate-180 {
        transform: rotate(180deg);
    }
    
    .equipment-type-content {
        transition: all 0.3s ease;
    }
    
    /* Image Upload Styles */
    .image-upload-area {
        margin-bottom: 1rem;
    }
    
    .image-upload-area.has-images .upload-dropzone {
        padding: 1rem;
        border-style: solid;
    }
    
    .image-upload-area.has-images .upload-content {
        flex-direction: row;
        gap: 0.75rem;
    }
    
    .image-upload-area.has-images .upload-icon {
        width: 24px;
        height: 24px;
    }
    
    .image-upload-area.has-images .upload-text {
        font-size: 0.75rem;
        margin: 0;
    }
    
    .image-upload-area.has-images .upload-hint {
        display: none;
    }
    
    .image-input {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
        overflow: hidden;
    }
    
    .upload-dropzone {
        border: 2px dashed var(--border);
        border-radius: 0.5rem;
        padding: 2rem 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--muted);
    }
    
    .upload-dropzone:hover {
        border-color: var(--primary);
        background: var(--accent);
    }
    
    .upload-dropzone.drag-over {
        border-color: var(--primary);
        background: var(--primary);
        color: var(--primary-foreground);
    }
    
    .upload-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .upload-icon {
        color: var(--muted-foreground);
        transition: color 0.3s ease;
    }
    
    .upload-dropzone:hover .upload-icon,
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary);
    }
    
    .upload-dropzone.drag-over .upload-icon {
        color: var(--primary-foreground);
    }
    
    .upload-text {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--foreground);
    }
    
    .upload-hint {
        font-size: 0.75rem;
        color: var(--muted-foreground);
    }
    
    .upload-dropzone.drag-over .upload-text,
    .upload-dropzone.drag-over .upload-hint {
        color: var(--primary-foreground);
    }
    
    /* Image Preview Container */
    .image-preview-container {
        margin-top: 1rem;
    }
    
    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.75rem;
    }
    
    @media (min-width: 640px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
    }
    
    @media (min-width: 1024px) {
        .image-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        }
    }
    
    .image-preview-item {
        position: relative;
        aspect-ratio: 4 / 3;
        border-radius: 0.5rem;
        overflow: hidden;
        border: 2px solid var(--border);
        background: var(--muted);
        transition: all 0.2s ease;
        cursor: move;
        cursor: grab;
    }
    
    .image-preview-item:active {
        cursor: grabbing;
    }
    
    .image-preview-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.1);
    }
    
    .dark .image-preview-item:hover {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.3);
    }
    
    .image-preview-item.dragging {
        opacity: 0.5;
        transform: scale(0.95);
        cursor: grabbing;
        z-index: 1000;
    }
    
    .image-preview-item.drag-over {
        border-color: var(--primary);
        border-width: 3px;
        transform: scale(1.05);
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.15);
    }
    
    .dark .image-preview-item.drag-over {
        box-shadow: 0 6px 16px oklch(0 0 0 / 0.4);
    }
    
    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    .image-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0, 74, 173, 0.6) 100%);
        opacity: 0;
        transition: opacity 0.2s ease;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        padding: 0.5rem;
    }
    
    .image-preview-item:hover .image-preview-overlay {
        opacity: 1;
    }
    
    .image-remove-btn {
        background: var(--destructive);
        color: white;
        border: none;
        border-radius: 0.375rem;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px oklch(0 0 0 / 0.2);
    }
    
    .image-remove-btn:hover {
        background: oklch(0.5 0.25 27);
        transform: scale(1.1);
    }
    
    /* Servicebog Radio Buttons */
    .servicebog-radio {
        transition: all 0.2s ease;
    }
    
    .servicebog-radio:hover {
        background: var(--accent);
        border-color: var(--primary);
    }
    
    .servicebog-radio input[type="radio"]:checked + span {
        font-weight: 600;
        color: var(--primary);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) {
        background: var(--primary);
        border-color: var(--primary);
        color: var(--primary-foreground);
    }
    
    .servicebog-radio:has(input[type="radio"]:checked) span {
        color: var(--primary-foreground);
        font-weight: 600;
    }
    
    /* Error Container */
    .error-container {
        border-color: oklch(0.8 0.2 27) !important;
        background: oklch(0.95 0.1 27) !important;
        color: oklch(0.4 0.2 27) !important;
    }
    
    .dark .error-container {
        border-color: oklch(0.6 0.2 27) !important;
        background: oklch(0.3 0.1 27) !important;
        color: oklch(0.7 0.2 27) !important;
    }
    
    /* Location Autocomplete Styles */
    .location-autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--background);
        border: 1px solid var(--border);
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        margin-top: 0.25rem;
    }
    
    .location-autocomplete-dropdown.show {
        display: block;
    }
    
    .location-autocomplete-item {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
        border-bottom: 1px solid var(--border);
    }
    
    .location-autocomplete-item:last-child {
        border-bottom: none;
    }
    
    .location-autocomplete-item:hover,
    .location-autocomplete-item.active {
        background: var(--accent);
    }
    
    .location-autocomplete-item-text {
        font-size: 0.875rem;
        color: var(--foreground);
    }
</style>
@endpush

@section('content')
<div class="container py-3 md:py-6">
    <div class="mb-6">
        <a href="{{ route('seller.dashboard', ['token' => $token]) }}" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="m12 19-7-7 7-7"></path>
                <path d="M19 12H5"></path>
            </svg>
            Back to Dashboard
        </a>
        <h1 class="text-2xl font-bold tracking-tight mb-2">
            Edit Vehicle Listing
        </h1>
        <p class="text-muted-foreground">
            Update your vehicle information below. All fields are visible and editable.
        </p>
    </div>

    @if($errors->any())
        <div class="w-full rounded-md border p-3 mb-4 error-container">
            <p class="text-sm font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Vehicle Form -->
    <form id="vehicle-form" data-action="{{ route('seller.vehicle.update', ['token' => $token, 'id' => $vehicle->id]) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

        <!-- Error Display Container -->
        <div id="form-errors-top" class="hidden w-full rounded-md border p-3 mb-4 error-container"></div>

        <!-- Section 1: Basic Vehicle Information -->
        <div class="expandable-section" data-section="basic-info">
            <div class="section-header active" onclick="toggleSection('basic-info')">
                <div class="section-title-group">
                    <div class="section-number">1</div>
                    <div>
                        <div class="section-title">Basic Vehicle Information</div>
                        <div class="section-subtitle">Title, variant, and color</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Basic information about your vehicle.
                </div>
                
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="title" class="text-sm font-medium required-field">Title</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $vehicle->title) }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Vehicle title">
                        <p class="field-help">Vehicle title</p>
                    </div>

                    <div class="space-y-2">
                        <label for="variant_id" class="text-sm font-medium">Variant</label>
                        <select id="variant_id" name="variant_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Variant</option>
                            @foreach($lookupData['variants'] as $variant)
                                <option value="{{ $variant->id }}" {{ $vehicle->details && $vehicle->details->variant_id == $variant->id ? 'selected' : '' }}>
                                    {{ $variant->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-help">Vehicle variant/trim level</p>
                    </div>

                    <div class="space-y-2">
                        <label for="color_id" class="text-sm font-medium">Color</label>
                        <select id="color_id" name="color_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Color</option>
                            @foreach($lookupData['colors'] as $color)
                                <option value="{{ $color->id }}" {{ $vehicle->details && $vehicle->details->color_id == $color->id ? 'selected' : '' }}>
                                    {{ $color->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-help">Vehicle exterior color</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Vehicle Specifications -->
        <div class="expandable-section" data-section="specifications">
            <div class="section-header active" onclick="toggleSection('specifications')">
                <div class="section-title-group">
                    <div class="section-number">2</div>
                    <div>
                        <div class="section-title">Vehicle Specifications</div>
                        <div class="section-subtitle">Kilometer driven, registration, inspection, and technical details</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Technical specifications and registration details.
                </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium required-field">Kilometer Driven</label>
                        <input type="number" id="km_driven" name="km_driven" min="0" required
                            value="{{ old('km_driven', $vehicle->km_driven) }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        <p class="field-help">How far car has driven</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">First Registration</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="first_registration_month" name="first_registration_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Month</option>
                                    @php
                                        $firstRegDate = $vehicle->first_registration_date ? \Carbon\Carbon::parse($vehicle->first_registration_date) : null;
                                        $firstRegMonth = $firstRegDate ? $firstRegDate->month : null;
                                    @endphp
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $firstRegMonth == $i ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="first_registration_year" name="first_registration_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Year</option>
                                    @php
                                        $firstRegYear = $firstRegDate ? $firstRegDate->year : null;
                                    @endphp
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}" {{ $firstRegYear == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">Month and year of first registration</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Last Inspection</label>
                        <div class="field-pair-inner">
                            <div>
                                <select id="last_inspection_month" name="last_inspection_month"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Month</option>
                                    @php
                                        $lastInspectionDate = $vehicle->details && $vehicle->details->last_inspection_date ? \Carbon\Carbon::parse($vehicle->details->last_inspection_date) : null;
                                        $lastInspectionMonth = $lastInspectionDate ? $lastInspectionDate->month : null;
                                    @endphp
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $lastInspectionMonth == $i ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <select id="last_inspection_year" name="last_inspection_year"
                                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Select Year</option>
                                    @php
                                        $lastInspectionYear = $lastInspectionDate ? $lastInspectionDate->year : null;
                                    @endphp
                                    @for($i = date('Y'); $i >= 1900; $i--)
                                        <option value="{{ $i }}" {{ $lastInspectionYear == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <p class="field-help">Month and year of last inspection</p>
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_efficiency" id="fuel_efficiency_label" class="text-sm font-medium">KM/L</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" min="0" step="any" inputmode="decimal"
                            value="{{ old('fuel_efficiency', $vehicle->fuel_efficiency) }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                        <p class="field-help" id="fuel_efficiency_help">Fuel efficiency in kilometers per liter</p>
                    </div>

                    <div class="space-y-2">
                        <label for="technical_total_weight" class="text-sm font-medium">Total Technical Weight (kg)</label>
                        <input type="number" id="technical_total_weight" name="technical_total_weight" min="0"
                            value="{{ old('technical_total_weight', $vehicle->details ? $vehicle->details->technical_total_weight : '') }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        <p class="field-help">Total technical weight in kg</p>
                    </div>

                    <div class="space-y-2">
                        <label for="euronom_id" class="text-sm font-medium">Euronom</label>
                        <select id="euronom_id" name="euronom_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Euronom</option>
                            @foreach($lookupData['euronorms'] as $euronom)
                                <option value="{{ $euronom->id }}" {{ $vehicle->details && $vehicle->details->euronom_id == $euronom->id ? 'selected' : '' }}>
                                    {{ $euronom->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-help">Euro emission standard</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Equipment & Features -->
        <div class="expandable-section" data-section="equipment">
            <div class="section-header active" onclick="toggleSection('equipment')">
                <div class="section-title-group">
                    <div class="section-number">3</div>
                    <div>
                        <div class="section-title">Equipment & Features</div>
                        <div class="section-subtitle">Select the equipment your vehicle has</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Select the equipment and features your vehicle has. This helps buyers find exactly what they're looking for.
                </div>
                
                <!-- Equipment by Category -->
                <div class="space-y-4">
                    @php
                        $selectedEquipmentIds = $vehicle->equipment->pluck('id')->toArray();
                    @endphp
                    @foreach($lookupData['equipmentTypes'] as $equipmentType)
                        @if($equipmentType->equipments->count() > 0)
                            <div class="equipment-type-group">
                                <h4 class="text-xs font-medium uppercase tracking-wide mb-3 text-muted-foreground">{{ $equipmentType->name }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($equipmentType->equipments as $equipment)
                                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                            <input 
                                                type="checkbox" 
                                                name="equipment_ids[]" 
                                                value="{{ $equipment->id }}"
                                                {{ in_array($equipment->id, $selectedEquipmentIds) ? 'checked' : '' }}
                                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            >
                                            <span>{{ $equipment->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    <!-- Equipment without category -->
                    @php
                        $equipmentWithoutType = $lookupData['equipment']->filter(function($equip) {
                            return !$equip->equipment_type_id;
                        });
                    @endphp
                    @if($equipmentWithoutType->count() > 0)
                        <div class="equipment-type-group">
                            <h4 class="text-sm font-semibold uppercase tracking-wide mb-3 text-foreground">Other</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($equipmentWithoutType as $equipment)
                                    <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                        <input 
                                            type="checkbox" 
                                            name="equipment_ids[]" 
                                            value="{{ $equipment->id }}"
                                            {{ in_array($equipment->id, $selectedEquipmentIds) ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        >
                                        <span>{{ $equipment->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Servicebog -->
        <div class="expandable-section" data-section="servicebog">
            <div class="section-header active" onclick="toggleSection('servicebog')">
                <div class="section-title-group">
                    <div class="section-number">3.5</div>
                    <div>
                        <div class="section-title">Servicebog</div>
                        <div class="section-subtitle">Service book information</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="mb-4">
                    <label class="text-sm font-medium mb-2 block">Servicebog</label>
                    <div class="flex gap-2 md:gap-3">
                        @php
                            $servicebog = $vehicle->details ? $vehicle->details->servicebog : 'Default';
                        @endphp
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Yes" class="h-3 w-3 md:h-4 md:w-4 text-primary" {{ $servicebog == 'Yes' ? 'checked' : '' }}>
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="No" class="h-3 w-3 md:h-4 md:w-4 text-primary" {{ $servicebog == 'No' ? 'checked' : '' }}>
                            <span>No</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 md:gap-2 px-2 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium cursor-pointer transition-all hover:bg-accent border border-input servicebog-radio">
                            <input type="radio" name="servicebog" value="Default" class="h-3 w-3 md:h-4 md:w-4 text-primary" {{ $servicebog == 'Default' || !$servicebog ? 'checked' : '' }}>
                            <span>Default</span>
                        </label>
                    </div>
                    <p class="field-help mt-2">Does the vehicle have a service book?</p>
                </div>
            </div>
        </div>

        <!-- Section 4: Pricing & Tax -->
        <div class="expandable-section" data-section="pricing">
            <div class="section-header active" onclick="toggleSection('pricing')">
                <div class="section-title-group">
                    <div class="section-number">4</div>
                    <div>
                        <div class="section-title">Pricing & Tax</div>
                        <div class="section-subtitle">Price and tax information</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium required-field">Price (DKK)</label>
                        <input type="number" id="price" name="price" required min="0"
                            value="{{ old('price', $vehicle->price) }}"
                            class="flex h-9 w-full rounded-md border {{ $errors->has('price') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        @error('price')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                        <p class="field-help">Selling price in DKK</p>
                    </div>
                </div>

                <!-- Expandable Tax Information Section -->
                <div class="mt-4 border border-input rounded-lg overflow-hidden">
                    <button type="button" class="equipment-type-toggle w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-foreground hover:bg-accent transition-colors"
                        onclick="toggleTaxInfo()">
                        <span>Tax Information Based on Mileage</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon transition-transform" id="tax-info-icon">
                            <path d="m6 9 6 6 6-6"></path>
                        </svg>
                    </button>
                    <div id="tax-info-content" class="equipment-type-content hidden px-4 pb-3 pt-2">
                        <p class="text-sm text-muted-foreground">Tax information based on mileage - To be implemented after consulting with Berken.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Media -->
        <div class="expandable-section" data-section="photos">
            <div class="section-header active" onclick="toggleSection('photos')">
                <div class="section-title-group">
                    <div class="section-number">5</div>
                    <div>
                        <div class="section-title">Photos</div>
                        <div class="section-subtitle">Add or edit photos of your vehicle</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="section-description">
                    Manage photos of your vehicle. Good photos help your listing sell faster! You can add new images, remove existing ones, or reorder them by dragging.
                </div>
                
                <!-- Image Upload Area -->
                <div class="image-upload-area" id="image-upload-area">
                    <input 
                        type="file" 
                        id="images" 
                        name="images[]" 
                        multiple 
                        accept="image/*"
                        class="image-input"
                    >
                    <div class="upload-dropzone" id="upload-dropzone">
                        <div class="upload-content">
                            <svg class="upload-icon" xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="upload-text">Click to upload or drag and drop</p>
                            <p class="upload-hint">PNG, JPG, GIF up to 20MB each</p>
                        </div>
                    </div>
                </div>
                
                <!-- Image Preview Grid -->
                <div id="image-preview-container" class="image-preview-container {{ $vehicle->images->count() > 0 ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold">
                            Images (<span id="image-count">{{ $vehicle->images->count() }}</span>)
                        </h4>
                        <button type="button" onclick="clearAllImages()" class="text-xs text-muted-foreground hover:text-foreground">
                            Clear All
                        </button>
                    </div>
                    <div id="image-preview-grid" class="image-preview-grid">
                        <!-- Existing images will be inserted here by JavaScript -->
                        @foreach($vehicle->images as $image)
                            <div class="image-preview-item" data-image-id="{{ $image->id }}" data-sort-order="{{ $image->sort_order }}" draggable="true">
                                <img src="{{ asset('storage/' . $image->image_path) }}" alt="Vehicle image">
                                <div class="image-preview-overlay">
                                    <button type="button" class="image-remove-btn" onclick="removeExistingImage({{ $image->id }})" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6L6 18M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 6: Description -->
        <div class="expandable-section" data-section="description">
            <div class="section-header active" onclick="toggleSection('description')">
                <div class="section-title-group">
                    <div class="section-number">6</div>
                    <div>
                        <div class="section-title">Description</div>
                        <div class="section-subtitle">Vehicle description</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="space-y-2">
                    <label for="description" class="text-sm font-medium">Description</label>
                    <textarea id="description" name="description" rows="6"
                        class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Enter vehicle description...">{{ old('description', $vehicle->details ? $vehicle->details->description : '') }}</textarea>
                    <p class="field-help">Describe your vehicle</p>
                </div>
            </div>
        </div>

        <!-- Section 7: Seller Information -->
        <div class="expandable-section" data-section="seller-info">
            <div class="section-header active" onclick="toggleSection('seller-info')">
                <div class="section-title-group">
                    <div class="section-number">7</div>
                    <div>
                        <div class="section-title">Seller Information</div>
                        <div class="section-subtitle">Your contact details</div>
                    </div>
                </div>
            </div>
            <div class="section-content expanded">
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="seller_phone" class="text-sm font-medium">Phone</label>
                        <input type="text" id="seller_phone" name="seller_phone" 
                            value="{{ old('seller_phone', $vehicle->details ? $vehicle->details->seller_phone : ($user->phone ?? '')) }}"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Your phone number">
                        <p class="field-help">Your contact phone number</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_address" class="text-sm font-medium">Location</label>
                        <div class="relative">
                            <input type="text" id="seller_address" name="seller_address" 
                                value="{{ old('seller_address', $vehicle->seller_address ?? ($user->address ?? '')) }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Your address">
                            <div id="location-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">Your location</p>
                    </div>

                    <div class="space-y-2">
                        <label for="seller_postcode" class="text-sm font-medium">Postal Code</label>
                        <div class="relative">
                            <input type="text" id="seller_postcode" name="seller_postcode" 
                                value="{{ old('seller_postcode', $vehicle->seller_postcode ?? ($user->postcode ?? '')) }}"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Postal code">
                            <div id="postcode-autocomplete" class="location-autocomplete-dropdown"></div>
                        </div>
                        <p class="field-help">Your postal code</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function toggleTaxInfo() {
            const content = document.getElementById('tax-info-content');
            const icon = document.getElementById('tax-info-icon');
            if (content && icon) {
                content.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        }
        </script>

        <!-- Submit Section -->
        <div class="submit-section">
            <h3>Ready to update your listing?</h3>
            <p>Review your information and click the button below to update your vehicle listing.</p>
            <button type="submit" class="btn btn-submit">
                Update Vehicle Listing
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    window.locationsData = @json($lookupData['locations'] ?? []);
    window.vehicleId = {{ $vehicle->id }};
    window.existingImages = @json($vehicle->images->map(function($img) {
        return [
            'id' => $img->id,
            'url' => asset('storage/' . $img->image_path),
            'sort_order' => $img->sort_order
        ];
    }));
</script>
<script src="{{ asset('js/seller-vehicle-edit-form.js') }}"></script>
@endpush
@endsection
