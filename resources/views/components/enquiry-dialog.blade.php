@props(['type', 'vehicle' => null, 'vehicleId' => null, 'shared' => false])

@php
    use App\Helpers\FormatHelper;

    // Determine vehicle slug for URLs (and dialog id); fallback to id for backward compatibility
    $slug = $vehicleId ?? null;
    if ($shared) {
        $slug = 'shared';
    } elseif (!$slug && $vehicle) {
        $slug = is_array($vehicle) ? ($vehicle['slug'] ?? $vehicle['id'] ?? null) : ($vehicle->slug ?? $vehicle->id ?? null);
    }
    $slug = $slug ? (is_string($slug) ? $slug : (string) $slug) : null;

    $numericVehicleId = null;
    if ($vehicle) {
        $numericVehicleId = is_array($vehicle) ? ($vehicle['id'] ?? null) : ($vehicle->id ?? null);
    }

    // Get form configuration based on type
    $formConfig = [
        'enquiry' => [
            'title' => __('messages.dialogs.enquiry_form'),
            'description' => __('messages.dialogs.enquiry_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.forms.enter_message'),
            'submitText' => __('messages.dialogs.submit_enquiry'),
            'endpoint' => ($shared || !$slug) ? '' : route('vehicles.enquire.submit', ['vehicle' => $slug]),
            'errorMessage' => __('messages.dialogs.please_login_enquiry'),
        ],
        'test-drive' => [
            'title' => __('messages.dialogs.test_drive_request'),
            'description' => __('messages.dialogs.test_drive_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.dialogs.test_drive_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_test_drive'),
            'endpoint' => $slug ? route('vehicles.test-drive.submit', ['vehicle' => $slug]) : '',
            'errorMessage' => __('messages.dialogs.please_login_test_drive'),
        ],
        'price-negotiation' => [
            'title' => __('messages.dialogs.price_negotiation'),
            'description' => __('messages.dialogs.price_negotiation_description'),
            'formTitle' => __('messages.forms.your_offer'),
            'messageLabel' => __('messages.dialogs.your_offer_message'),
            'messagePlaceholder' => __('messages.dialogs.price_negotiation_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_offer'),
            'endpoint' => $slug ? route('vehicles.price-negotiation.submit', ['vehicle' => $slug]) : '',
            'errorMessage' => __('messages.dialogs.please_login_price_negotiation'),
        ],
        'exchange' => [
            'title' => __('messages.dialogs.exchange_form'),
            'description' => __('messages.dialogs.exchange_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.dialogs.exchange_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_exchange'),
            'endpoint' => $slug ? route('vehicles.exchange.submit', ['vehicle' => $slug]) : '',
            'errorMessage' => __('messages.dialogs.please_login_exchange'),
        ],
    ];
    
    $config = $formConfig[$type] ?? $formConfig['enquiry'];
    
    // Get vehicle data - handle both objects and arrays
    if ($shared || !$vehicle) {
        $vehicleTitle = '';
        $vehiclePrice = null;
        $vehicleBrand = null;
        $vehicleModel = null;
    } elseif (is_array($vehicle)) {
        $vehicleTitle = $vehicle['title'] ?? __('messages.forms.vehicle');
        $vehiclePrice = $vehicle['price'] ?? null;
        $vehicleBrand = $vehicle['brand_name'] ?? null;
        $vehicleModel = $vehicle['model_name'] ?? null;
    } else {
        $vehicleTitle = $vehicle->title ?? __('messages.forms.vehicle');
        $vehiclePrice = $vehicle->price ?? null;
        $vehicleBrand = $vehicle->brand_name ?? null;
        $vehicleModel = $vehicle->model_name ?? null;
    }
    $priceLabel = $type === 'price-negotiation' ? __('messages.forms.current_price') : __('messages.forms.price');

    // Prefill auth user data if logged in via JWT cookie
    $jwtUser   = app(\App\Services\AuthService::class)->getAuthenticatedUser(request());
    $authName  = $jwtUser?->name  ?? '';
    $authEmail = $jwtUser?->email ?? '';
@endphp

