@extends('layouts.app')

@section('title', __('messages.pages.price_negotiation.page_title') . ' | Bilskyen')

@php
    use App\Helpers\FormatHelper;
@endphp

@section('content')
<div class="container space-y-8 py-6 max-w-4xl">
    <!-- Header Section -->
    <div class="space-y-4">
        <div class="flex flex-col gap-4">
            <h1 class="text-foreground text-3xl font-bold tracking-tight">
                {{ __('messages.pages.price_negotiation.title') }}
            </h1>
            <p class="text-muted-foreground">
                {{ __('messages.pages.price_negotiation.description') }}
            </p>
        </div>
        <div class="border-t border-border"></div>
    </div>

    <!-- Vehicle Information Card -->
    <div class="bg-gray-50 rounded-lg p-6 border border-border">
        <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.price_negotiation.vehicle_information') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <span class="text-sm text-muted-foreground">{{ __('messages.pages.price_negotiation.vehicle') }}</span>
                <p class="text-foreground font-medium">{{ $vehicle->title }}</p>
            </div>
            <div>
                <span class="text-sm text-muted-foreground">{{ __('messages.pages.price_negotiation.current_price') }}</span>
                <p class="text-foreground font-medium text-primary">{{ FormatHelper::formatCurrency($vehicle->price ?? null) }}</p>
            </div>
            @if($vehicle->brand_name)
            <div>
                <span class="text-sm text-muted-foreground">{{ __('messages.pages.price_negotiation.brand') }}</span>
                <p class="text-foreground font-medium">{{ $vehicle->brand_name }}</p>
            </div>
            @endif
            @if($vehicle->model_name)
            <div>
                <span class="text-sm text-muted-foreground">{{ __('messages.pages.price_negotiation.model') }}</span>
                <p class="text-foreground font-medium">{{ $vehicle->model_name }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Price Negotiation Form -->
    <div class="bg-gray-50 rounded-lg p-6 border border-border">
        <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.price_negotiation.your_offer') }}</h2>
        <form id="price-negotiation-form" class="space-y-4">
            @csrf
            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

            <!-- Error Display Container -->
            <div id="form-errors" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                <ul id="error-list" class="list-disc list-inside text-sm text-red-800"></ul>
            </div>

            <!-- Success Message -->
            <div id="success-message" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3 mb-4">
                <p class="text-sm text-green-800"></p>
            </div>

            <div class="space-y-2">
                <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {{ __('messages.forms.full_name') }} <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    required
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="{{ __('messages.forms.placeholders.full_name') }}"
                >
            </div>

            <div class="space-y-2">
                <label for="message" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {{ __('messages.pages.price_negotiation.offer_message_label') }} <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="message" 
                    name="message" 
                    required
                    rows="6"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="{{ __('messages.pages.price_negotiation.offer_placeholder') }}"
                ></textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button 
                    type="submit" 
                    id="submit-btn"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                >
                    <span id="submit-text">{{ __('messages.pages.price_negotiation.submit') }}</span>
                    <svg id="submit-spinner" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                <a 
                    href="{{ route('vehicle.detail', $vehicle) }}" 
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    {{ __('messages.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('price-negotiation-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const errorContainer = document.getElementById('form-errors');
    const errorList = document.getElementById('error-list');
    const successMessage = document.getElementById('success-message');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Hide previous messages
        errorContainer.classList.add('hidden');
        successMessage.classList.add('hidden');
        errorList.innerHTML = '';

        // Disable submit button
        submitBtn.disabled = true;
        submitText.textContent = '{{ __('messages.pages.price_negotiation.submitting') }}';
        submitSpinner.classList.remove('hidden');

        // Get form data
        const formData = new FormData(form);
        const bot = typeof window.bilskyenBotFields === 'function' ? await window.bilskyenBotFields() : {};
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            message: formData.get('message'),
            ...bot,
        };

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const response = await fetch(@json(route('vehicles.price-negotiation.submit', ['vehicle' => $vehicle->slug])), {
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
                if (response.status === 401) {
                    // Redirect to login
                    if (window.showSnackbar) {
                        window.showSnackbar('{{ __('messages.pages.price_negotiation.login_required') }}', 'error');
                    }
                    setTimeout(() => {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                    return;
                }

                // Show validation errors
                if (result.errors) {
                    const errors = result.errors;
                    for (const field in errors) {
                        const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                        fieldErrors.forEach(error => {
                            const li = document.createElement('li');
                            li.textContent = error;
                            errorList.appendChild(li);
                        });
                    }
                    errorContainer.classList.remove('hidden');
                } else {
                    const errorMsg = result.message || '{{ __('messages.pages.price_negotiation.submit_error') }}';
                    if (window.showSnackbar) {
                        window.showSnackbar(errorMsg, 'error');
                    } else {
                        errorList.innerHTML = `<li>${errorMsg}</li>`;
                        errorContainer.classList.remove('hidden');
                    }
                }
            } else {
                // Success
                const successMsg = result.message || '{{ __('messages.pages.price_negotiation.submit_success') }}';
                successMessage.querySelector('p').textContent = successMsg;
                successMessage.classList.remove('hidden');
                
                // Reset form
                form.reset();
                
                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (window.showSnackbar) {
                    window.showSnackbar(successMsg, 'success');
                }

                if (typeof window.bilskyenTrackMetaLead === 'function') {
                    window.bilskyenTrackMetaLead(result?.data?.meta_lead_event_id, @json((string) $vehicle->id), {
                        content_name: @json($vehicle->title),
                        value: @json((float) ($vehicle->price ?? 0))
                    });
                }

                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = '{{ route("vehicle.detail", $vehicle) }}';
                }, 3000);
            }
        } catch (error) {
            console.error('Error submitting price negotiation:', error);
            const errorMsg = '{{ __('messages.pages.price_negotiation.generic_error') }}';
            if (window.showSnackbar) {
                window.showSnackbar(errorMsg, 'error');
            } else {
                errorList.innerHTML = `<li>${errorMsg}</li>`;
                errorContainer.classList.remove('hidden');
            }
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitText.textContent = '{{ __('messages.pages.price_negotiation.submit') }}';
            submitSpinner.classList.add('hidden');
        }
    });
});
</script>
@endsection
