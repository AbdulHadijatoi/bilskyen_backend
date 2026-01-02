// Sell Your Car Form Handler
// Handles expandable sections, form submission via AJAX, lookup, and form prefilling

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSellYourCarForm);
    } else {
        initSellYourCarForm();
    }

    function initSellYourCarForm() {
        // Initialize expandable sections
        initExpandableSections();
        
        // Initialize brand/model loading
        initBrandModelLoading();
        
        // Initialize registration lookup
        initRegistrationLookup();
        
        // Initialize form submission
        initFormSubmission();
    }

    // Expandable Sections
    function initExpandableSections() {
        // Make toggleSection available globally
        window.toggleSection = function(sectionId) {
            const section = document.querySelector(`[data-section="${sectionId}"]`);
            if (!section) return;
            
            const header = section.querySelector('.section-header');
            const content = section.querySelector('.section-content');
            
            if (!header || !content) return;
            
            const isExpanded = content.classList.contains('expanded');
            
            if (isExpanded) {
                content.classList.remove('expanded');
                header.classList.remove('active');
            } else {
                content.classList.add('expanded');
                header.classList.add('active');
                
                // Smooth scroll to section
                setTimeout(() => {
                    section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        };
        
        // Expand all sections
        window.expandAllSections = function() {
            document.querySelectorAll('.expandable-section').forEach(section => {
                const header = section.querySelector('.section-header');
                const content = section.querySelector('.section-content');
                if (header && content) {
                    content.classList.add('expanded');
                    header.classList.add('active');
                }
            });
        };
        
        // Collapse all sections
        window.collapseAllSections = function() {
            document.querySelectorAll('.expandable-section').forEach(section => {
                const header = section.querySelector('.section-header');
                const content = section.querySelector('.section-content');
                if (header && content) {
                    content.classList.remove('expanded');
                    header.classList.remove('active');
                }
            });
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
        const registrationInput = document.getElementById('registration-lookup');
        const lookupError = document.getElementById('lookup-error');
        const lookupLoading = document.getElementById('lookup-loading');
        const vehicleForm = document.getElementById('vehicle-form');

        if (!registrationInput) return;

        function performLookup() {
            const registration = registrationInput.value.trim();
            
            if (!registration) {
                lookupError.textContent = 'Please enter a license plate number';
                lookupError.style.color = 'var(--destructive)';
                return;
            }

            lookupError.textContent = '';
            lookupError.style.color = '';
            lookupLoading.classList.remove('hidden');
            registrationInput.disabled = true;

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
                registrationInput.disabled = false;

                if (data.status === 'error' || !data.data) {
                    let errorMessage = data.message || 'Failed to fetch vehicle information';
                    
                    if (data.errors && data.errors.code === 'TIMEOUT') {
                        errorMessage = 'The vehicle lookup is taking longer than expected. Please try again in a moment.';
                    } else if (data.errors && data.errors.retryable) {
                        errorMessage = 'The vehicle lookup service is temporarily unavailable. Please try again in a moment.';
                    }
                    
                    lookupError.textContent = errorMessage;
                    lookupError.style.color = 'var(--destructive)';
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
                    const errorMsg = 'No vehicle data found in API response. Please try again.';
                    lookupError.textContent = errorMsg;
                    lookupError.style.color = 'var(--destructive)';
                    return;
                }
                
                // Show the form
                if (vehicleForm) {
                    vehicleForm.classList.remove('form-hidden');
                    vehicleForm.classList.add('form-visible');
                }
                
                // Prefill form and expand relevant sections
                prefillForm(vehicleData);
                
                // Expand essential section by default
                setTimeout(() => {
                    window.toggleSection('essential');
                }, 100);
                
                // Show success message
                const successMsg = document.createElement('div');
                successMsg.className = 'success-badge';
                successMsg.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Vehicle information loaded successfully! Review and complete the form below.</span>
                `;
                if (vehicleForm) {
                    vehicleForm.insertBefore(successMsg, vehicleForm.firstChild);
                    setTimeout(() => {
                        successMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                    setTimeout(() => successMsg.remove(), 8000);
                }
            })
            .catch(error => {
                lookupLoading.classList.add('hidden');
                registrationInput.disabled = false;
                lookupError.textContent = 'An error occurred while fetching vehicle information. Please try again.';
                lookupError.style.color = 'var(--destructive)';
                console.error('Lookup error:', error);
            });
        }

        // Trigger lookup on Enter key
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
        
        // Clear previous errors
        clearErrors();
        
        // Validate required fields
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach(field => {
            field.classList.remove('border-red-500');
            field.classList.add('border-input');
            
            const errorMsg = field.parentElement.querySelector('.field-error');
            if (errorMsg) {
                errorMsg.remove();
            }

            if (!field.value || (field.type === 'number' && field.value < 0)) {
                isValid = false;
                field.classList.remove('border-input');
                field.classList.add('border-red-500');
                
                const errorElement = document.createElement('p');
                errorElement.className = 'field-error';
                errorElement.textContent = 'This field is required';
                field.parentElement.appendChild(errorElement);

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        if (!isValid) {
            if (firstInvalidField) {
                // Find and expand the section containing the invalid field
                const section = firstInvalidField.closest('.expandable-section');
                if (section) {
                    const sectionId = section.getAttribute('data-section');
                    const header = section.querySelector('.section-header');
                    const content = section.querySelector('.section-content');
                    if (header && content && !content.classList.contains('expanded')) {
                        content.classList.add('expanded');
                        header.classList.add('active');
                    }
                }
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
            }
            return;
        }
        
        // Create FormData BEFORE disabling form fields
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
                hideLoadingState(submitBtn, form);
                displayGeneralError('An unexpected error occurred. Please try again.');
                return;
            }

            hideLoadingState(submitBtn, form);

            if (!response.ok || data.status === 'error') {
                if (data.errors) {
                    displayErrors(data.errors);
                    // Expand section with first error
                    const firstErrorField = Object.keys(data.errors)[0];
                    const field = document.querySelector(`[name="${firstErrorField}"]`);
                    if (field) {
                        const section = field.closest('.expandable-section');
                        if (section) {
                            const sectionId = section.getAttribute('data-section');
                            const header = section.querySelector('.section-header');
                            const content = section.querySelector('.section-content');
                            if (header && content && !content.classList.contains('expanded')) {
                                content.classList.add('expanded');
                                header.classList.add('active');
                            }
                        }
                        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        field.focus();
                    }
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

        // Add loading overlay
        if (!document.querySelector('.loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
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

        // Remove loading overlay
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Error display functions
    function clearErrors() {
        const topErrorContainer = document.getElementById('form-errors-top');
        if (topErrorContainer) {
            topErrorContainer.innerHTML = '';
            topErrorContainer.classList.add('hidden');
        }

        document.querySelectorAll('.field-error').forEach(el => el.remove());
        
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
        
        if (typeof errors === 'object' && !Array.isArray(errors)) {
            const errorMessages = [];
            
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

        field.classList.remove('border-input');
        field.classList.add('border-red-500');

        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        const errorElement = document.createElement('p');
        errorElement.className = 'field-error';
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
            
            if (typeof value === 'number' || (typeof value === 'string' && /^\d+$/.test(value))) {
                const idValue = String(value);
                if (select.querySelector(`option[value="${idValue}"]`)) {
                    select.value = idValue;
                    console.log(`Set ${selectId} = ${idValue} (by ID)`);
                    return true;
                }
            }
            
            if (typeof value === 'object' && value !== null && value.id !== undefined) {
                const idValue = String(value.id);
                if (select.querySelector(`option[value="${idValue}"]`)) {
                    select.value = idValue;
                    console.log(`Set ${selectId} = ${idValue} (from object.id)`);
                    return true;
                }
            }
            
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
        if (registration) {
            setFieldValue('registration', registration);
            setFieldValue('registration-lookup', registration);
        }
        
        const vin = apiData.vin || apiData.chassis_number || apiData.chassis || apiData.chassisNumber;
        if (vin) setFieldValue('vin', vin);
        
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
                            const vehicleForm = document.getElementById('vehicle-form');
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
            
            if (!brandFound && typeof brandValue === 'string') {
                const vehicleForm = document.getElementById('vehicle-form');
                let brandHiddenInput = document.getElementById('brand_name_hidden');
                if (!brandHiddenInput && vehicleForm) {
                    brandHiddenInput = document.createElement('input');
                    brandHiddenInput.type = 'hidden';
                    brandHiddenInput.id = 'brand_name_hidden';
                    brandHiddenInput.name = 'brand_name';
                    vehicleForm.appendChild(brandHiddenInput);
                }
                if (brandHiddenInput) {
                    brandHiddenInput.value = brandValue;
                }
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

        // Additional fields
        setFieldValue('description', apiData.description || apiData.notes || apiData.comments);
        
        // Handle equipment array
        if (apiData.equipment && Array.isArray(apiData.equipment)) {
            apiData.equipment.forEach(function(equipId) {
                if (equipId) {
                    const actualId = typeof equipId === 'object' && equipId.id ? equipId.id : equipId;
                    const checkbox = document.querySelector(`input[name="equipment_ids[]"][value="${actualId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        // Trigger change handler to update UI
                        const equipmentName = checkbox.closest('.equipment-item')?.querySelector('.equipment-name')?.textContent || '';
                        if (window.handleEquipmentChange) {
                            window.handleEquipmentChange(checkbox, actualId, equipmentName);
                        }
                    }
                }
            });
        }
        
        // Auto-expand sections that have been filled
        const sectionsToExpand = ['essential', 'details'];
        if (apiData.enginePower || apiData.batteryCapacity) {
            sectionsToExpand.push('technical');
        }
        if (apiData.color || apiData.bodyType) {
            sectionsToExpand.push('additional');
        }
        if (apiData.equipment && apiData.equipment.length > 0) {
            sectionsToExpand.push('equipment');
        }
        
        sectionsToExpand.forEach(sectionId => {
            window.toggleSection(sectionId);
        });
        
        console.log('Form prefilling completed');
    }
    
    // Equipment selection handlers
    window.handleEquipmentChange = function(checkbox, equipmentId, equipmentName) {
        const isChecked = checkbox.checked;
        const equipmentItem = checkbox.closest('.equipment-item');
        const category = equipmentItem.closest('.equipment-category');
        
        if (isChecked) {
            equipmentItem.classList.add('selected');
            if (category) {
                category.classList.add('has-selected');
            }
            // Move to top of category
            if (category) {
                const grid = category.querySelector('.equipment-grid');
                if (grid && equipmentItem.parentElement === grid) {
                    grid.insertBefore(equipmentItem, grid.firstChild);
                }
            }
        } else {
            equipmentItem.classList.remove('selected');
            // Check if category has any selected items
            if (category) {
                const hasSelected = category.querySelector('.equipment-item.selected');
                if (!hasSelected) {
                    category.classList.remove('has-selected');
                }
            }
        }
        
        updateSelectedEquipmentSummary();
    };
    
    window.clearAllEquipment = function() {
        const checkboxes = document.querySelectorAll('.equipment-checkbox:checked');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const equipmentId = parseInt(checkbox.value);
            const equipmentName = checkbox.closest('.equipment-item').querySelector('.equipment-name').textContent;
            handleEquipmentChange(checkbox, equipmentId, equipmentName);
        });
    };
    
    function updateSelectedEquipmentSummary() {
        const selectedCheckboxes = document.querySelectorAll('.equipment-checkbox:checked');
        const summaryContainer = document.getElementById('selected-equipment-summary');
        const summaryList = document.getElementById('selected-equipment-list');
        const countElement = document.getElementById('selected-count');
        
        if (!summaryContainer || !summaryList || !countElement) return;
        
        const count = selectedCheckboxes.length;
        countElement.textContent = count;
        
        if (count === 0) {
            summaryContainer.classList.add('hidden');
            return;
        }
        
        summaryContainer.classList.remove('hidden');
        summaryList.innerHTML = '';
        
        selectedCheckboxes.forEach(checkbox => {
            const equipmentItem = checkbox.closest('.equipment-item');
            const equipmentName = equipmentItem.querySelector('.equipment-name').textContent;
            const equipmentId = parseInt(checkbox.value);
            
            const badge = document.createElement('div');
            badge.className = 'selected-equipment-badge';
            badge.innerHTML = `
                <span>${escapeHtml(equipmentName)}</span>
                <button type="button" onclick="removeEquipment(${equipmentId})" aria-label="Remove ${escapeHtml(equipmentName)}">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;
            summaryList.appendChild(badge);
        });
    }
    
    window.removeEquipment = function(equipmentId) {
        const checkbox = document.querySelector(`.equipment-checkbox[value="${equipmentId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            const equipmentName = checkbox.closest('.equipment-item').querySelector('.equipment-name').textContent;
            handleEquipmentChange(checkbox, equipmentId, equipmentName);
        }
    };
    
    // Initialize equipment summary on page load
    function initEquipmentSummary() {
        // Update summary for any pre-checked items (from form prefilling)
        setTimeout(() => {
            updateSelectedEquipmentSummary();
            
            // Move selected items to top
            document.querySelectorAll('.equipment-checkbox:checked').forEach(checkbox => {
                const equipmentItem = checkbox.closest('.equipment-item');
                const category = equipmentItem.closest('.equipment-category');
                if (equipmentItem && category) {
                    equipmentItem.classList.add('selected');
                    category.classList.add('has-selected');
                    const grid = category.querySelector('.equipment-grid');
                    if (grid && equipmentItem.parentElement === grid) {
                        grid.insertBefore(equipmentItem, grid.firstChild);
                    }
                }
            });
        }, 100);
    }
    
    // Call init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initEquipmentSummary();
            initImageUpload();
        });
    } else {
        initEquipmentSummary();
        initImageUpload();
    }
    
    // Image Upload Handlers - Rewritten with cleaner architecture
    // Single source of truth: fileMap stores File objects with unique IDs
    const imageUploadState = {
        fileMap: new Map(), // Map<fileId, File> - single source of truth
        fileInput: null,
        isUpdating: false, // Flag to prevent recursive updates
        maxSize: 10 * 1024 * 1024, // 10MB
        allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']
    };
    
    // Generate unique file ID using file properties (more robust than name_size)
    function getFileId(file) {
        // Use a combination that's unique: name, size, lastModified, and type
        // This handles edge cases better than just name_size and avoids underscore issues
        return `${file.name}|${file.size}|${file.lastModified}|${file.type}`;
    }
    
    // Check if file is duplicate
    function isDuplicateFile(file) {
        const fileId = getFileId(file);
        return imageUploadState.fileMap.has(fileId);
    }
    
    // Validate a single file
    function validateFile(file) {
        if (isDuplicateFile(file)) {
            return { valid: false, error: `File "${file.name}" is already selected.` };
        }
        
        if (!imageUploadState.allowedTypes.includes(file.type)) {
            return { valid: false, error: `File "${file.name}" is not a valid image format. Please use JPEG, PNG, or GIF.` };
        }
        
        if (file.size > imageUploadState.maxSize) {
            return { valid: false, error: `File "${file.name}" is too large. Maximum size is 10MB.` };
        }
        
        return { valid: true };
    }
    
    // Add files to the file map and update the input
    function addFiles(files) {
        if (imageUploadState.isUpdating) return;
        
        const validFiles = [];
        const errors = [];
        
        // Validate all files first
        Array.from(files).forEach(file => {
            const validation = validateFile(file);
            if (validation.valid) {
                validFiles.push(file);
            } else {
                errors.push(validation.error);
            }
        });
        
        // Show errors if any
        if (errors.length > 0) {
            errors.forEach(error => displayImageError(error));
        }
        
        // Add valid files to map
        validFiles.forEach(file => {
            const fileId = getFileId(file);
            imageUploadState.fileMap.set(fileId, file);
        });
        
        // Update file input and previews
        if (validFiles.length > 0 || errors.length > 0) {
            syncFileInput();
            updateImagePreviews();
        }
    }
    
    // Remove a file by its ID
    function removeFileById(fileId) {
        if (imageUploadState.isUpdating) return;
        
        if (imageUploadState.fileMap.delete(fileId)) {
            syncFileInput();
            updateImagePreviews();
        }
    }
    
    // Sync the file input with the file map
    function syncFileInput() {
        if (!imageUploadState.fileInput) return;
        
        imageUploadState.isUpdating = true;
        try {
            const dataTransfer = new DataTransfer();
            
            // Add all files from map to DataTransfer
            imageUploadState.fileMap.forEach(file => {
                try {
                    dataTransfer.items.add(file);
                } catch (e) {
                    console.error('Error adding file to DataTransfer:', e, file.name);
                }
            });
            
            // Update file input
            imageUploadState.fileInput.files = dataTransfer.files;
        } finally {
            // Use setTimeout to ensure change event doesn't fire during update
            setTimeout(() => {
                imageUploadState.isUpdating = false;
            }, 50);
        }
    }
    
    // Initialize image upload functionality
    function initImageUpload() {
        const dropzone = document.getElementById('upload-dropzone');
        const fileInput = document.getElementById('images');
        
        if (!dropzone || !fileInput) return;
        
        imageUploadState.fileInput = fileInput;
        
        // Initialize file map from existing files in input
        if (fileInput.files && fileInput.files.length > 0) {
            Array.from(fileInput.files).forEach(file => {
                const fileId = getFileId(file);
                imageUploadState.fileMap.set(fileId, file);
            });
            updateImagePreviews();
        }
        
        // Handle file input change (user selection via dialog)
        fileInput.addEventListener('change', function(e) {
            // Skip if we're programmatically updating
            if (imageUploadState.isUpdating) {
                e.target.value = '';
                return;
            }
            
            const newFiles = e.target.files;
            if (newFiles && newFiles.length > 0) {
                addFiles(newFiles);
                // Clear the input value to allow selecting the same file again
                e.target.value = '';
            }
        });
        
        // Drag and drop handlers
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                addFiles(files);
            }
        });
        
        // Click to upload
        dropzone.addEventListener('click', function(e) {
            if (e.target !== fileInput && !fileInput.contains(e.target)) {
                fileInput.click();
            }
        });
    }
    
    // Update previews based on current file map
    function updateImagePreviews() {
        const container = document.getElementById('image-preview-container');
        const grid = document.getElementById('image-preview-grid');
        const countElement = document.getElementById('image-count');
        const uploadArea = document.getElementById('image-upload-area');
        
        if (!container || !grid) return;
        
        const fileCount = imageUploadState.fileMap.size;
        
        if (fileCount === 0) {
            container.classList.add('hidden');
            if (uploadArea) {
                uploadArea.classList.remove('has-images');
            }
            return;
        }
        
        container.classList.remove('hidden');
        if (uploadArea) {
            uploadArea.classList.add('has-images');
        }
        if (countElement) {
            countElement.textContent = fileCount;
        }
        
        // Clear existing previews
        grid.innerHTML = '';
        
        // Create preview for each file in the map
        imageUploadState.fileMap.forEach((file, fileId) => {
            const previewItem = createImagePreview(file, fileId);
            grid.appendChild(previewItem);
        });
    }
    
    // Create preview element for a file
    function createImagePreview(file, fileId) {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.dataset.fileId = fileId;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            item.insertBefore(img, item.firstChild);
        };
        reader.readAsDataURL(file);
        
        // File info
        const fileSize = formatFileSize(file.size);
        const info = document.createElement('div');
        info.className = 'image-preview-info';
        info.textContent = `${file.name} (${fileSize})`;
        item.appendChild(info);
        
        // Overlay with remove button
        const overlay = document.createElement('div');
        overlay.className = 'image-preview-overlay';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'image-remove-btn';
        removeBtn.setAttribute('data-file-id', fileId);
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const targetFileId = this.getAttribute('data-file-id') || 
                                this.closest('.image-preview-item')?.dataset.fileId;
            if (targetFileId) {
                removeFileById(targetFileId);
            }
        });
        removeBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        `;
        overlay.appendChild(removeBtn);
        item.appendChild(overlay);
        
        return item;
    }
    
    // Public function to remove image by fileId
    window.removeImage = function(fileId) {
        removeFileById(fileId);
    };
    
    // Public function to clear all images
    window.clearAllImages = function() {
        if (imageUploadState.isUpdating) return;
        
        imageUploadState.fileMap.clear();
        syncFileInput();
        updateImagePreviews();
    };
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    function displayImageError(message) {
        // You can integrate this with your existing error display system
        const errorContainer = document.getElementById('form-errors-top');
        if (errorContainer) {
            errorContainer.innerHTML = `<p class="text-sm font-medium">${escapeHtml(message)}</p>`;
            errorContainer.classList.remove('hidden');
            errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            setTimeout(() => {
                errorContainer.classList.add('hidden');
            }, 5000);
        } else {
            alert(message);
        }
    }
})();
