@extends('layouts.app')

@section('title', 'Sell Your Car - Bilskyen')

@push('styles')
<style>
    /* Expandable Section Styles */
    .expandable-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        margin-bottom: 1rem;
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
        padding: 0.875rem 1rem;
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
        background: var(--primary);
        color: var(--primary-foreground);
    }
    
    .section-title-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
    }
    
    .section-number {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--background);
        color: var(--foreground);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.75rem;
        flex-shrink: 0;
    }
    
    .section-header.active .section-number {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--foreground);
    }
    
    .section-header.active .section-title {
        color: var(--primary-foreground);
    }
    
    .section-subtitle {
        font-size: 0.75rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }
    
    .section-header.active .section-subtitle {
        color: var(--primary-foreground);
        opacity: 0.9;
    }
    
    .section-icon {
        width: 20px;
        height: 20px;
        transition: transform 0.3s ease;
        flex-shrink: 0;
    }
    
    .section-header.active .section-icon {
        transform: rotate(180deg);
    }
    
    .section-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s ease, padding 0.3s ease;
        padding: 0 1rem;
    }
    
    .section-content.expanded {
        max-height: 5000px;
        padding: 1rem;
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
    
    /* License Plate Lookup Section */
    .lookup-section {
        background: linear-gradient(135deg, oklch(0.205 0 0) 0%, oklch(0.205 0 0) 100%);
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        color: oklch(0.985 0 0);
    }
    
    .dark .lookup-section {
        background: linear-gradient(135deg, oklch(0.205 0 0) 0%, oklch(0.205 0 0) 100%);
        color: oklch(0.985 0 0);
    }
    
    .lookup-section h2 {
        color: oklch(0.985 0 0);
        margin-bottom: 0.5rem;
    }
    
    .lookup-section p {
        color: oklch(0.985 0 0);
        opacity: 0.9;
        margin-bottom: 1rem;
    }
    
    .lookup-input-group {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    
    .lookup-input-wrapper {
        flex: 1;
        min-width: 200px;
    }
    
    .lookup-input-wrapper input {
        background: var(--background);
        color: var(--foreground);
        border: 2px solid transparent;
    }
    
    .lookup-input-wrapper input:focus {
        border-color: oklch(0.985 0 0);
        background: var(--background);
    }
    
    .btn {
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    
    .btn-primary {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn-primary:hover:not(:disabled) {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    .btn-secondary {
        background: var(--background);
        color: var(--primary-foreground);
        border: 1px solid var(--primary-foreground);
    }
    
    .btn-secondary:hover:not(:disabled) {
        background: var(--primary-foreground);
        color: var(--primary);
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Success Badge */
    .success-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: oklch(0.95 0.1 145);
        border: 1px solid oklch(0.8 0.15 145);
        border-radius: 0.5rem;
        font-size: 0.75rem;
        color: oklch(0.4 0.2 145);
        margin-bottom: 0.75rem;
    }
    
    .dark .success-badge {
        background: oklch(0.3 0.1 145);
        border-color: oklch(0.5 0.15 145);
        color: oklch(0.7 0.2 145);
    }
    
    /* Loading State */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: oklch(0 0 0 / 0.3);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dark .loading-overlay {
        background: oklch(0 0 0 / 0.5);
    }
    
    .loading-content {
        background: var(--card);
        padding: 1.25rem;
        border-radius: 0.5rem;
        text-align: center;
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.15);
    }
    
    .dark .loading-content {
        box-shadow: 0 10px 25px oklch(0 0 0 / 0.4);
    }
    
    /* Submit Section */
    .submit-section {
        background: var(--muted);
        border: 1px solid var(--border);
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
    
    /* Expand All Button */
    .expand-controls {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    
    .expand-btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        background: var(--muted);
        color: var(--foreground);
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .expand-btn:hover {
        background: var(--accent);
    }
    
    /* Mobile Responsive */
    @media (max-width: 640px) {
        .lookup-section {
            padding: 1rem;
        }
        
        .lookup-input-group {
            flex-direction: column;
        }
        
        .lookup-input-wrapper {
            min-width: 100%;
        }
        
        .section-header {
            padding: 0.75rem;
        }
        
        .section-content.expanded {
            padding: 0.75rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .expand-controls {
            justify-content: stretch;
        }
        
        .expand-btn {
            flex: 1;
        }
    }
    
    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
    
    /* Hide form initially */
    .form-hidden {
        display: none;
    }
    
    .form-visible {
        display: block;
        animation: fadeInForm 0.5s ease-in;
    }
    
    @keyframes fadeInForm {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Equipment Section Styles - Matching vehicles.blade.php */
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
    }
    
    .image-preview-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.1);
    }
    
    .dark .image-preview-item:hover {
        box-shadow: 0 4px 12px oklch(0 0 0 / 0.3);
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
        background: linear-gradient(to bottom, transparent 0%, oklch(0 0 0 / 0.6) 100%);
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
    
    .image-preview-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: oklch(0 0 0 / 0.7);
        color: white;
        padding: 0.5rem;
        font-size: 0.75rem;
        text-align: center;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .image-preview-item:hover .image-preview-info {
        opacity: 1;
    }
    
    .image-preview-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--muted-foreground);
    }
    
    /* Error Container - adapts to dark/light mode */
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
    
    /* Override Tailwind red border classes for dark mode */
    .dark .border-red-500 {
        border-color: oklch(0.6 0.25 27) !important;
    }
    
    .dark .border-red-200 {
        border-color: oklch(0.5 0.15 27) !important;
    }
    
    .dark .bg-red-50 {
        background-color: oklch(0.3 0.1 27) !important;
    }
    
    .dark .text-red-800 {
        color: oklch(0.7 0.2 27) !important;
    }
</style>
@endpush

@section('content')
<div class="container py-3 md:py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight mb-2">
            Sell your car on Denmark's largest car market
        </h1>
        <p class="text-muted-foreground max-w-2xl">
            Enter your car's license plate and we'll help you with the rest. All fields are visible - expand sections to add more details.
        </p>
    </div>

    @if(session('success'))
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.15 145); background: oklch(0.95 0.1 145); color: oklch(0.4 0.2 145);">
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.5 0.15 145) !important;
                background: oklch(0.3 0.1 145) !important;
                color: oklch(0.7 0.2 145) !important;
            }
        </style>
    @endif

    @if($errors->any())
        <div class="w-full rounded-md border p-3 mb-4" style="border-color: oklch(0.8 0.2 27); background: oklch(0.95 0.1 27); color: oklch(0.4 0.2 27);">
            <p class="text-sm font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <style>
            .dark .w-full.rounded-md.border.p-3.mb-4 {
                border-color: oklch(0.6 0.2 27) !important;
                background: oklch(0.3 0.1 27) !important;
                color: oklch(0.7 0.2 27) !important;
            }
        </style>
    @endif

    <!-- License Plate Lookup Section -->
    <div class="lookup-section">
        <h2 class="text-lg font-semibold">Find Your Vehicle</h2>
        <p>Enter your car's license plate number and press Enter. We'll automatically fill in the vehicle information for you.</p>
        
        <div class="lookup-input-group">
            <div class="lookup-input-wrapper">
                <label for="registration-lookup" class="block text-sm font-medium mb-2" style="color: oklch(0.985 0 0);">
                    License Plate Number
                </label>
                <input
                    type="text"
                    id="registration-lookup"
                    placeholder="e.g., AB12345 (Press Enter to search)"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                <p class="text-xs mt-1" id="lookup-error" style="color: oklch(0.985 0 0); opacity: 0.8;"></p>
            </div>
            </div>
        
        <div id="lookup-loading" class="hidden mt-4">
            <div class="flex items-center gap-2 text-sm" style="color: oklch(0.985 0 0); opacity: 0.9;">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading vehicle information...</span>
            </div>
        </div>
    </div>

    <!-- Vehicle Form -->
    <form id="vehicle-form" data-action="{{ route('sell-your-car.store') }}" enctype="multipart/form-data" class="form-hidden">
        @csrf

        <!-- Error Display Container -->
        <div id="form-errors-top" class="hidden w-full rounded-md border p-3 mb-4 error-container"></div>

        <!-- Expand Controls -->
        <div class="expand-controls">
            <button type="button" class="expand-btn" onclick="expandAllSections()">Expand All</button>
            <button type="button" class="expand-btn" onclick="collapseAllSections()">Collapse All</button>
        </div>

        <!-- Section 1: Essential Information -->
        <div class="expandable-section" data-section="essential">
            <div class="section-header" onclick="toggleSection('essential')">
                <div class="section-title-group">
                    <div class="section-number">1</div>
                    <div>
                        <div class="section-title">Essential Information</div>
                        <div class="section-subtitle">Required fields to list your vehicle</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Please provide the basic details about your vehicle. Fields marked with * are required.
                </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="title" class="text-sm font-medium required-field">Title</label>
                        <input type="text" id="title" name="title" required
                            class="flex h-9 w-full rounded-md border {{ $errors->has('title') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="e.g., 2020 Tesla Model 3">
                        @error('title')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="registration" class="text-sm font-medium required-field">Registration</label>
                        <input type="text" id="registration" name="registration" required
                            class="flex h-9 w-full rounded-md border {{ $errors->has('registration') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="License plate number">
                        @error('registration')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="price" class="text-sm font-medium required-field">Price (DKK)</label>
                        <input type="number" id="price" name="price" required min="0"
                            class="flex h-9 w-full rounded-md border {{ $errors->has('price') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm"
                            placeholder="0">
                        @error('price')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="location_id" class="text-sm font-medium required-field">Location</label>
                        <select id="location_id" name="location_id" required
                            class="flex h-9 w-full rounded-md border {{ $errors->has('location_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Location</option>
                            @foreach($lookupData['locations'] as $location)
                                <option value="{{ $location->id }}">
                                    {{ $location->city }}, {{ $location->postcode }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_type_id" class="text-sm font-medium required-field">Fuel Type</label>
                        <select id="fuel_type_id" name="fuel_type_id" required
                            class="flex h-9 w-full rounded-md border {{ $errors->has('fuel_type_id') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm">
                            <option value="">Select Fuel Type</option>
                            @foreach($lookupData['fuelTypes'] as $fuelType)
                                <option value="{{ $fuelType->id }}">
                                    {{ $fuelType->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('fuel_type_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="listing_type_id" class="text-sm font-medium">Listing Type</label>
                        <select id="listing_type_id" name="listing_type_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Type</option>
                            @foreach($lookupData['listingTypes'] as $type)
                                <option value="{{ $type->id }}" {{ $type->name === 'Purchase' ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="field-help">
                            <strong>Purchase:</strong> You are selling the vehicle directly. 
                            <strong>Leasing:</strong> You are offering the vehicle for lease/rental.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Vehicle Details -->
        <div class="expandable-section" data-section="details">
            <div class="section-header" onclick="toggleSection('details')">
                <div class="section-title-group">
                    <div class="section-number">2</div>
                    <div>
                        <div class="section-title">Vehicle Details</div>
                        <div class="section-subtitle">Brand, model, year, and specifications</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Help buyers learn more about your vehicle. All fields are optional but the more information you provide, the better.
                    </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="brand_id" class="text-sm font-medium">Brand</label>
                        <select id="brand_id" name="brand_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
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
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Model</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="model_year_id" class="text-sm font-medium">Model Year</label>
                        <select id="model_year_id" name="model_year_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Year</option>
                            @foreach($lookupData['modelYears'] as $year)
                                <option value="{{ $year->id }}">
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="category_id" class="text-sm font-medium">Category</label>
                        <select id="category_id" name="category_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Category</option>
                            @foreach($lookupData['categories'] as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="mileage" class="text-sm font-medium">Mileage (km)</label>
                        <input type="number" id="mileage" name="mileage" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                    </div>

                    <div class="space-y-2">
                        <label for="km_driven" class="text-sm font-medium">Kilometers Driven</label>
                        <input type="number" id="km_driven" name="km_driven" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0">
                    </div>

                    <div class="space-y-2">
                        <label for="vin" class="text-sm font-medium">VIN</label>
                        <input type="text" id="vin" name="vin"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Vehicle identification number">
                    </div>

                    <div class="space-y-2">
                        <label for="vin_location" class="text-sm font-medium">VIN Location</label>
                        <input type="text" id="vin_location" name="vin_location"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Where the VIN is located on the vehicle">
                    </div>

                    <div class="space-y-2">
                        <label for="version" class="text-sm font-medium">Version</label>
                        <input type="text" id="version" name="version"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Vehicle version">
                    </div>

                    <div class="space-y-2">
                        <label for="type_name" class="text-sm font-medium">Type Name</label>
                        <input type="text" id="type_name" name="type_name"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Type name">
                    </div>

                    <div class="space-y-2">
                        <label for="category" class="text-sm font-medium">Category (Text)</label>
                        <input type="text" id="category" name="category"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Vehicle category">
                    </div>

                    <div class="space-y-2">
                        <label for="first_registration_date" class="text-sm font-medium">First Registration Date</label>
                        <input type="date" id="first_registration_date" name="first_registration_date"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="description" class="text-sm font-medium">Description</label>
                        <textarea id="description" name="description" rows="4"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Tell potential buyers about your vehicle..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Technical Specifications -->
        <div class="expandable-section" data-section="technical">
            <div class="section-header" onclick="toggleSection('technical')">
                <div class="section-title-group">
                    <div class="section-number">3</div>
                    <div>
                        <div class="section-title">Technical Specifications</div>
                        <div class="section-subtitle">Engine, power, battery, and performance</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Technical details about your vehicle's performance and specifications.
                </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="engine_power" class="text-sm font-medium">Engine Power (HP)</label>
                        <input type="number" id="engine_power" name="engine_power" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="battery_capacity" class="text-sm font-medium">Battery Capacity (kWh)</label>
                        <input type="number" id="battery_capacity" name="battery_capacity" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="range_km" class="text-sm font-medium">Range (km)</label>
                        <input type="number" id="range_km" name="range_km" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Electric vehicle range">
                    </div>

                    <div class="space-y-2">
                        <label for="charging_type" class="text-sm font-medium">Charging Type</label>
                        <input type="text" id="charging_type" name="charging_type"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="e.g., Type 2, CCS, CHAdeMO">
                    </div>

                    <div class="space-y-2">
                        <label for="fuel_efficiency" class="text-sm font-medium">Fuel Efficiency (L/100km or kWh/100km)</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" min="0" step="0.01"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0.00">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_displacement" class="text-sm font-medium">Engine Displacement (cc)</label>
                        <input type="number" id="engine_displacement" name="engine_displacement" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_cylinders" class="text-sm font-medium">Engine Cylinders</label>
                        <input type="number" id="engine_cylinders" name="engine_cylinders" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="engine_code" class="text-sm font-medium">Engine Code</label>
                        <input type="text" id="engine_code" name="engine_code"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Engine identification code">
                    </div>

                    <div class="space-y-2">
                        <label for="top_speed" class="text-sm font-medium">Top Speed (km/h)</label>
                        <input type="number" id="top_speed" name="top_speed" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="towing_weight" class="text-sm font-medium">Towing Weight (kg)</label>
                        <input type="number" id="towing_weight" name="towing_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="ownership_tax" class="text-sm font-medium">Ownership Tax (DKK)</label>
                        <input type="number" id="ownership_tax" name="ownership_tax" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Additional Information -->
        <div class="expandable-section" data-section="additional">
            <div class="section-header" onclick="toggleSection('additional')">
                <div class="section-title-group">
                    <div class="section-number">4</div>
                    <div>
                        <div class="section-title">Additional Information</div>
                        <div class="section-subtitle">Type, color, condition, and more</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Provide additional details to make your listing stand out. All fields are optional.
                    </div>
                <div class="form-grid">
                    <div class="space-y-2">
                        <label for="type_id" class="text-sm font-medium">Type</label>
                        <select id="type_id" name="type_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
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
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
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
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
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
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Body Type</option>
                            @foreach($lookupData['bodyTypes'] as $bodyType)
                                <option value="{{ $bodyType->id }}">
                                    {{ $bodyType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="condition_id" class="text-sm font-medium">Condition</label>
                        <select id="condition_id" name="condition_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
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
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Gear Type</option>
                            @foreach($lookupData['gearTypes'] as $gearType)
                                <option value="{{ $gearType->id }}">
                                    {{ $gearType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="price_type_id" class="text-sm font-medium">Price Type</label>
                        <select id="price_type_id" name="price_type_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Price Type</option>
                            @foreach($lookupData['priceTypes'] as $priceType)
                                <option value="{{ $priceType->id }}">
                                    {{ $priceType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="sales_type_id" class="text-sm font-medium">Sales Type</label>
                        <select id="sales_type_id" name="sales_type_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select Sales Type</option>
                            @foreach($lookupData['salesTypes'] as $salesType)
                                <option value="{{ $salesType->id }}">
                                    {{ $salesType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    </div>
                    </div>
                    </div>

        <!-- Section 5: Advanced Vehicle Details -->
        <div class="expandable-section" data-section="advanced">
            <div class="section-header" onclick="toggleSection('advanced')">
                <div class="section-title-group">
                    <div class="section-number">5</div>
                    <div>
                        <div class="section-title">Advanced Vehicle Details</div>
                        <div class="section-subtitle">Weight, dimensions, safety, and inspection details</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Advanced technical specifications and details about your vehicle. All fields are optional.
                </div>
                <div class="form-grid">
                    <!-- Weight Specifications -->
                    <div class="space-y-2">
                        <label for="total_weight" class="text-sm font-medium">Total Weight (kg)</label>
                        <input type="number" id="total_weight" name="total_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="vehicle_weight" class="text-sm font-medium">Vehicle Weight (kg)</label>
                        <input type="number" id="vehicle_weight" name="vehicle_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="technical_total_weight" class="text-sm font-medium">Technical Total Weight (kg)</label>
                        <input type="number" id="technical_total_weight" name="technical_total_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="towing_weight_brakes" class="text-sm font-medium">Towing Weight with Brakes (kg)</label>
                        <input type="number" id="towing_weight_brakes" name="towing_weight_brakes" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="minimum_weight" class="text-sm font-medium">Minimum Weight (kg)</label>
                        <input type="number" id="minimum_weight" name="minimum_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="gross_combination_weight" class="text-sm font-medium">Gross Combination Weight (kg)</label>
                        <input type="number" id="gross_combination_weight" name="gross_combination_weight" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <!-- Physical Specifications -->
                    <div class="space-y-2">
                        <label for="doors" class="text-sm font-medium">Number of Doors</label>
                        <input type="number" id="doors" name="doors" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="minimum_seats" class="text-sm font-medium">Minimum Seats</label>
                        <input type="number" id="minimum_seats" name="minimum_seats" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="maximum_seats" class="text-sm font-medium">Maximum Seats</label>
                        <input type="number" id="maximum_seats" name="maximum_seats" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="wheels" class="text-sm font-medium">Number of Wheels</label>
                        <input type="number" id="wheels" name="wheels" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="axles" class="text-sm font-medium">Number of Axles</label>
                        <input type="number" id="axles" name="axles" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="drive_axles" class="text-sm font-medium">Drive Axles</label>
                        <input type="number" id="drive_axles" name="drive_axles" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="wheelbase" class="text-sm font-medium">Wheelbase (mm)</label>
                        <input type="number" id="wheelbase" name="wheelbase" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="coupling" class="text-sm font-medium">Coupling</label>
                        <select id="coupling" name="coupling"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <!-- Safety Features -->
                    <div class="space-y-2">
                        <label for="ncap_five" class="text-sm font-medium">5-Star NCAP Rating</label>
                        <select id="ncap_five" name="ncap_five"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                            <option value="">Select</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="airbags" class="text-sm font-medium">Number of Airbags</label>
                        <input type="number" id="airbags" name="airbags" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="integrated_child_seats" class="text-sm font-medium">Integrated Child Seats</label>
                        <input type="number" id="integrated_child_seats" name="integrated_child_seats" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="seat_belt_alarms" class="text-sm font-medium">Seat Belt Alarms</label>
                        <input type="number" id="seat_belt_alarms" name="seat_belt_alarms" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <!-- Inspection Details -->
                    <div class="space-y-2">
                        <label for="last_inspection_date" class="text-sm font-medium">Last Inspection Date</label>
                        <input type="date" id="last_inspection_date" name="last_inspection_date"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="last_inspection_result" class="text-sm font-medium">Last Inspection Result</label>
                        <input type="text" id="last_inspection_result" name="last_inspection_result"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="e.g., Pass, Fail">
                    </div>

                    <div class="space-y-2">
                        <label for="last_inspection_odometer" class="text-sm font-medium">Last Inspection Odometer (km)</label>
                        <input type="number" id="last_inspection_odometer" name="last_inspection_odometer" min="0"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="type_approval_code" class="text-sm font-medium">Type Approval Code</label>
                        <input type="text" id="type_approval_code" name="type_approval_code"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Type approval code">
                    </div>

                    <div class="space-y-2">
                        <label for="euronorm" class="text-sm font-medium">Euro Emission Standard</label>
                        <input type="text" id="euronorm" name="euronorm"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="e.g., Euro 6, Euro 5">
                    </div>

                    <!-- Registration Details -->
                    <div class="space-y-2">
                        <label for="registration_status" class="text-sm font-medium">Registration Status</label>
                        <input type="text" id="registration_status" name="registration_status"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Current registration status">
                    </div>

                    <div class="space-y-2">
                        <label for="registration_status_updated_date" class="text-sm font-medium">Registration Status Updated Date</label>
                        <input type="date" id="registration_status_updated_date" name="registration_status_updated_date"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="expire_date" class="text-sm font-medium">Expiration Date</label>
                        <input type="date" id="expire_date" name="expire_date"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="status_updated_date" class="text-sm font-medium">Status Updated Date</label>
                        <input type="date" id="status_updated_date" name="status_updated_date"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <!-- Leasing Information -->
                    <div class="space-y-2">
                        <label for="leasing_period_start" class="text-sm font-medium">Leasing Period Start</label>
                        <input type="date" id="leasing_period_start" name="leasing_period_start"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <div class="space-y-2">
                        <label for="leasing_period_end" class="text-sm font-medium">Leasing Period End</label>
                        <input type="date" id="leasing_period_end" name="leasing_period_end"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    </div>

                    <!-- Other Details -->
                    <div class="space-y-2 md:col-span-2">
                        <label for="extra_equipment" class="text-sm font-medium">Extra Equipment</label>
                        <textarea id="extra_equipment" name="extra_equipment" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="List any extra equipment..."></textarea>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="dispensations" class="text-sm font-medium">Dispensations</label>
                        <textarea id="dispensations" name="dispensations" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Any dispensations..."></textarea>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="permits" class="text-sm font-medium">Permits</label>
                        <textarea id="permits" name="permits" rows="3"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Any permits..."></textarea>
                    </div>

                    <!-- Hidden field for vehicle_external_id (may be auto-populated) -->
                    <input type="hidden" id="vehicle_external_id" name="vehicle_external_id" value="">
                </div>
            </div>
        </div>

        <!-- Section 6: Equipment & Features -->
        <div class="expandable-section" data-section="equipment">
            <div class="section-header" onclick="toggleSection('equipment')">
                <div class="section-title-group">
                    <div class="section-number">6</div>
                    <div>
                        <div class="section-title">Equipment & Features</div>
                        <div class="section-subtitle">Select the equipment your vehicle has</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Select the equipment and features your vehicle has. This helps buyers find exactly what they're looking for.
                </div>
                
                <!-- Equipment by Category -->
                <div class="space-y-2">
                    @foreach($lookupData['equipmentTypes'] as $equipmentType)
                        @if($equipmentType->equipments->count() > 0)
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
                                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                                <input 
                                                    type="checkbox" 
                                                    name="equipment_ids[]" 
                                                    value="{{ $equipment->id }}"
                                                    class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                    onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                                                >
                                                <span>{{ $equipment->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
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
                        <div class="equipment-type-group border border-input rounded-lg overflow-hidden">
                            <button 
                                type="button"
                                class="equipment-type-toggle w-full flex items-center justify-between px-4 py-3 text-sm font-semibold text-foreground hover:bg-accent transition-colors"
                                data-type-id="uncategorized"
                            >
                                <span class="uppercase tracking-wide">Other</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="equipment-type-icon transition-transform">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                            </button>
                            <div class="equipment-type-content hidden px-4 pb-3 pt-2">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($equipmentWithoutType as $equipment)
                                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input">
                                            <input 
                                                type="checkbox" 
                                                name="equipment_ids[]" 
                                                value="{{ $equipment->id }}"
                                                class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                onchange="handleEquipmentChange(this, {{ $equipment->id }}, '{{ addslashes($equipment->name) }}')"
                                            >
                                            <span>{{ $equipment->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section 7: Photos -->
        <div class="expandable-section" data-section="photos">
            <div class="section-header" onclick="toggleSection('photos')">
                <div class="section-title-group">
                    <div class="section-number">7</div>
                    <div>
                        <div class="section-title">Photos</div>
                        <div class="section-subtitle">Add photos of your vehicle</div>
                    </div>
                </div>
                <svg class="section-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="section-content">
                <div class="section-description">
                    Add photos of your vehicle. Good photos help your listing sell faster! You can select multiple images. Drag and drop or click to upload.
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
                            <p class="upload-hint">PNG, JPG, GIF up to 10MB each</p>
                        </div>
                    </div>
                </div>
                
                <!-- Image Preview Grid -->
                <div id="image-preview-container" class="image-preview-container hidden">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold">
                            Selected Images (<span id="image-count">0</span>)
                        </h4>
                        <button type="button" onclick="clearAllImages()" class="text-xs text-muted-foreground hover:text-foreground">
                            Clear All
                        </button>
                    </div>
                    <div id="image-preview-grid" class="image-preview-grid">
                        <!-- Image previews will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="vehicle_list_status_id" value="{{ \App\Constants\VehicleListStatus::PUBLISHED }}">
        <input type="hidden" name="published_at" value="">

        <!-- Submit Section -->
        <div class="submit-section">
            <h3>Ready to publish your listing?</h3>
            <p>Review your information and click the button below to publish your vehicle listing.</p>
            <button type="submit" class="btn btn-submit">
                Publish Vehicle Listing
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ asset('js/sell-your-car-form.js') }}"></script>
@endpush
@endsection
