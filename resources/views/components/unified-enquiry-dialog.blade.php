@props(['vehicle'])

@php
    use App\Helpers\FormatHelper;

    $slug = $vehicle->slug ?? (string) $vehicle->id;
    $vehicleTitle = $vehicle->title ?? __('messages.forms.vehicle');
    $vehiclePrice = $vehicle->price ?? null;
    $vehicleBrand = $vehicle->brand_name ?? null;
    $vehicleModel = $vehicle->model_name ?? null;
    $numericVehicleId = $vehicle->id ?? null;

    $jwtUser = app(\App\Services\AuthService::class)->getAuthenticatedUser(request());
    $authName = $jwtUser?->name ?? '';
    $authEmail = $jwtUser?->email ?? '';

    $typeOptions = [
        'enquiry' => __('messages.pages.vehicles.detail.send_enquiry'),
        'test-drive' => __('messages.pages.vehicles.detail.request_test_drive'),
        'exchange' => __('messages.pages.vehicles.detail.exchange_request'),
        'price-negotiation' => __('messages.pages.vehicles.detail.price_negotiation'),
    ];

    $formConfig = [
        'enquiry' => [
            'title' => __('messages.dialogs.enquiry_form'),
            'description' => __('messages.dialogs.enquiry_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.forms.enter_message'),
            'submitText' => __('messages.dialogs.submit_enquiry'),
            'endpoint' => route('vehicles.enquire.submit', ['vehicle' => $slug]),
            'messageRequired' => false,
        ],
        'test-drive' => [
            'title' => __('messages.dialogs.test_drive_request'),
            'description' => __('messages.dialogs.test_drive_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.dialogs.test_drive_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_test_drive'),
            'endpoint' => route('vehicles.test-drive.submit', ['vehicle' => $slug]),
            'messageRequired' => false,
        ],
        'price-negotiation' => [
            'title' => __('messages.dialogs.price_negotiation'),
            'description' => __('messages.dialogs.price_negotiation_description'),
            'formTitle' => __('messages.forms.your_offer'),
            'messageLabel' => __('messages.dialogs.your_offer_message'),
            'messagePlaceholder' => __('messages.dialogs.price_negotiation_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_offer'),
            'endpoint' => route('vehicles.price-negotiation.submit', ['vehicle' => $slug]),
            'messageRequired' => true,
        ],
        'exchange' => [
            'title' => __('messages.dialogs.exchange_form'),
            'description' => __('messages.dialogs.exchange_description'),
            'formTitle' => __('messages.forms.your_details'),
            'messageLabel' => __('messages.forms.message'),
            'messagePlaceholder' => __('messages.dialogs.exchange_message_placeholder'),
            'submitText' => __('messages.dialogs.submit_exchange'),
            'endpoint' => route('vehicles.exchange.submit', ['vehicle' => $slug]),
            'messageRequired' => true,
        ],
    ];
@endphp

