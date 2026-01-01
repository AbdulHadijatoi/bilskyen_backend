// Sell Your Car Form Handler
// Handles form submission via AJAX, lookup, and form prefilling

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSellYourCarForm);
    } else {
        initSellYourCarForm();
    }

    function initSellYourCarForm() {
        // Initialize collapsible sections
        initCollapsibleSections();
        
        // Initialize brand/model loading
        initBrandModelLoading();
        
        // Initialize registration lookup
        initRegistrationLookup();
        
        // Initialize form submission
        initFormSubmission();
    }

    // Collapsible sections
    function initCollapsibleSections() {
        // Make toggleSection available globally for onclick handlers
        window.toggleSection = function(sectionId) {
            const content = document.getElementById(sectionId + '-content');
            if (!content) return;
            
            const header = content.previousElementSibling;
            const icon = header.querySelector('.section-icon');
            
            content.classList.toggle('active');
            if (icon) {
                icon.classList.toggle('rotated');
            }
        };
    }

    // Load models when brand is selected
    function initBrandModelLoading() {
        const brandSelect = document.getElementById('brand_id');
        if (!brandSelect) return;

        brandSelect.addEventListener('change', function() {
            const brandId = this.value;
            const modelSelect = document.getElementById('model_id');
            
            if (!modelSelect) return;
            
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
    }

    // Registration lookup
    function initRegistrationLookup() {
        const lookupBtn = document.getElementById('lookup-btn');
        const registrationInput = document.getElementById('registration-lookup');
        const vehicleForm = document.getElementById('vehicle-form');
        const lookupError = document.getElementById('lookup-error');
        const lookupLoading = document.getElementById('lookup-loading');

        if (!lookupBtn || !registrationInput || !vehicleForm) return;

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
                    advanced: true
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

                console.log('API Response:', data);

                if (data.status === 'error' || !data.data) {
                    let errorMessage = data.message || 'Failed to fetch vehicle information';
                    
                    if (data.errors && data.errors.code === 'TIMEOUT') {
                        errorMessage = 'The vehicle lookup is taking longer than expected. Please try again in a moment, or you can fill in the form manually.';
                    } else if (data.errors && data.errors.retryable) {
                        errorMessage = 'The vehicle lookup service is temporarily unavailable. Please try again in a moment, or you can fill in the form manually.';
                    }
                    
                    lookupError.textContent = errorMessage;
                    lookupError.classList.add('text-red-600');
                    vehicleForm.classList.remove('hidden');
                    return;
                }

                // Extract vehicle data from response
                let vehicleData = null;
                
                if (data.data && data.data.data) {
                    vehicleData = data.data.data;
                } else if (data.data) {
                    vehicleData = data.data;
                } else if (data.vehicle) {
                    vehicleData = data.vehicle;
                } else if (Array.isArray(data) && data.length > 0) {
                    vehicleData = data[0];
                } else if (data.status === 'success' && data.data) {
                    vehicleData = data.data;
                } else if (typeof data === 'object' && !data.status && !data.errors) {
                    vehicleData = data;
                }
                
                if (!vehicleData || typeof vehicleData !== 'object') {
                    const errorMsg = 'No vehicle data found in API response. Response structure: ' + JSON.stringify(data).substring(0, 500);
                    lookupError.textContent = errorMsg;
                    lookupError.classList.add('text-red-600');
                    console.error('Failed to extract vehicle data:', data);
                    vehicleForm.classList.remove('hidden');
                    return;
                }
                
                vehicleForm.classList.remove('hidden');
                
                setTimeout(() => {
                    prefillForm(vehicleData);
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
    }

    // Form submission handler
    function initFormSubmission() {
        const form = document.getElementById('vehicle-form');
        if (!form) return;

        form.addEventListener('submit', handleFormSubmit);
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formContainer = form.closest('.container') || document.querySelector('.container');
        
        // Clear previous errors
        clearErrors();
        
        // Create FormData BEFORE disabling form fields
        // Disabled fields are NOT included in FormData!
        const formData = new FormData(form);
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Show loading state AFTER creating FormData
        showLoadingState(submitBtn, form);
        
        try {
            const response = await fetch(form.getAttribute('data-action') || '/sell-your-car', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                // If response is not JSON, treat as error
                hideLoadingState(submitBtn, form);
                displayGeneralError('An unexpected error occurred. Please try again.');
                return;
            }

            // Hide loading state
            hideLoadingState(submitBtn, form);

            if (!response.ok || data.status === 'error') {
                // Handle validation errors
                if (data.errors) {
                    displayErrors(data.errors);
                } else {
                    displayGeneralError(data.message || 'An error occurred while saving the vehicle.');
                }
                return;
            }

            // Success - redirect to vehicle detail page
            if (data.redirect_url) {
                window.location.href = data.redirect_url;
            } else if (data.vehicle_id) {
                window.location.href = `/vehicle/${data.vehicle_id}`;
            } else {
                displayGeneralError('Vehicle saved successfully, but redirect URL is missing.');
            }

        } catch (error) {
            console.error('Form submission error:', error);
            hideLoadingState(submitBtn, form);
            displayGeneralError('An unexpected error occurred. Please try again.');
        }
    }

    // Loading state management
    function showLoadingState(submitBtn, form) {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
            `;
        }

        // Disable form fields
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input !== submitBtn) {
                    input.disabled = true;
                }
            });
        }

        // Add loading overlay
        if (form && !form.querySelector('.form-loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'form-loading-overlay';
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.3); z-index: 9999; display: flex; align-items: center; justify-content: center;';
            overlay.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg text-center">
                    <svg class="animate-spin h-8 w-8 mx-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-muted-foreground">Saving vehicle...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
    }

    function hideLoadingState(submitBtn, form) {
        if (submitBtn) {
            submitBtn.disabled = false;
            if (submitBtn.dataset.originalText) {
                submitBtn.textContent = submitBtn.dataset.originalText;
                delete submitBtn.dataset.originalText;
            }
        }

        // Enable form fields
        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                input.disabled = false;
            });
        }

        // Remove loading overlay
        const overlay = document.querySelector('.form-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Error display functions
    function clearErrors() {
        // Clear top-level errors
        const topErrorContainer = document.getElementById('form-errors-top');
        if (topErrorContainer) {
            topErrorContainer.innerHTML = '';
            topErrorContainer.classList.add('hidden');
        }

        // Clear inline errors
        document.querySelectorAll('.field-error').forEach(el => el.remove());
        
        // Remove error borders
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
            el.classList.add('border-input');
        });
    }

    function displayGeneralError(message) {
        let errorContainer = document.getElementById('form-errors-top');
        
        if (!errorContainer) {
            const form = document.getElementById('vehicle-form');
            if (!form) return;
            
            errorContainer = document.createElement('div');
            errorContainer.id = 'form-errors-top';
            errorContainer.className = 'w-full rounded-md border border-red-200 bg-red-50 p-4 text-red-800 mb-6';
            form.insertBefore(errorContainer, form.firstChild);
        }
        
        errorContainer.innerHTML = `<p class="text-sm font-medium">${escapeHtml(message)}</p>`;
        errorContainer.classList.remove('hidden');
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function displayErrors(errors) {
        clearErrors();
        
        // Display top-level errors
        if (typeof errors === 'object' && !Array.isArray(errors)) {
            const errorMessages = [];
            
            // Handle Laravel validation errors format
            Object.keys(errors).forEach(field => {
                const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                fieldErrors.forEach(error => {
                    if (field === 'error' || field === 'message') {
                        errorMessages.push(error);
                    } else {
                        displayFieldError(field, error);
                    }
                });
            });
            
            if (errorMessages.length > 0) {
                displayGeneralError(errorMessages.join('<br>'));
            }
        } else if (Array.isArray(errors)) {
            displayGeneralError(errors.join('<br>'));
        } else if (typeof errors === 'string') {
            displayGeneralError(errors);
        }
    }

    function displayFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`) || 
                     document.getElementById(fieldName) ||
                     document.getElementById(fieldName.replace('.', '_'));
        
        if (!field) {
            console.warn(`Field not found for error: ${fieldName}`);
            return;
        }

        // Add error border
        field.classList.remove('border-input');
        field.classList.add('border-red-500');

        // Remove existing error message
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add error message
        const errorElement = document.createElement('p');
        errorElement.className = 'field-error text-sm text-red-600 mt-1';
        errorElement.textContent = message;
        
        const fieldContainer = field.closest('.space-y-2') || field.parentElement;
        if (fieldContainer) {
            fieldContainer.appendChild(errorElement);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Prefill form with API data
    function prefillForm(apiData) {
        console.log('PrefillForm called with:', apiData);
        console.log('All API data keys:', Object.keys(apiData));
        
        const vehicleForm = document.getElementById('vehicle-form');
        if (vehicleForm) {
            vehicleForm.classList.remove('hidden');
        }
        
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
        
        // Helper function to safely set select value by ID or text match
        function setSelectByIdOrText(selectId, value) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.warn(`Select not found: ${selectId}`);
                return false;
            }
            if (value === null || value === undefined || value === '') return false;
            
            // If value is a number (ID), set it directly
            if (typeof value === 'number' || (typeof value === 'string' && /^\d+$/.test(value))) {
                const idValue = String(value);
                if (select.querySelector(`option[value="${idValue}"]`)) {
                    select.value = idValue;
                    console.log(`Set ${selectId} = ${idValue} (by ID)`);
                    return true;
                }
            }
            
            // If value is an object with id property
            if (typeof value === 'object' && value !== null && value.id !== undefined) {
                const idValue = String(value.id);
                if (select.querySelector(`option[value="${idValue}"]`)) {
                    select.value = idValue;
                    console.log(`Set ${selectId} = ${idValue} (from object.id)`);
                    return true;
                }
            }
            
            // Otherwise, try to match by text (for backward compatibility)
            const text = String(value).toLowerCase().trim();
            for (let option of select.options) {
                if (option.value && option.text.trim().toLowerCase() === text) {
                    select.value = option.value;
                    console.log(`Set ${selectId} = ${option.value} (matched text: ${text})`);
                    return true;
                }
            }
            
            console.warn(`No match found in ${selectId} for: ${value}`);
            return false;
        }
        
        // Basic fields
        const registration = apiData.registration || apiData.registration_number || apiData.reg || apiData.plate || apiData.license_plate;
        if (registration) setFieldValue('registration', registration);
        
        const vin = apiData.vin || apiData.chassis_number || apiData.chassis || apiData.chassisNumber;
        if (vin) setFieldValue('vin', vin);
        
        // Title - only use direct API values
        const title = apiData.title || apiData.name;
        if (title) setFieldValue('title', String(title).trim());

        // Map brand
        const brandValue = apiData.brand || apiData.make || apiData.manufacturer || apiData.make_name;
        if (brandValue !== null && brandValue !== undefined) {
            const brandSelect = document.getElementById('brand_id');
            let brandFound = false;
            
            if (setSelectByIdOrText('brand_id', brandValue)) {
                if (brandSelect) {
                    brandSelect.dispatchEvent(new Event('change'));
                }
                brandFound = true;
                
                setTimeout(() => {
                    const modelValue = apiData.model || apiData.model_name || apiData.modelName;
                    if (modelValue !== null && modelValue !== undefined) {
                        if (!setSelectByIdOrText('model_id', modelValue)) {
                            let hiddenInput = document.getElementById('model_name_hidden');
                            if (!hiddenInput && vehicleForm) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.id = 'model_name_hidden';
                                hiddenInput.name = 'model_name';
                                vehicleForm.appendChild(hiddenInput);
                            }
                            if (hiddenInput) {
                                hiddenInput.value = typeof modelValue === 'object' ? modelValue.name : modelValue;
                            }
                        }
                    }
                }, 500);
            }
            
            if (!brandFound && typeof brandValue === 'string' && vehicleForm) {
                let brandHiddenInput = document.getElementById('brand_name_hidden');
                if (!brandHiddenInput) {
                    brandHiddenInput = document.createElement('input');
                    brandHiddenInput.type = 'hidden';
                    brandHiddenInput.id = 'brand_name_hidden';
                    brandHiddenInput.name = 'brand_name';
                    vehicleForm.appendChild(brandHiddenInput);
                }
                brandHiddenInput.value = brandValue;
            }
        }

        // Map fuel type
        const fuelTypeValue = apiData.fuel_type || apiData.fuelType || apiData.fuel || apiData.fuelTypeName;
        if (fuelTypeValue !== null && fuelTypeValue !== undefined) {
            setSelectByIdOrText('fuel_type_id', fuelTypeValue);
        }

        // Map model year
        let year = apiData.model_year || apiData.year || apiData.modelYear || apiData.registration_year;
        if (!year && apiData.first_registration_date) {
            const dateStr = apiData.first_registration_date;
            const yearMatch = dateStr.match(/^(\d{4})/);
            if (yearMatch) {
                year = yearMatch[1];
            }
        }
        if (year !== null && year !== undefined) {
            setSelectByIdOrText('model_year_id', year);
        }

        // Map category
        const categoryValue = apiData.category || apiData.vehicleType || apiData.vehicle_type || apiData.category_name;
        if (categoryValue !== null && categoryValue !== undefined) {
            setSelectByIdOrText('category_id', categoryValue);
        }

        // Numeric fields
        const price = apiData.price || apiData.price_dkk || apiData.list_price || apiData.priceDkk;
        if (price) setFieldValue('price', price);
        
        const mileage = apiData.mileage || apiData.km || apiData.odometer || apiData.odometer_reading || apiData.kmDriven;
        if (mileage) {
            setFieldValue('mileage', mileage);
            setFieldValue('km_driven', mileage);
        }
        
        if (apiData.batteryCapacity || apiData.battery_capacity) setFieldValue('battery_capacity', apiData.batteryCapacity || apiData.battery_capacity);
        if (apiData.enginePower || apiData.engine_power) setFieldValue('engine_power', apiData.enginePower || apiData.engine_power);
        if (apiData.towingWeight || apiData.towing_weight) setFieldValue('towing_weight', apiData.towingWeight || apiData.towing_weight);
        if (apiData.ownershipTax || apiData.ownership_tax) setFieldValue('ownership_tax', apiData.ownershipTax || apiData.ownership_tax);

        // First registration date
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

        // Map body type, color, use, type
        const bodyType = apiData.body_type || apiData.bodyType || apiData.body_style || apiData.vehicle_body;
        if (bodyType) setSelectByIdOrText('body_type_id', bodyType);

        const color = apiData.color || apiData.colour || apiData.paint_color || apiData.exterior_color;
        if (color) setSelectByIdOrText('color_id', color);
        
        const use = apiData.use || apiData.use_id;
        if (use) setSelectByIdOrText('use_id', use);

        const type = apiData.type || apiData.type_id;
        if (type) setSelectByIdOrText('type_id', type);

        // Additional detailed fields
        setFieldValue('description', apiData.description || apiData.notes || apiData.comments);
        setFieldValue('vin_location', apiData.vin_location || apiData.vinLocation);
        setFieldValue('version', apiData.version);
        setFieldValue('type_name', apiData.type_name || apiData.typeName);
        setFieldValue('engine_displacement', apiData.engine_displacement || apiData.engineDisplacement || apiData.displacement || apiData.displacement_cc);
        setFieldValue('engine_cylinders', apiData.engine_cylinders || apiData.engineCylinders || apiData.cylinders);
        setFieldValue('engine_code', apiData.engine_code || apiData.engineCode);
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
        setFieldValue('technical_total_weight', apiData.technical_total_weight || apiData.technicalTotalWeight);
        setFieldValue('minimum_weight', apiData.minimum_weight || apiData.minimumWeight);
        setFieldValue('gross_combination_weight', apiData.gross_combination_weight || apiData.grossCombinationWeight);
        setFieldValue('towing_weight', apiData.towing_weight || apiData.towingWeight);
        setFieldValue('towing_weight_brakes', apiData.towing_weight_brakes || apiData.towingWeightBrakes);
        
        // Handle coupling (boolean)
        if (apiData.coupling !== undefined && apiData.coupling !== null) {
            setFieldValue('coupling', apiData.coupling ? '1' : '0');
        }
        
        setFieldValue('wheelbase', apiData.wheelbase);
        setFieldValue('type_approval_code', apiData.type_approval_code || apiData.typeApprovalCode);
        setFieldValue('category', apiData.category);
        setFieldValue('wheels', apiData.wheels);
        setFieldValue('axles', apiData.axles);
        setFieldValue('drive_axles', apiData.drive_axles || apiData.driveAxles);
        setFieldValue('extra_equipment', apiData.extra_equipment || apiData.extraEquipment);
        setFieldValue('integrated_child_seats', apiData.integrated_child_seats || apiData.integratedChildSeats);
        setFieldValue('seat_belt_alarms', apiData.seat_belt_alarms || apiData.seatBeltAlarms);
        setFieldValue('euronorm', apiData.euronorm || apiData.euroNorm);
        
        // Registration status fields
        setFieldValue('registration_status', apiData.registration_status || apiData.registrationStatus);
        setFieldValue('vehicle_external_id', apiData.vehicle_external_id || apiData.vehicleExternalId || apiData.vehicle_id);
        
        // Registration status dates
        if (apiData.registration_status_updated_date || apiData.registrationStatusUpdatedDate) {
            try {
                const date = new Date(apiData.registration_status_updated_date || apiData.registrationStatusUpdatedDate);
                if (!isNaN(date.getTime())) {
                    setFieldValue('registration_status_updated_date', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid registration_status_updated_date format:', apiData.registration_status_updated_date);
            }
        }
        
        if (apiData.expire_date || apiData.expireDate) {
            try {
                const date = new Date(apiData.expire_date || apiData.expireDate);
                if (!isNaN(date.getTime())) {
                    setFieldValue('expire_date', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid expire_date format:', apiData.expire_date);
            }
        }
        
        if (apiData.status_updated_date || apiData.statusUpdatedDate) {
            try {
                const date = new Date(apiData.status_updated_date || apiData.statusUpdatedDate);
                if (!isNaN(date.getTime())) {
                    setFieldValue('status_updated_date', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid status_updated_date format:', apiData.status_updated_date);
            }
        }
        
        // Inspection fields
        if (apiData.last_inspection_date || apiData.lastInspectionDate) {
            try {
                const date = new Date(apiData.last_inspection_date || apiData.lastInspectionDate);
                if (!isNaN(date.getTime())) {
                    setFieldValue('last_inspection_date', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid last_inspection_date format:', apiData.last_inspection_date);
            }
        }
        
        setFieldValue('last_inspection_result', apiData.last_inspection_result || apiData.lastInspectionResult);
        setFieldValue('last_inspection_odometer', apiData.last_inspection_odometer || apiData.lastInspectionOdometer);
        
        // Leasing fields
        if (apiData.leasing_period_start || apiData.leasingPeriodStart) {
            try {
                const date = new Date(apiData.leasing_period_start || apiData.leasingPeriodStart);
                if (!isNaN(date.getTime())) {
                    setFieldValue('leasing_period_start', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid leasing_period_start format:', apiData.leasing_period_start);
            }
        }
        
        if (apiData.leasing_period_end || apiData.leasingPeriodEnd) {
            try {
                const date = new Date(apiData.leasing_period_end || apiData.leasingPeriodEnd);
                if (!isNaN(date.getTime())) {
                    setFieldValue('leasing_period_end', date.toISOString().split('T')[0]);
                }
            } catch (e) {
                console.warn('Invalid leasing_period_end format:', apiData.leasing_period_end);
            }
        }
        
        // Handle dispensations and permits
        if (apiData.dispensations) {
            const dispensationsValue = Array.isArray(apiData.dispensations) 
                ? apiData.dispensations.join(', ') 
                : apiData.dispensations;
            setFieldValue('dispensations', dispensationsValue);
        }
        
        if (apiData.permits) {
            const permitsValue = Array.isArray(apiData.permits) 
                ? apiData.permits.join(', ') 
                : apiData.permits;
            setFieldValue('permits', permitsValue);
        }
        
        // Handle ncap_five (boolean)
        if (apiData.ncap_five !== undefined) {
            setFieldValue('ncap_five', apiData.ncap_five ? '1' : '0');
        } else if (apiData.ncapFive !== undefined) {
            setFieldValue('ncap_five', apiData.ncapFive ? '1' : '0');
        }
        
        // Handle equipment array
        if (apiData.equipment && Array.isArray(apiData.equipment)) {
            apiData.equipment.forEach(function(equipId) {
                if (equipId) {
                    const checkbox = document.querySelector(`input[name="equipment_ids[]"][value="${equipId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log(`Checked equipment: ${equipId}`);
                    } else {
                        const actualId = typeof equipId === 'object' && equipId.id ? equipId.id : equipId;
                        const checkboxAlt = document.querySelector(`input[name="equipment_ids[]"][value="${actualId}"]`);
                        if (checkboxAlt) {
                            checkboxAlt.checked = true;
                            console.log(`Checked equipment: ${actualId}`);
                        }
                    }
                }
            });
        }
        
        console.log('Form prefilling completed');
    }
})();