<div id="{{ $type }}-dialog-{{ $slug }}"
     class="fixed inset-0 z-50 hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $type }}-dialog-title"
     data-endpoint-template="{{ rtrim(route('vehicles.enquire.submit', ['vehicle' => '__SLUG__']), '/') }}"
     @if($shared) data-shared="1" @endif>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        onclick="closeEnquiryDialog('{{ $type }}', '{{ $slug }}')"
        aria-hidden="true"
    ></div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-background rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <div class="flex-1">
                    <h2 id="{{ $type }}-dialog-title" class="text-xl font-semibold text-foreground">
                        {{ $config['title'] }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ $config['description'] }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeEnquiryDialog('{{ $type }}', '{{ $slug }}')"
                    class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="{{ __('messages.dialogs.close_dialog') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Scrollable Content -->
            <div class="overflow-y-auto flex-1 px-6 py-4">
                <!-- Vehicle Information Card -->
                <div class="bg-gray-50 rounded-lg p-4 border border-border mb-4">
                    <h3 class="text-foreground text-sm font-semibold mb-3">{{ __('messages.forms.vehicle_information') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.vehicle') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $vehicleTitle }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-muted-foreground">{{ $priceLabel }}</span>
                            <p class="text-foreground font-medium text-sm text-primary">{{ FormatHelper::formatCurrency($vehiclePrice) }}</p>
                        </div>
                        @if($vehicleBrand)
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.brand') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $vehicleBrand }}</p>
                        </div>
                        @endif
                        @if($vehicleModel)
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.model') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $vehicleModel }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Form -->
                <div class="bg-gray-50 rounded-lg p-4 border border-border">
                    <h3 class="text-foreground text-sm font-semibold mb-4">{{ $config['formTitle'] }}</h3>
                    <form id="{{ $type }}-form-{{ $slug }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $slug }}">

                        <!-- Error Display Container -->
                        <div id="{{ $type }}-errors-{{ $slug }}" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                            <ul id="{{ $type }}-error-list-{{ $slug }}" class="list-disc list-inside text-sm text-red-800"></ul>
                        </div>

                        <!-- Success Message -->
                        <div id="{{ $type }}-success-{{ $slug }}" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3 mb-4">
                            <p class="text-sm text-green-800"></p>
                        </div>

                        <div class="space-y-2">
                            <label for="{{ $type }}-name-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.full_name') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="{{ $type }}-name-{{ $slug }}" 
                                name="name" 
                                required
                                value="{{ $authName }}"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_full_name') }}"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="{{ $type }}-email-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.email') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="{{ $type }}-email-{{ $slug }}" 
                                name="email" 
                                required
                                value="{{ $authEmail }}"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_email') }}"
                            >
                        </div>

                        <div class="space-y-2">
                            <label for="{{ $type }}-phone-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.phone_number') }}
                            </label>
                            <input 
                                type="tel" 
                                id="{{ $type }}-phone-{{ $slug }}" 
                                name="phone" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_phone') }}"
                            >
                        </div>

                        @if($type === 'exchange')
                        <div class="space-y-2">
                            <label for="{{ $type }}-licence_plate-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.licence_plate') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="{{ $type }}-licence_plate-{{ $slug }}" 
                                name="licence_plate" 
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_licence_plate') }}"
                            >
                        </div>
                        <div class="space-y-2">
                            <label for="{{ $type }}-kilometers-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.kilometres_used') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="{{ $type }}-kilometers-{{ $slug }}" 
                                name="kilometers" 
                                required
                                min="0"
                                step="1"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_kilometres') }}"
                            >
                        </div>
                        <div class="space-y-2">
                            <label for="{{ $type }}-expected_price-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.expected_price') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="{{ $type }}-expected_price-{{ $slug }}" 
                                name="expected_price" 
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.enter_expected_price') }}"
                            >
                        </div>
                        @endif

                        <div class="space-y-2">
                            <label for="{{ $type }}-message-{{ $slug }}" class="text-sm font-medium leading-none">
                                {{ $config['messageLabel'] }} <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="{{ $type }}-message-{{ $slug }}" 
                                name="message" 
                                required
                                rows="5"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ $config['messagePlaceholder'] }}"
                            ></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button 
                                type="submit" 
                                id="{{ $type }}-submit-btn-{{ $slug }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <span id="{{ $type }}-submit-text-{{ $slug }}">{{ $config['submitText'] }}</span>
                                <svg id="{{ $type }}-submit-spinner-{{ $slug }}" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            <button 
                                type="button"
                                onclick="closeEnquiryDialog('{{ $type }}', '{{ $slug }}')"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                {{ __('messages.common.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const dialogId = '{{ $type }}-dialog-{{ $slug }}';
    const formId = '{{ $type }}-form-{{ $slug }}';
    const form = document.getElementById(formId);
    
    if (!form) return;
    
    const submitBtn = document.getElementById('{{ $type }}-submit-btn-{{ $slug }}');
    const submitText = document.getElementById('{{ $type }}-submit-text-{{ $slug }}');
    const submitSpinner = document.getElementById('{{ $type }}-submit-spinner-{{ $slug }}');
    const errorContainer = document.getElementById('{{ $type }}-errors-{{ $slug }}');
    const errorList = document.getElementById('{{ $type }}-error-list-{{ $slug }}');
    const successMessage = document.getElementById('{{ $type }}-success-{{ $slug }}');
    const endpoint = form.dataset.endpoint || '{{ $config['endpoint'] }}';
    const errorMsg = '{{ $config['errorMessage'] }}';
    let funnelStarted = false;

    form.addEventListener('input', function () {
        if (funnelStarted) return;
        funnelStarted = true;
        if (typeof window.bilskyenTrackFunnel === 'function') {
            window.bilskyenTrackFunnel('form_start', { form: '{{ $type }}' });
        }
    }, { once: true });
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Hide previous messages
        if (errorContainer) errorContainer.classList.add('hidden');
        if (successMessage) successMessage.classList.add('hidden');
        if (errorList) errorList.innerHTML = '';

        // Disable submit button
        if (submitBtn) submitBtn.disabled = true;
        if (submitText) submitText.textContent = '{{ __('messages.dialogs.submitting') }}';
        if (submitSpinner) submitSpinner.classList.remove('hidden');

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
        @if($type === 'exchange')
        data.licence_plate = formData.get('licence_plate');
        data.kilometers = formData.get('kilometers');
        data.expected_price = formData.get('expected_price');
        @endif

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const response = await fetch(endpoint, {
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
                if (typeof window.bilskyenTrackFunnel === 'function') {
                    window.bilskyenTrackFunnel('form_error', { form: '{{ $type }}', status: String(response.status) });
                }
                if (response.status === 401) {
                    // Redirect to login
                    if (window.showSnackbar) {
                        window.showSnackbar(errorMsg, 'error');
                    }
                    setTimeout(() => {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                    return;
                }

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
                    let errorMsgText = result.message || '{{ __('messages.dialogs.failed_to_submit') }}';
                    if (typeof errorMsgText === 'string' && errorMsgText.startsWith('messages.')) {
                        errorMsgText = '{{ __('messages.dialogs.failed_to_submit') }}';
                    }
                    if (window.showSnackbar) {
                        window.showSnackbar(errorMsgText, 'error');
                    } else if (errorList) {
                        errorList.innerHTML = `<li>${errorMsgText}</li>`;
                        if (errorContainer) errorContainer.classList.remove('hidden');
                    }
                }
            } else {
                // Success — never display raw translation keys
                let successMsg = result.message || '{{ __('messages.dialogs.request_submitted') }}';
                if (typeof successMsg === 'string' && /^messages\./.test(successMsg.trim())) {
                    successMsg = '{{ __('messages.dialogs.request_submitted') }}';
                }
                if (successMessage) {
                    successMessage.querySelector('p').textContent = successMsg;
                    successMessage.classList.remove('hidden');
                }
                
                // Reset form
                form.reset();
                
                // Show snackbar
                if (window.showSnackbar) {
                    window.showSnackbar(successMsg, 'success');
                }

                if (typeof window.bilskyenTrackMetaLead === 'function') {
                    window.bilskyenTrackMetaLead(result?.data?.meta_lead_event_id, @json($numericVehicleId ? (string) $numericVehicleId : ''), {
                        content_name: @json($vehicleTitle),
                        value: @json((float) ($vehiclePrice ?? 0))
                    });
                }

                if (window.__bilskyenFormOpen) {
                    window.__bilskyenFormOpen.converted = true;
                }

                // Close dialog immediately after submit
                closeEnquiryDialog('{{ $type }}', '{{ $slug }}');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            if (typeof window.bilskyenTrackFunnel === 'function') {
                window.bilskyenTrackFunnel('form_error', { form: '{{ $type }}', status: 'network' });
            }
            const errorMsgText = '{{ __('messages.dialogs.error_occurred') }}';
            if (window.showSnackbar) {
                window.showSnackbar(errorMsgText, 'error');
            } else if (errorList) {
                errorList.innerHTML = `<li>${errorMsgText}</li>`;
                if (errorContainer) errorContainer.classList.remove('hidden');
            }
        } finally {
            // Re-enable submit button
            if (submitBtn) submitBtn.disabled = false;
            if (submitText) submitText.textContent = '{{ $config['submitText'] }}';
            if (submitSpinner) submitSpinner.classList.add('hidden');
        }
    });
    
    // Handle ESC key to close dialog
    const dialog = document.getElementById(dialogId);
    if (dialog) {
        dialog.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEnquiryDialog('{{ $type }}', '{{ $slug }}');
            }
        });
    }
})();
</script>