<div id="enquiry-dialog-{{ $slug }}"
     class="fixed inset-0 z-50 hidden unified-enquiry-dialog"
     role="dialog"
     aria-modal="true"
     aria-labelledby="unified-enquiry-dialog-title"
     data-unified="1"
     data-config='@json($formConfig)'
     data-default-messages='@json([
         "enquiry" => __("messages.dialogs.enquiry_default_message"),
         "test-drive" => __("messages.dialogs.test_drive_default_message"),
     ])'>
    <div
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        onclick="closeEnquiryDialog('enquiry', '{{ $slug }}')"
        aria-hidden="true"
    ></div>

    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-background rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <div class="flex-1">
                    <h2 id="unified-enquiry-dialog-title" class="text-xl font-semibold text-foreground" data-unified-title>
                        {{ $formConfig['enquiry']['title'] }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1" data-unified-description>
                        {{ $formConfig['enquiry']['description'] }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeEnquiryDialog('enquiry', '{{ $slug }}')"
                    class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="{{ __('messages.dialogs.close_dialog') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-4">
                <div class="unified-enquiry-vehicle-card bg-gray-50 rounded-lg p-4 border border-border mb-4">
                    <h3 class="text-foreground text-sm font-semibold mb-3">{{ __('messages.forms.vehicle_information') }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.vehicle') }}</span>
                            <p class="text-foreground font-medium text-sm">{{ $vehicleTitle }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-muted-foreground">{{ __('messages.forms.price') }}</span>
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

                <div class="bg-gray-50 rounded-lg p-4 border border-border">
                    <form id="unified-enquiry-form-{{ $slug }}" class="space-y-4" data-endpoint="{{ $formConfig['enquiry']['endpoint'] }}">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $slug }}">

                        <div class="unified-enquiry-field">
                            <label for="unified-enquiry-type-{{ $slug }}" class="unified-enquiry-label">
                                {{ __('messages.pages.vehicles.detail.enquiry_type_label') }}
                            </label>
                            <select
                                id="unified-enquiry-type-{{ $slug }}"
                                name="enquiry_type"
                                class="unified-enquiry-select"
                                data-unified-type-select
                            >
                                @foreach($typeOptions as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected($typeKey === 'enquiry')>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="unified-enquiry-errors-{{ $slug }}" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3">
                            <ul id="unified-enquiry-error-list-{{ $slug }}" class="list-disc list-inside text-sm text-red-800"></ul>
                        </div>

                        <div id="unified-enquiry-success-{{ $slug }}" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3">
                            <p class="text-sm text-green-800"></p>
                        </div>

                        <h3 class="text-foreground text-sm font-semibold mb-1" data-unified-form-title>{{ $formConfig['enquiry']['formTitle'] }}</h3>

                        <div class="unified-enquiry-field">
                            <label for="unified-enquiry-name-{{ $slug }}" class="unified-enquiry-label">
                                {{ __('messages.forms.full_name') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="unified-enquiry-name-{{ $slug }}"
                                name="name"
                                required
                                value="{{ $authName }}"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="{{ __('messages.forms.enter_full_name') }}"
                            >
                        </div>

                        <div class="unified-enquiry-field">
                            <label for="unified-enquiry-email-{{ $slug }}" class="unified-enquiry-label">
                                {{ __('messages.forms.email') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="email"
                                id="unified-enquiry-email-{{ $slug }}"
                                name="email"
                                required
                                value="{{ $authEmail }}"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="{{ __('messages.forms.enter_email') }}"
                            >
                        </div>

                        <div class="unified-enquiry-field">
                            <label for="unified-enquiry-phone-{{ $slug }}" class="unified-enquiry-label">
                                {{ __('messages.forms.phone_number') }} <span class="text-red-500 unified-enquiry-phone-required hidden">*</span>
                            </label>
                            <input
                                type="tel"
                                id="unified-enquiry-phone-{{ $slug }}"
                                name="phone"
                                autocomplete="tel"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="{{ __('messages.forms.enter_phone') }}"
                            >
                        </div>

                        <div class="unified-enquiry-exchange-fields hidden space-y-4" data-exchange-fields>
                            <div class="unified-enquiry-field">
                                <label for="unified-enquiry-licence_plate-{{ $slug }}" class="unified-enquiry-label">
                                    {{ __('messages.forms.licence_plate') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="unified-enquiry-licence_plate-{{ $slug }}"
                                    name="licence_plate"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="{{ __('messages.forms.enter_licence_plate') }}"
                                >
                            </div>
                            <div class="unified-enquiry-field">
                                <label for="unified-enquiry-kilometers-{{ $slug }}" class="unified-enquiry-label">
                                    {{ __('messages.forms.kilometres_used') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="unified-enquiry-kilometers-{{ $slug }}"
                                    name="kilometers"
                                    min="0"
                                    step="1"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="{{ __('messages.forms.enter_kilometres') }}"
                                >
                            </div>
                            <div class="unified-enquiry-field">
                                <label for="unified-enquiry-expected_price-{{ $slug }}" class="unified-enquiry-label">
                                    {{ __('messages.forms.expected_price') }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="unified-enquiry-expected_price-{{ $slug }}"
                                    name="expected_price"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="{{ __('messages.forms.enter_expected_price') }}"
                                >
                            </div>
                        </div>

                        <div class="unified-enquiry-field unified-enquiry-message-field" data-message-field>
                            <label for="unified-enquiry-message-{{ $slug }}" class="unified-enquiry-label">
                                <span data-unified-message-label>{{ $formConfig['enquiry']['messageLabel'] }}</span> <span class="text-red-500" data-unified-message-required>*</span>
                            </label>
                            <textarea
                                id="unified-enquiry-message-{{ $slug }}"
                                name="message"
                                rows="5"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="{{ $formConfig['enquiry']['messagePlaceholder'] }}"
                                data-unified-message-input
                            ></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 pt-2">
                            <button
                                type="submit"
                                id="unified-enquiry-submit-btn-{{ $slug }}"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                            >
                                <span id="unified-enquiry-submit-text-{{ $slug }}" data-unified-submit-text>{{ $formConfig['enquiry']['submitText'] }}</span>
                                <svg id="unified-enquiry-submit-spinner-{{ $slug }}" class="hidden h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="closeEnquiryDialog('enquiry', '{{ $slug }}')"
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
(function () {
    const slug = @json($slug);
    const dialog = document.getElementById('enquiry-dialog-' + slug);
    const form = document.getElementById('unified-enquiry-form-' + slug);
    if (!dialog || !form) return;

    const config = JSON.parse(dialog.dataset.config || '{}');
    const defaultMessages = JSON.parse(dialog.dataset.defaultMessages || '{}');
    const titleEl = dialog.querySelector('[data-unified-title]');
    const descriptionEl = dialog.querySelector('[data-unified-description]');
    const formTitleEl = dialog.querySelector('[data-unified-form-title]');
    const messageLabelEl = dialog.querySelector('[data-unified-message-label]');
    const messageInput = dialog.querySelector('[data-unified-message-input]');
    const messageRequiredEl = dialog.querySelector('[data-unified-message-required]');
    const submitTextEl = dialog.querySelector('[data-unified-submit-text]');
    const exchangeFields = dialog.querySelector('[data-exchange-fields]');
    const phoneInput = document.getElementById('unified-enquiry-phone-' + slug);
    const phoneRequiredMarker = dialog.querySelector('.unified-enquiry-phone-required');
    const typeSelect = dialog.querySelector('[data-unified-type-select]');
    const submitBtn = document.getElementById('unified-enquiry-submit-btn-' + slug);
    const submitSpinner = document.getElementById('unified-enquiry-submit-spinner-' + slug);
    const errorContainer = document.getElementById('unified-enquiry-errors-' + slug);
    const errorList = document.getElementById('unified-enquiry-error-list-' + slug);
    const successMessage = document.getElementById('unified-enquiry-success-' + slug);
    let funnelStarted = false;
    let activeType = 'enquiry';

    function isMobileCompact() {
        return window.matchMedia('(max-width: 639px)').matches;
    }

    function applyCompactMode() {
        dialog.classList.toggle('unified-enquiry-dialog--compact', isMobileCompact());
        if (phoneInput) {
            phoneInput.required = isMobileCompact();
        }
        if (phoneRequiredMarker) {
            phoneRequiredMarker.classList.toggle('hidden', !isMobileCompact());
        }
        updateMessageField();
    }

    function getSelectedType() {
        return typeSelect?.value || 'enquiry';
    }

    function updateMessageField() {
        const typeConfig = config[activeType] || config.enquiry;
        if (messageInput) {
            messageInput.required = !!typeConfig.messageRequired;
        }
        if (messageRequiredEl) {
            messageRequiredEl.classList.toggle('hidden', !typeConfig.messageRequired);
        }
    }

    function applyType(type, trackOpen) {
        activeType = config[type] ? type : 'enquiry';
        const typeConfig = config[activeType];
        if (titleEl) titleEl.textContent = typeConfig.title;
        if (descriptionEl) descriptionEl.textContent = typeConfig.description;
        if (formTitleEl) formTitleEl.textContent = typeConfig.formTitle;
        if (messageLabelEl) messageLabelEl.textContent = typeConfig.messageLabel;
        if (messageInput) messageInput.placeholder = typeConfig.messagePlaceholder;
        if (submitTextEl) submitTextEl.textContent = typeConfig.submitText;
        form.dataset.endpoint = typeConfig.endpoint;
        if (exchangeFields) {
            exchangeFields.classList.toggle('hidden', activeType !== 'exchange');
        }
        exchangeFields?.querySelectorAll('input').forEach((input) => {
            input.required = activeType === 'exchange';
        });
        updateMessageField();
        if (trackOpen && typeof window.bilskyenTrackFunnel === 'function') {
            window.bilskyenTrackFunnel('form_open', { form: activeType });
        }
    }

    window.setUnifiedEnquiryType = function (type) {
        if (typeSelect) {
            typeSelect.value = config[type] ? type : 'enquiry';
        }
        applyType(typeSelect?.value || 'enquiry', false);
    };

    typeSelect?.addEventListener('change', function () {
        applyType(this.value, true);
    });

    form.addEventListener('input', function () {
        if (funnelStarted) return;
        funnelStarted = true;
        if (typeof window.bilskyenTrackFunnel === 'function') {
            window.bilskyenTrackFunnel('form_start', { form: activeType });
        }
    }, { once: true });

    dialog.addEventListener('unified-enquiry-open', function () {
        funnelStarted = false;
        applyCompactMode();
        applyType(getSelectedType(), false);
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        activeType = getSelectedType();

        if (errorContainer) errorContainer.classList.add('hidden');
        if (successMessage) successMessage.classList.add('hidden');
        if (errorList) errorList.innerHTML = '';

        if (submitBtn) submitBtn.disabled = true;
        if (submitSpinner) submitSpinner.classList.remove('hidden');

        const formData = new FormData(form);
        const bot = typeof window.bilskyenBotFields === 'function' ? await window.bilskyenBotFields() : {};
        let message = formData.get('message');
        if ((!message || !String(message).trim()) && defaultMessages[activeType]) {
            message = defaultMessages[activeType];
        }

        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            message: message,
            ...bot,
        };

        if (activeType === 'exchange') {
            data.licence_plate = formData.get('licence_plate');
            data.kilometers = formData.get('kilometers');
            data.expected_price = formData.get('expected_price');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const response = await fetch(form.dataset.endpoint, {
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
                    window.bilskyenTrackFunnel('form_error', { form: activeType, status: String(response.status) });
                }
                if (result.errors && errorList) {
                    Object.values(result.errors).flat().forEach((error) => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorList.appendChild(li);
                    });
                    errorContainer?.classList.remove('hidden');
                } else if (window.showSnackbar) {
                    window.showSnackbar(result.message || '{{ __('messages.dialogs.failed_to_submit') }}', 'error');
                }
            } else {
                let successMsg = result.message || '{{ __('messages.dialogs.request_submitted') }}';
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
                form.reset();
                if (typeSelect) {
                    typeSelect.value = 'enquiry';
                }
                applyType('enquiry', false);
                closeEnquiryDialog('enquiry', slug);
            }
        } catch (error) {
            if (typeof window.bilskyenTrackFunnel === 'function') {
                window.bilskyenTrackFunnel('form_error', { form: activeType, status: 'network' });
            }
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (submitSpinner) submitSpinner.classList.add('hidden');
        }
    });
})();
</script>
