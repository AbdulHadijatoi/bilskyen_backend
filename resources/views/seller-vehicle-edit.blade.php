@extends('layouts.app')

@section('title', 'Edit Vehicle | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    use App\Constants\VehicleListStatus;
@endphp

@section('content')
<div class="bg-muted min-h-screen">
    <div class="container mx-auto py-6">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <a href="{{ route('seller.dashboard', ['token' => $token]) }}" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-foreground">Edit Vehicle</h1>
                <p class="text-muted-foreground mt-2">Update your vehicle listing details</p>
            </div>

            <!-- Edit Form -->
            <div class="rounded-lg bg-card border border-border p-6">
                <form id="edit-vehicle-form" class="space-y-6">
                    @csrf
                    
                    <!-- Error Display -->
                    <div id="form-errors" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                        <ul id="error-list" class="list-disc list-inside text-sm text-red-800"></ul>
                    </div>

                    <!-- Success Message -->
                    <div id="success-message" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3 mb-4">
                        <p class="text-sm text-green-800"></p>
                    </div>

                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h2 class="text-xl font-semibold text-foreground border-b border-border pb-2">Basic Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-foreground mb-2">
                                    Title <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    value="{{ old('title', $vehicle->title) }}"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="e.g., 2020 BMW 3 Series"
                                />
                            </div>

                            <!-- Price -->
                            <div>
                                <label for="price" class="block text-sm font-medium text-foreground mb-2">
                                    Price (kr.) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    value="{{ old('price', $vehicle->price) }}"
                                    min="0"
                                    step="1"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="250000"
                                />
                            </div>

                            <!-- KM Driven -->
                            <div>
                                <label for="km_driven" class="block text-sm font-medium text-foreground mb-2">
                                    KM Driven
                                </label>
                                <input
                                    type="number"
                                    id="km_driven"
                                    name="km_driven"
                                    value="{{ old('km_driven', $vehicle->km_driven) }}"
                                    min="0"
                                    step="1"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    placeholder="50000"
                                />
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="vehicle_list_status_id" class="block text-sm font-medium text-foreground mb-2">
                                    Status
                                </label>
                                <select
                                    id="vehicle_list_status_id"
                                    name="vehicle_list_status_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    @foreach($lookupData['vehicleListStatuses'] as $status)
                                    <option value="{{ $status->id }}" {{ $vehicle->vehicle_list_status_id == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-foreground mb-2">
                                Description
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="5"
                                class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="Describe your vehicle..."
                            >{{ old('description', $vehicle->details?->description) }}</textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-border">
                        <button
                            type="submit"
                            id="submit-btn"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                        >
                            <span id="submit-text">Save Changes</span>
                            <svg id="submit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <a
                            href="{{ route('seller.dashboard', ['token' => $token]) }}"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-vehicle-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const errorContainer = document.getElementById('form-errors');
    const errorList = document.getElementById('error-list');
    const successMessage = document.getElementById('success-message');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Hide previous messages
        if (errorContainer) errorContainer.classList.add('hidden');
        if (successMessage) successMessage.classList.add('hidden');
        if (errorList) errorList.innerHTML = '';

        // Disable submit button
        if (submitBtn) submitBtn.disabled = true;
        if (submitText) submitText.textContent = 'Saving...';
        if (submitSpinner) submitSpinner.classList.remove('hidden');

        // Get form data
        const formData = new FormData(form);
        const data = {
            title: formData.get('title'),
            price: formData.get('price') ? parseInt(formData.get('price')) : null,
            km_driven: formData.get('km_driven') ? parseInt(formData.get('km_driven')) : null,
            vehicle_list_status_id: formData.get('vehicle_list_status_id') ? parseInt(formData.get('vehicle_list_status_id')) : null,
            description: formData.get('description'),
        };

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const token = '{{ $token }}';
        const vehicleId = {{ $vehicle->id }};

        try {
            const response = await fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                // Show validation errors
                if (result.errors && errorList) {
                    const errors = result.errors;
                    for (const field in errors) {
                        const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                        fieldErrors.forEach(error => {
                            const li = document.createElement('li');
                            li.textContent = error;
                            errorList.appendChild(li);
                        });
                    }
                    if (errorContainer) errorContainer.classList.remove('hidden');
                } else {
                    const errorMsg = result.message || 'Failed to update vehicle. Please try again.';
                    if (window.showSnackbar) {
                        window.showSnackbar(errorMsg, 'error');
                    } else if (errorList) {
                        errorList.innerHTML = `<li>${errorMsg}</li>`;
                        if (errorContainer) errorContainer.classList.remove('hidden');
                    }
                }
            } else {
                // Success
                const successMsg = result.message || 'Vehicle updated successfully!';
                if (successMessage) {
                    successMessage.querySelector('p').textContent = successMsg;
                    successMessage.classList.remove('hidden');
                }
                
                // Show snackbar
                if (window.showSnackbar) {
                    window.showSnackbar(successMsg, 'success');
                }

                // Redirect to dashboard after 1.5 seconds
                setTimeout(() => {
                    window.location.href = `{{ route('seller.dashboard', ['token' => $token]) }}`;
                }, 1500);
            }
        } catch (error) {
            console.error('Error updating vehicle:', error);
            const errorMsg = 'An error occurred. Please try again.';
            if (window.showSnackbar) {
                window.showSnackbar(errorMsg, 'error');
            } else if (errorList) {
                errorList.innerHTML = `<li>${errorMsg}</li>`;
                if (errorContainer) errorContainer.classList.remove('hidden');
            }
        } finally {
            // Re-enable submit button
            if (submitBtn) submitBtn.disabled = false;
            if (submitText) submitText.textContent = 'Save Changes';
            if (submitSpinner) submitSpinner.classList.add('hidden');
        }
    });
});
</script>
@endsection
