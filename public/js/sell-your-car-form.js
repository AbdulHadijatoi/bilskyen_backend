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
        
        // Initialize registration lookup
        initRegistrationLookup();
        
        // Initialize form submission
        initFormSubmission();
        
        // Initialize equipment collapsible
        initEquipmentCollapsible();
        
        // Initialize image upload
        initImageUpload();
        
        // Initialize description auto-generation
        initDescriptionAutoGeneration();
        
        // Initialize tax info toggle
        initTaxInfoToggle();
        
        // Initialize fuel efficiency label updater
        initFuelEfficiencyLabelUpdater();
        
        // Ensure fuel_efficiency field always allows decimals (step="any") unless explicitly electric
        // This runs after all initialization to override any API-driven changes
        setTimeout(function() {
            const fuelEfficiencyInput = document.getElementById('fuel_efficiency');
            if (fuelEfficiencyInput) {
                // Check if current step is "1" - if so, check if we should keep it or change to "any"
                const currentStep = fuelEfficiencyInput.getAttribute('step');
                const currentValue = fuelEfficiencyInput.value;
                
                // If step is "1" and field is empty or has decimal, change to "any"
                // This allows users to enter decimal values like 54.5
                if (currentStep === '1') {
                    // Only keep step="1" if field is empty AND we're certain it's electric
                    // Otherwise, default to step="any" to allow decimals
                    if (currentValue && (currentValue.includes('.') || parseFloat(currentValue) % 1 !== 0)) {
                        fuelEfficiencyInput.setAttribute('step', 'any');
                        fuelEfficiencyInput.setAttribute('inputmode', 'decimal');
                        fuelEfficiencyInput.placeholder = '0.00';
                    } else {
                        // Even if empty, default to step="any" unless we're absolutely certain it's electric
                        // Check if there's a fuel type select that explicitly says electric
                        const fuelTypeSelect = document.getElementById('fuel_type_id');
                        if (!fuelTypeSelect || !fuelTypeSelect.value) {
                            // No fuel type selected - default to step="any"
                            fuelEfficiencyInput.setAttribute('step', 'any');
                            fuelEfficiencyInput.setAttribute('inputmode', 'decimal');
                            fuelEfficiencyInput.placeholder = '0.00';
                        }
                    }
                }
            }
        }, 200);
    }

    // Expandable Sections
    function initExpandableSections() {
        // Expand all sections by default on page load
        document.querySelectorAll('.expandable-section').forEach(section => {
            const header = section.querySelector('.section-header');
            const content = section.querySelector('.section-content');
            if (header && content) {
                content.classList.add('expanded');
                header.classList.add('active');
            }
        });
        
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

                console.log('API Response received:', data);

                // Check for error status first
                if (data.status === 'error') {
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
                // Handle nested structure: { data: { data: { ...vehicle data... } } }
                let vehicleData = null;
                
                if (data.data && data.data.data && typeof data.data.data === 'object') {
                    // Nested structure: data.data.data (most common)
                    vehicleData = data.data.data;
                } else if (data.data && typeof data.data === 'object' && !Array.isArray(data.data) && data.data.registration) {
                    // Single nested: data.data with registration field
                    vehicleData = data.data;
                } else if (data.vehicle && typeof data.vehicle === 'object') {
                    vehicleData = data.vehicle;
                } else if (Array.isArray(data) && data.length > 0) {
                    vehicleData = data[0];
                } else if (data.status === 'success' && data.data) {
                    vehicleData = data.data;
                } else if (typeof data === 'object' && !data.status && !data.errors && data.registration) {
                    // Direct vehicle data object
                    vehicleData = data;
                }
                
                console.log('Extracted vehicle data:', vehicleData);
                
                if (!vehicleData || typeof vehicleData !== 'object' || !vehicleData.registration) {
                    console.error('Vehicle data extraction failed. Response structure:', JSON.stringify(data, null, 2));
                    const errorMsg = 'No vehicle data found in API response. Please try again.';
                    lookupError.textContent = errorMsg;
                    lookupError.style.color = 'var(--destructive)';
                    return;
                }
                
                // Store complete API response data for form submission
                window.apiResponseData = vehicleData;
                
                // Set registration in hidden field before showing form
                const registrationHidden = document.getElementById('registration');
                if (registrationHidden && registration) {
                    registrationHidden.value = registration;
                }
                
                // Show the form
                if (vehicleForm) {
                    vehicleForm.classList.remove('form-hidden');
                    vehicleForm.classList.add('form-visible');
                }
                
                // Ensure all sections are expanded by default
                document.querySelectorAll('.expandable-section').forEach(section => {
                    const header = section.querySelector('.section-header');
                    const content = section.querySelector('.section-content');
                    if (header && content) {
                        content.classList.add('expanded');
                        header.classList.add('active');
                    }
                });
                
                // Prefill form (will set fields by name, not ID)
                prefillForm(vehicleData);
                
                // Update fuel efficiency label after prefilling (in case fuel_type was set)
                if (vehicleData.fuel_type && vehicleData.fuel_type.id) {
                    updateFuelEfficiencyLabel(vehicleData.fuel_type.id);
                } else if (vehicleData.fuel_type_id) {
                    updateFuelEfficiencyLabel(vehicleData.fuel_type_id);
                }
                
                // Set title from backend response (simple - just set it)
                if (vehicleData.title) {
                    const titleDisplay = document.getElementById('title-display');
                    const titleInput = document.getElementById('title');
                    if (titleDisplay) {
                        titleDisplay.textContent = vehicleData.title;
                    }
                    if (titleInput) {
                        titleInput.value = vehicleData.title;
                    }
                }
                
                // Update vehicle info display (brand, model, year, fuel type)
                updateVehicleInfoDisplay(vehicleData);
                
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
                
                console.error('Lookup error:', error);
                
                // Try to extract error message from error object
                let errorMessage = 'An error occurred while fetching vehicle information. Please try again.';
                
                if (error && typeof error === 'object') {
                    if (error.message) {
                        errorMessage = error.message;
                    } else if (error.errors && typeof error.errors === 'object') {
                        const errorKeys = Object.keys(error.errors);
                        if (errorKeys.length > 0) {
                            const firstError = Array.isArray(error.errors[errorKeys[0]]) 
                                ? error.errors[errorKeys[0]][0] 
                                : error.errors[errorKeys[0]];
                            errorMessage = firstError || errorMessage;
                        }
                    } else if (error.status === 'error' && error.message) {
                        errorMessage = error.message;
                    }
                } else if (typeof error === 'string') {
                    errorMessage = error;
                }
                
                lookupError.textContent = errorMessage;
                lookupError.style.color = 'var(--destructive)';
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
        
        // Validate images are required
        const imageCount = imageUploadState && imageUploadState.fileMap ? imageUploadState.fileMap.size : 0;
        if (imageCount === 0) {
            isValid = false;
            const photosSection = document.querySelector('[data-section="photos"]');
            if (photosSection) {
                const header = photosSection.querySelector('.section-header');
                const content = photosSection.querySelector('.section-content');
                if (header && content && !content.classList.contains('expanded')) {
                    content.classList.add('expanded');
                    header.classList.add('active');
                }
                photosSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            displayGeneralError('Please upload at least one image of your vehicle.');
            return;
        }

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
        
        // Generate description before submission if not already done
        generateDescription();
        
        // Create FormData BEFORE disabling form fields
        const formData = new FormData(form);
        
        // Merge all API response fields into form data
        if (window.apiResponseData && typeof window.apiResponseData === 'object') {
            const apiData = window.apiResponseData;
            
            // Helper function to safely add value to FormData
            const addToFormData = (key, value) => {
                if (value === null || value === undefined) {
                    return; // Skip null/undefined values
                }
                
                if (Array.isArray(value)) {
                    // Handle arrays - convert to JSON string for complex arrays, or append each item for simple arrays
                    if (value.length > 0 && typeof value[0] === 'object' && value[0] !== null) {
                        // Complex array (like equipment, dispensations, permits) - convert to JSON
                        formData.append(key, JSON.stringify(value));
                    } else {
                        // Simple array - append each item
                        value.forEach(item => {
                            formData.append(key + '[]', item);
                        });
                    }
                } else if (typeof value === 'object' && value !== null) {
                    // Handle objects - extract ID if it's a lookup object, or convert to JSON
                    if (value.id !== undefined && value.name !== undefined) {
                        // Lookup object (color, variant, euronorm, body_type, use, type) - use ID
                        formData.append(key.replace('_object', '_id'), value.id);
                    } else {
                        // Other objects - convert to JSON
                        formData.append(key, JSON.stringify(value));
                    }
                } else if (typeof value === 'boolean') {
                    // Convert boolean to integer (0 or 1)
                    formData.append(key, value ? '1' : '0');
                } else {
                    // String, number, etc. - add as-is
                    formData.append(key, value);
                }
            };
            
            // Map API fields to form fields, handling special cases
            Object.keys(apiData).forEach(key => {
                // Skip fields that are already in the form or are internal processing fields
                if (key === 'title' || key === 'registration' || 
                    key === 'brand_name' || key === 'model_name' || key === 'model_year_name' || key === 'fuel_type_name') {
                    // These are internal processing fields - skip
                    return;
                }
                
                // Handle brand, model, model_year, fuel_type objects - extract IDs
                if (key === 'brand' && typeof apiData[key] === 'object' && apiData[key] !== null && apiData[key].id) {
                    formData.append('brand_id', apiData[key].id);
                    return;
                }
                if (key === 'model' && typeof apiData[key] === 'object' && apiData[key] !== null && apiData[key].id) {
                    formData.append('model_id', apiData[key].id);
                    return;
                }
                if (key === 'model_year' && typeof apiData[key] === 'object' && apiData[key] !== null && apiData[key].id) {
                    formData.append('model_year_id', apiData[key].id);
                    return;
                }
                if (key === 'fuel_type' && typeof apiData[key] === 'object' && apiData[key] !== null && apiData[key].id) {
                    formData.append('fuel_type_id', apiData[key].id);
                    return;
                }
                
                // Handle null model_year (might not be in API response)
                if (key === 'model_year' && (apiData[key] === null || apiData[key] === undefined)) {
                    // Skip null model_year - don't send it
                    return;
                }
                
                // Skip equipment, color, variant, euronorm - handled separately below
                if (key === 'equipment' || key === 'color' || key === 'variant' || key === 'euronorm') {
                    return;
                }
                
                const value = apiData[key];
                
                // Special mappings
                if (key === 'vehicle_id') {
                    // Map vehicle_id from API to vehicle_external_id for database
                    addToFormData('vehicle_external_id', value);
                } else if (key === 'type' && typeof value === 'object' && value !== null && value.id) {
                    // Type object - extract ID
                    addToFormData('type_id', value.id);
                    if (value.name) {
                        addToFormData('type_name', value.name);
                    }
                } else if (key === 'use' && typeof value === 'object' && value !== null && value.id) {
                    // Use object - extract ID
                    addToFormData('use_id', value.id);
                } else if (key === 'body_type' && typeof value === 'object' && value !== null && value.id) {
                    // Body type object - extract ID
                    addToFormData('body_type_id', value.id);
                } else if (key === 'dispensations' || key === 'permits') {
                    // Arrays - convert to JSON string
                    addToFormData(key, value);
                } else if (key === 'coupling' || key === 'ncap_five') {
                    // Boolean fields - convert to integer
                    addToFormData(key, value);
                } else {
                    // Regular field - add as-is
                    addToFormData(key, value);
                }
            });
            
            // Handle equipment array separately (already handled in form, but include quantities if needed)
            if (apiData.equipment && Array.isArray(apiData.equipment)) {
                // Equipment IDs are already in form, but we could store quantities here if needed
                // For now, just ensure equipment data is available
            }
            
            // Handle color, variant, euronorm objects - extract IDs if not already in form
            if (apiData.color && typeof apiData.color === 'object' && apiData.color.id) {
                const colorId = formData.get('color_id');
                if (!colorId || colorId === '') {
                    formData.set('color_id', apiData.color.id);
                }
            }
            
            if (apiData.variant && typeof apiData.variant === 'object' && apiData.variant.id) {
                const variantId = formData.get('variant_id');
                if (!variantId || variantId === '') {
                    formData.set('variant_id', apiData.variant.id);
                }
            }
            
            if (apiData.euronorm && typeof apiData.euronorm === 'object' && apiData.euronorm.id) {
                const euronomId = formData.get('euronom_id');
                if (!euronomId || euronomId === '') {
                    formData.set('euronom_id', apiData.euronorm.id);
                }
            }
            
            console.log('Merged API response data into form submission');
        }
        
        // Handle variant name if variant_id is not set but variant name exists
        const variantSelect = document.getElementById('variant_id');
        if (variantSelect && !variantSelect.value) {
            // Check if there's a variant name input (from API)
            const variantNameInput = document.getElementById('variant_name_hidden');
            if (variantNameInput && variantNameInput.value) {
                formData.append('variant_name', variantNameInput.value);
            }
        }
        
        // Handle euronom name if euronom_id is not set but euronom name exists
        const euronomSelect = document.getElementById('euronom_id');
        if (euronomSelect && !euronomSelect.value) {
            const euronomNameInput = document.getElementById('euronom_name_hidden');
            if (euronomNameInput && euronomNameInput.value) {
                formData.append('euronom_name', euronomNameInput.value);
            }
        }
        
        // Manually append files from fileMap to ensure they're included
        // This is necessary because files added via DataTransfer API might not be
        // properly included when FormData is created from the form
        if (imageUploadState && imageUploadState.fileMap && imageUploadState.fileMap.size > 0) {
            // Remove any existing images[] entries from FormData to avoid duplicates
            // Note: FormData.delete() removes all entries with the given key
            formData.delete('images[]');
            formData.delete('images');
            
            // Validate and append all files from fileMap in order
            let validFileCount = 0;
            const fileErrors = [];
            
            imageUploadState.fileOrder.forEach((fileId, index) => {
                const file = imageUploadState.fileMap.get(fileId);
                if (!file) {
                    console.warn(`File not found in fileMap for fileId: ${fileId}`);
                    fileErrors.push(`Image ${index + 1} is missing or invalid.`);
                    return;
                }
                
                // Validate file object
                if (!(file instanceof File)) {
                    console.error(`Invalid file object at index ${index}:`, file);
                    fileErrors.push(`Image ${index + 1} is not a valid file.`);
                    return;
                }
                
                // Validate file size (20MB = 20 * 1024 * 1024 bytes)
                const maxSize = 20 * 1024 * 1024;
                if (file.size > maxSize) {
                    fileErrors.push(`Image ${index + 1} (${file.name}) exceeds 20MB limit.`);
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    fileErrors.push(`Image ${index + 1} (${file.name}) has invalid file type. Only JPEG, PNG, and GIF are allowed.`);
                    return;
                }
                
                // Append valid file
                try {
                    formData.append('images[]', file, file.name);
                    validFileCount++;
                } catch (e) {
                    console.error(`Error appending file ${file.name}:`, e);
                    fileErrors.push(`Failed to upload image ${index + 1} (${file.name}).`);
                }
            });
            
            // Show validation errors if any
            if (fileErrors.length > 0) {
                hideLoadingState(submitBtn, form);
                displayGeneralError(fileErrors.join('<br>'));
                const photosSection = document.querySelector('[data-section="photos"]');
                if (photosSection) {
                    photosSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            
            if (validFileCount === 0) {
                hideLoadingState(submitBtn, form);
                displayGeneralError('No valid images found. Please upload at least one image.');
                return;
            }
            
            console.log(`Added ${validFileCount} valid image(s) to FormData:`, 
                imageUploadState.fileOrder.map(fileId => {
                    const file = imageUploadState.fileMap.get(fileId);
                    return file ? `${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB)` : 'unknown';
                })
            );
        } else {
            console.log('No images in fileMap to add to FormData');
        }
        
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
        document.querySelectorAll('.image-upload-error').forEach(el => el.remove());
        
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
            const imageErrors = [];
            
            // First pass: collect image errors separately
            Object.keys(errors).forEach(field => {
                if (field.startsWith('images.')) {
                    const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                    fieldErrors.forEach(error => {
                        imageErrors.push(error);
                    });
                }
            });
            
            // Display image errors together if any
            if (imageErrors.length > 0) {
                displayImageErrors(imageErrors);
            }
            
            // Second pass: handle other errors
            Object.keys(errors).forEach(field => {
                const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                fieldErrors.forEach(error => {
                    if (field === 'error' || field === 'message') {
                        errorMessages.push(error);
                    } else if (!field.startsWith('images.')) {
                        // Skip image errors as they're handled above
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
    
    function displayImageErrors(errorMessages) {
        const photosSection = document.querySelector('[data-section="photos"]');
        if (!photosSection) {
            // Fallback to general error if photos section not found
            displayGeneralError(errorMessages.join('<br>'));
            return;
        }
        
        // Expand photos section if not already expanded
        const header = photosSection.querySelector('.section-header');
        const content = photosSection.querySelector('.section-content');
        if (header && content && !content.classList.contains('expanded')) {
            content.classList.add('expanded');
            header.classList.add('active');
        }
        
        // Remove existing error
        const existingError = photosSection.querySelector('.image-upload-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Create error element
        const errorElement = document.createElement('div');
        errorElement.className = 'image-upload-error p-3 mb-4 rounded-md border border-red-500 bg-red-50 dark:bg-red-900/20';
        
        // Format error messages - make them more user-friendly
        const formattedMessages = errorMessages.map(msg => {
            // Replace technical messages with user-friendly ones
            if (msg.includes('failed to upload')) {
                return 'One or more images failed to upload. Please check file size (max 20MB) and format (JPEG, PNG, GIF only), then try again.';
            }
            return msg;
        });
        
        // Remove duplicates
        const uniqueMessages = [...new Set(formattedMessages)];
        
        errorElement.innerHTML = `
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-800 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-red-800 mb-1">Image Upload Error</p>
                    <ul class="text-sm text-red-800 list-disc list-inside space-y-1">
                        ${uniqueMessages.map(msg => `<li>${escapeHtml(msg)}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
        
        // Insert error after upload area or at the top of section content
        const uploadArea = document.getElementById('image-upload-area');
        if (uploadArea && uploadArea.parentNode) {
            uploadArea.parentNode.insertBefore(errorElement, uploadArea.nextSibling);
        } else {
            const sectionContent = photosSection.querySelector('.section-content');
            if (sectionContent) {
                sectionContent.insertBefore(errorElement, sectionContent.firstChild);
            }
        }
        
        // Scroll to photos section
        photosSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function displayFieldError(fieldName, message) {
        // Image errors are now handled by displayImageErrors() in displayErrors()
        // This function only handles non-image field errors
        if (fieldName.startsWith('images.')) {
            // Should not reach here as image errors are handled separately
            return;
        }
        
        const field = document.querySelector(`[name="${fieldName}"]`) || 
                     document.getElementById(fieldName) ||
                     document.getElementById(fieldName.replace('.', '_'));
        
        if (!field) {
            console.warn(`Field not found for error: ${fieldName}`);
            // Still show as general error if field not found
            displayGeneralError(`${fieldName}: ${message}`);
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

    // Update fuel efficiency label based on fuel type
    // DEFAULT BEHAVIOR: Always use step="any" to allow decimal values like 54.5
    // Only change to step="1" if fuel type is EXPLICITLY electric (3 or 7) AND user hasn't entered a decimal value
    function updateFuelEfficiencyLabel(fuelTypeId) {
        const label = document.getElementById('fuel_efficiency_label');
        const help = document.getElementById('fuel_efficiency_help');
        const input = document.getElementById('fuel_efficiency');
        
        if (!label || !help || !input) return;
        
        // Validate fuelTypeId - if invalid, default to decimal step
        const parsedFuelTypeId = parseInt(fuelTypeId);
        if (!fuelTypeId || isNaN(parsedFuelTypeId) || parsedFuelTypeId <= 0) {
            // Default to decimal step if fuel type is invalid or not provided
            label.textContent = 'KM/L';
            help.textContent = 'Fuel efficiency in kilometers per liter';
            input.placeholder = '0.00';
            input.setAttribute('step', 'any');
            input.setAttribute('inputmode', 'decimal');
            return;
        }
        
        // Electric fuel types: 3 (Electric), 7 (El)
        const electricFuelTypes = [3, 7];
        // Hybrid fuel types: 4 (Hybrid), 5 (Plug-in Hybrid)
        const hybridFuelTypes = [4, 5];
        
        // Check if current value has decimals - if so, always preserve step="any"
        const currentValue = input.value;
        const hasDecimalValue = currentValue && (currentValue.includes('.') || (parseFloat(currentValue) % 1 !== 0));
        
        if (electricFuelTypes.includes(parsedFuelTypeId)) {
            // For electric vehicles, only use step="1" if field is empty AND no decimal value exists
            // If user has entered or wants to enter decimals, always use step="any"
            if (hasDecimalValue) {
                // User has entered decimal - keep step="any"
                label.textContent = 'Electric Range (km)';
                help.textContent = 'Electric range in kilometers';
                input.placeholder = '0.00';
                input.setAttribute('step', 'any');
                input.setAttribute('inputmode', 'decimal');
            } else if (!currentValue) {
                // Field is empty - can use step="1" for electric
                label.textContent = 'Electric Range (km)';
                help.textContent = 'Electric range in kilometers';
                input.placeholder = '0';
                input.setAttribute('step', '1');
                input.setAttribute('inputmode', 'numeric');
            } else {
                // Field has whole number - keep step="any" to allow user to change to decimal
                label.textContent = 'Electric Range (km)';
                help.textContent = 'Electric range in kilometers';
                input.placeholder = '0.00';
                input.setAttribute('step', 'any');
                input.setAttribute('inputmode', 'decimal');
            }
        } else if (hybridFuelTypes.includes(parsedFuelTypeId)) {
            label.textContent = 'Electric Range / KM/L';
            help.textContent = 'Electric range in km (for EV mode) or fuel efficiency in km/l';
            input.placeholder = '0.00';
            input.setAttribute('step', 'any');
            input.setAttribute('inputmode', 'decimal');
        } else {
            // Petrol, Diesel, Benzin, or any other fuel type - always allow decimals
            label.textContent = 'KM/L';
            help.textContent = 'Fuel efficiency in kilometers per liter';
            input.placeholder = '0.00';
            input.setAttribute('step', 'any');
            input.setAttribute('inputmode', 'decimal');
        }
    }

    // Update vehicle info display (brand, model, year, fuel type)
    function updateVehicleInfoDisplay(vehicleData) {
        const infoDisplay = document.getElementById('vehicle-info-display');
        if (!infoDisplay) return;
        
        // Extract brand name
        let brandName = '';
        if (vehicleData.brand) {
            if (typeof vehicleData.brand === 'object' && vehicleData.brand.name) {
                brandName = vehicleData.brand.name;
            } else if (typeof vehicleData.brand === 'string') {
                brandName = vehicleData.brand;
            }
        } else if (vehicleData.brand_name) {
            brandName = vehicleData.brand_name;
        }
        
        // Extract model name
        let modelName = '';
        if (vehicleData.model) {
            if (typeof vehicleData.model === 'object' && vehicleData.model.name) {
                modelName = vehicleData.model.name;
            } else if (typeof vehicleData.model === 'string') {
                modelName = vehicleData.model;
            }
        } else if (vehicleData.model_name) {
            modelName = vehicleData.model_name;
        }
        
        // Extract model year
        let modelYear = '';
        if (vehicleData.model_year) {
            if (typeof vehicleData.model_year === 'object' && vehicleData.model_year.name) {
                modelYear = vehicleData.model_year.name;
            } else if (typeof vehicleData.model_year === 'object' && vehicleData.model_year.year) {
                modelYear = vehicleData.model_year.year;
            } else if (typeof vehicleData.model_year === 'string' || typeof vehicleData.model_year === 'number') {
                modelYear = String(vehicleData.model_year);
            }
        } else if (vehicleData.model_year_name) {
            modelYear = vehicleData.model_year_name;
        } else if (vehicleData.year) {
            modelYear = String(vehicleData.year);
        }
        
        // Extract fuel type
        let fuelType = '';
        if (vehicleData.fuel_type) {
            if (typeof vehicleData.fuel_type === 'object' && vehicleData.fuel_type.name) {
                fuelType = vehicleData.fuel_type.name;
            } else if (typeof vehicleData.fuel_type === 'string') {
                fuelType = vehicleData.fuel_type;
            }
        } else if (vehicleData.fuel_type_name) {
            fuelType = vehicleData.fuel_type_name;
        }
        
        // Update display elements
        const brandDisplay = document.getElementById('vehicle-brand-display');
        const modelDisplay = document.getElementById('vehicle-model-display');
        const yearDisplay = document.getElementById('vehicle-year-display');
        const fuelDisplay = document.getElementById('vehicle-fuel-display');
        
        if (brandDisplay) brandDisplay.textContent = brandName || '';
        if (modelDisplay) modelDisplay.textContent = modelName || '';
        if (yearDisplay) yearDisplay.textContent = modelYear || '';
        if (fuelDisplay) fuelDisplay.textContent = fuelType || '';
        
        // Show the display if at least one value is available
        if (brandName || modelName || modelYear || fuelType) {
            infoDisplay.classList.remove('hidden');
        } else {
            infoDisplay.classList.add('hidden');
        }
    }

    // Prefill form with API data
    function prefillForm(apiData) {
        console.log('PrefillForm called with API data:', apiData);
        console.log('API response structure:', JSON.stringify(apiData, null, 2));
        
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
        
        // Helper function to add option to select if it doesn't exist, and remove duplicates
        function addOptionIfNotExists(selectId, value, text) {
            const select = document.getElementById(selectId);
            if (!select || select.tagName !== 'SELECT') {
                return false;
            }
            
            const valueStr = String(value);
            const textStr = String(text).trim();
            
            // Check if option with this value already exists
            const existingOption = select.querySelector(`option[value="${valueStr}"]`);
            if (existingOption) {
                // Option exists, just update text if different
                if (existingOption.textContent.trim() !== textStr) {
                    existingOption.textContent = textStr;
                }
                return true;
            }
            
            // Remove any duplicate options with same text (but different value)
            const options = Array.from(select.options);
            const duplicateIndices = [];
            options.forEach((option, index) => {
                if (option.textContent.trim().toLowerCase() === textStr.toLowerCase() && option.value !== valueStr) {
                    duplicateIndices.push(index);
                }
            });
            
            // Remove duplicates (in reverse order to maintain indices)
            duplicateIndices.reverse().forEach(index => {
                select.remove(index);
            });
            
            // Add new option
            const option = document.createElement('option');
            option.value = valueStr;
            option.textContent = textStr;
            select.appendChild(option);
            return true;
        }
        
        // Helper function to safely set select value by ID or text match
        function setSelectByIdOrText(selectId, value) {
            const select = document.getElementById(selectId);
            if (!select) {
                console.warn(`Select not found: ${selectId}`);
                return false;
            }
            if (value === null || value === undefined || value === '') return false;
            
            // Ensure it's a select element
            if (select.tagName !== 'SELECT') {
                console.warn(`Element ${selectId} is not a SELECT element`);
                return false;
            }
            
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
            // Convert HTMLOptionsCollection to array for safe iteration
            const options = Array.from(select.options);
            for (let option of options) {
                if (option.value && option.text && option.text.trim().toLowerCase() === text) {
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
            const registrationInput = document.getElementById('registration');
            if (registrationInput) {
                registrationInput.value = registration;
            }
            setFieldValue('registration-lookup', registration);
        }
        
        // Numeric fields
        const price = apiData.price || apiData.price_dkk || apiData.list_price || apiData.priceDkk;
        if (price) setFieldValue('price', price);
        
        // KM Driven (removed mileage)
        const kmDriven = apiData.km_driven || apiData.mileage || apiData.km || apiData.odometer || apiData.odometer_reading || apiData.kmDriven;
        if (kmDriven) {
            setFieldValue('km_driven', kmDriven);
        }
        
        // Handle fuel_type - update label and set fuel_type_id if available
        let fuelTypeId = null;
        if (apiData.fuel_type && typeof apiData.fuel_type === 'object' && apiData.fuel_type.id) {
            fuelTypeId = apiData.fuel_type.id;
            // Store fuel_type_id for form submission (will be handled in form submission)
        } else if (apiData.fuel_type_id) {
            fuelTypeId = apiData.fuel_type_id;
        }
        
        // Update fuel efficiency label based on fuel type
        if (fuelTypeId) {
            updateFuelEfficiencyLabel(fuelTypeId);
        }
        
        // Fuel efficiency
        const fuelEfficiency = apiData.fuel_efficiency || apiData.fuelEfficiency || apiData.range_km || apiData.rangeKm;
        if (fuelEfficiency) {
            setFieldValue('fuel_efficiency', fuelEfficiency);
        }
        
        // Technical total weight
        const technicalTotalWeight = apiData.technical_total_weight || apiData.technicalTotalWeight;
        if (technicalTotalWeight) {
            setFieldValue('technical_total_weight', technicalTotalWeight);
        }
        
        // First registration date (month/year picker)
        const regDate = apiData.firstRegistrationDate || apiData.first_registration_date || apiData.registration_date || apiData.first_reg_date;
        if (regDate) {
            try {
                const date = new Date(regDate);
                if (!isNaN(date.getTime())) {
                    const month = date.getMonth() + 1; // getMonth() returns 0-11
                    const year = date.getFullYear();
                    setFieldValue('first_registration_month', month);
                    setFieldValue('first_registration_year', year);
                }
            } catch (e) {
                console.warn('Invalid date format:', regDate);
            }
        }
        
        // Last inspection date (month/year picker)
        const lastInspectionDate = apiData.last_inspection_date || apiData.lastInspectionDate;
        if (lastInspectionDate) {
            try {
                const date = new Date(lastInspectionDate);
                if (!isNaN(date.getTime())) {
                    const month = date.getMonth() + 1;
                    const year = date.getFullYear();
                    setFieldValue('last_inspection_month', month);
                    setFieldValue('last_inspection_year', year);
                }
            } catch (e) {
                console.warn('Invalid last inspection date format:', lastInspectionDate);
            }
        }

        // Map color - use color object from backend (already processed and created if needed)
        if (apiData.color && typeof apiData.color === 'object') {
            const colorId = apiData.color.id;
            const colorName = apiData.color.name;
            if (colorId && colorName) {
                // Add option if it doesn't exist (to handle newly created colors)
                addOptionIfNotExists('color_id', colorId, colorName);
                setSelectByIdOrText('color_id', colorId);
            }
        }
        
        // Map variant - use variant object from backend (already processed and created if needed)
        if (apiData.variant && typeof apiData.variant === 'object') {
            const variantId = apiData.variant.id;
            const variantName = apiData.variant.name;
            if (variantId && variantName) {
                // Add option if it doesn't exist (to handle newly created variants)
                addOptionIfNotExists('variant_id', variantId, variantName);
                setSelectByIdOrText('variant_id', variantId);
            }
        }
        
        // Map euronorm - use euronorm object from backend (already processed and created if needed)
        if (apiData.euronorm && typeof apiData.euronorm === 'object') {
            const euronomId = apiData.euronorm.id;
            const euronomName = apiData.euronorm.name;
            if (euronomId && euronomName) {
                // Add option if it doesn't exist (to handle newly created euronorms)
                addOptionIfNotExists('euronom_id', euronomId, euronomName);
                setSelectByIdOrText('euronom_id', euronomId);
            }
        }
        
        // Map fuel_type - update label based on fuel type
        if (apiData.fuel_type && typeof apiData.fuel_type === 'object' && apiData.fuel_type.id) {
            updateFuelEfficiencyLabel(apiData.fuel_type.id);
        } else if (apiData.fuel_type_id) {
            updateFuelEfficiencyLabel(apiData.fuel_type_id);
        }
        
        // Servicebog
        const servicebog = apiData.servicebog || apiData.service_book;
        if (servicebog) {
            const servicebogRadio = document.querySelector(`input[name="servicebog"][value="${servicebog}"]`);
            if (servicebogRadio) {
                servicebogRadio.checked = true;
            }
        }

        // Description - allow API override
        if (apiData.description) {
            setFieldValue('description', apiData.description);
        } else {
            // Generate description after a short delay to allow other fields to be set
            setTimeout(() => generateDescription(), 500);
        }
        
        // Handle equipment array - use equipment objects from backend (already processed and created if needed)
        if (apiData.equipment && Array.isArray(apiData.equipment)) {
            apiData.equipment.forEach(function(equipment) {
                if (equipment && typeof equipment === 'object' && equipment.id && equipment.name) {
                    const equipmentId = equipment.id;
                    const equipmentName = equipment.name;
                    
                    // Find existing checkbox
                    let checkbox = document.querySelector(`input[name="equipment_ids[]"][value="${equipmentId}"]`);
                    
                    if (!checkbox) {
                        // Equipment doesn't exist in dropdown, create a checkbox dynamically
                        const equipmentSection = document.querySelector('[data-section="equipment"] .section-content');
                        if (equipmentSection) {
                            // Find "Other" section or create it
                            let otherSection = null;
                            const allSections = equipmentSection.querySelectorAll('.equipment-type-group');
                            for (let section of allSections) {
                                const h4 = section.querySelector('h4');
                                if (h4 && h4.textContent.trim().toLowerCase() === 'other') {
                                    otherSection = section;
                                    break;
                                }
                            }
                            
                            if (!otherSection) {
                                // Create "Other" section
                                const otherDiv = document.createElement('div');
                                otherDiv.className = 'equipment-type-group';
                                otherDiv.innerHTML = `
                                    <h4 class="text-sm font-semibold uppercase tracking-wide mb-3 text-foreground">Other</h4>
                                    <div class="flex flex-wrap gap-2"></div>
                                `;
                                equipmentSection.appendChild(otherDiv);
                                otherSection = otherDiv;
                            }
                            
                            // Add checkbox to the "Other" section
                            const container = otherSection.querySelector('.flex.flex-wrap.gap-2');
                            if (container) {
                                const label = document.createElement('label');
                                label.className = 'inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium cursor-pointer transition-all hover:bg-accent focus-within:bg-accent border border-input';
                                
                                const input = document.createElement('input');
                                input.type = 'checkbox';
                                input.name = 'equipment_ids[]';
                                input.value = equipmentId;
                                input.className = 'h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-ring focus:ring-offset-2';
                                input.onchange = function() {
                                    if (window.handleEquipmentChange) {
                                        window.handleEquipmentChange(this, equipmentId, equipmentName);
                                    }
                                };
                                
                                const span = document.createElement('span');
                                span.textContent = equipmentName;
                                
                                label.appendChild(input);
                                label.appendChild(span);
                                container.appendChild(label);
                                checkbox = input;
                            }
                        }
                    }
                    
                    if (checkbox) {
                        checkbox.checked = true;
                        // Trigger change handler to update UI
                        if (window.handleEquipmentChange) {
                            window.handleEquipmentChange(checkbox, equipmentId, equipmentName);
                        }
                    }
                }
            });
        }
        
        // Generate description after all fields are set (with longer delay to ensure models are loaded)
        setTimeout(() => {
            if (!apiData.description) {
                generateDescription();
            }
        }, 1200);
        
        console.log('Form prefilling completed. All fields processed.');
    }
    
    // Description auto-generation
    function initDescriptionAutoGeneration() {
        const fields = [
            'equipment_ids[]', 'servicebog', 'km_driven', 
            'first_registration_month', 'first_registration_year',
            'last_inspection_month', 'last_inspection_year',
            'fuel_efficiency', 'euronom_id', 'technical_total_weight'
        ];
        
        fields.forEach(fieldName => {
            const inputs = document.querySelectorAll(`[name="${fieldName}"]`);
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    generateDescription();
                });
            });
        });
        
        // Also listen for equipment checkboxes
        document.addEventListener('change', (e) => {
            if (e.target.name === 'equipment_ids[]') {
                generateDescription();
            }
        });
    }
    
    function generateDescription() {
        const descriptionTextarea = document.getElementById('description');
        if (!descriptionTextarea) return;
        
        // Don't regenerate if user has manually edited
        if (descriptionTextarea.dataset.userEdited === 'true') {
            return;
        }
        
        const parts = [];
        
        // Equipment
        const equipmentCheckboxes = document.querySelectorAll('input[name="equipment_ids[]"]:checked');
        if (equipmentCheckboxes.length > 0) {
            const equipmentNames = Array.from(equipmentCheckboxes).map(cb => {
                return cb.nextElementSibling?.textContent || '';
            }).filter(name => name);
            if (equipmentNames.length > 0) {
                parts.push('Equipment: ' + equipmentNames.join(', '));
            }
        }
        
        // Servicebog
        const servicebogRadio = document.querySelector('input[name="servicebog"]:checked');
        if (servicebogRadio && servicebogRadio.value !== 'Default') {
            parts.push('Service book: ' + servicebogRadio.value);
        }
        
        // Kilometer Driven
        const kmDriven = document.getElementById('km_driven')?.value;
        if (kmDriven) {
            parts.push('Kilometers driven: ' + parseInt(kmDriven).toLocaleString() + ' km');
        }
        
        // First Registration
        const firstRegMonth = document.getElementById('first_registration_month')?.value;
        const firstRegYear = document.getElementById('first_registration_year')?.value;
        if (firstRegMonth && firstRegYear) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const monthName = monthNames[parseInt(firstRegMonth) - 1];
            parts.push('First registration: ' + monthName + ' ' + firstRegYear);
        }
        
        // Last Inspection
        const lastInspMonth = document.getElementById('last_inspection_month')?.value;
        const lastInspYear = document.getElementById('last_inspection_year')?.value;
        if (lastInspMonth && lastInspYear) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const monthName = monthNames[parseInt(lastInspMonth) - 1];
            parts.push('Last inspection: ' + monthName + ' ' + lastInspYear);
        }
        
        // Fuel Efficiency / Electric Range
        const fuelEfficiency = document.getElementById('fuel_efficiency')?.value;
        if (fuelEfficiency) {
            // Get fuel_type_id to determine the correct unit
            const fuelTypeId = window.apiResponseData?.fuel_type?.id || window.apiResponseData?.fuel_type_id;
            const electricFuelTypes = [3, 7]; // Electric, El
            const hybridFuelTypes = [4, 5]; // Hybrid, Plug-in Hybrid
            
            if (fuelTypeId && electricFuelTypes.includes(parseInt(fuelTypeId))) {
                parts.push('Electric range: ' + parseInt(fuelEfficiency).toLocaleString() + ' km');
            } else if (fuelTypeId && hybridFuelTypes.includes(parseInt(fuelTypeId))) {
                parts.push('Range/Efficiency: ' + parseFloat(fuelEfficiency).toFixed(2) + ' km');
            } else {
                parts.push('Fuel efficiency: ' + parseFloat(fuelEfficiency).toFixed(2) + ' km/l');
            }
        }
        
        // Euronom
        const euronomSelect = document.getElementById('euronom_id');
        if (euronomSelect && euronomSelect.value) {
            const euronomOption = euronomSelect.options[euronomSelect.selectedIndex];
            if (euronomOption && euronomOption.text) {
                parts.push('Euro norm: ' + euronomOption.text);
            }
        }
        
        // Total Technical Weight
        const technicalWeight = document.getElementById('technical_total_weight')?.value;
        if (technicalWeight) {
            parts.push('Total technical weight: ' + parseInt(technicalWeight).toLocaleString() + ' kg');
        }
        
        if (parts.length > 0) {
            descriptionTextarea.value = parts.join('. ') + '.';
        }
        
        // Mark as generated
        descriptionTextarea.dataset.autoGenerated = 'true';
    }
    
    // Allow user to edit description
    document.addEventListener('DOMContentLoaded', () => {
        const descriptionTextarea = document.getElementById('description');
        if (descriptionTextarea) {
            descriptionTextarea.addEventListener('input', () => {
                descriptionTextarea.dataset.userEdited = 'true';
            });
        }
    });
    
    // Tax info toggle
    // Initialize fuel efficiency label updater
    function initFuelEfficiencyLabelUpdater() {
        // Check if fuel_type_id select exists and listen for changes
        const fuelTypeSelect = document.getElementById('fuel_type_id');
        const input = document.getElementById('fuel_efficiency');
        
        // Always start with step="any" as default (allows decimal values like 54.5)
        // This will be overridden only if fuel type is explicitly electric (3 or 7)
        if (input) {
            // Set default first, will be updated if fuel type is electric
            input.setAttribute('step', 'any');
            input.setAttribute('inputmode', 'decimal');
            input.setAttribute('placeholder', '0.00');
        }
        
        if (fuelTypeSelect) {
            // Set initial step value based on current selection
            if (fuelTypeSelect.value) {
                updateFuelEfficiencyLabel(fuelTypeSelect.value);
            } else {
                // Default to decimal step if no fuel type selected
                if (input) {
                    input.setAttribute('step', 'any');
                    input.setAttribute('inputmode', 'decimal');
                }
            }
            
            fuelTypeSelect.addEventListener('change', function() {
                if (this.value) {
                    updateFuelEfficiencyLabel(this.value);
                } else {
                    // Reset to default decimal step if fuel type is cleared
                    if (input) {
                        input.setAttribute('step', 'any');
                        input.setAttribute('inputmode', 'decimal');
                    }
                }
            });
        } else {
            // If no fuel type select exists, ensure step is set to decimal
            if (input) {
                input.setAttribute('step', 'any');
                input.setAttribute('inputmode', 'decimal');
            }
        }
        
        // Also check if fuel_type is already set from API response
        // Default behavior: Always use step="any" to allow decimal values like 54.5
        // Only change to step="1" if fuel type is explicitly electric (3 or 7) AND field is empty
        if (window.apiResponseData) {
            const fuelTypeId = window.apiResponseData.fuel_type?.id || window.apiResponseData.fuel_type_id;
            const parsedFuelTypeId = parseInt(fuelTypeId);
            
            // Default to step="any" - only change if explicitly electric and field is empty
            if (input) {
                const electricFuelTypes = [3, 7];
                const isElectric = fuelTypeId && !isNaN(parsedFuelTypeId) && electricFuelTypes.includes(parsedFuelTypeId);
                
                if (isElectric && !input.value) {
                    // Only set to step="1" if explicitly electric AND field is empty
                    updateFuelEfficiencyLabel(fuelTypeId);
                } else {
                    // Always default to step="any" for all other cases
                    input.setAttribute('step', 'any');
                    input.setAttribute('inputmode', 'decimal');
                    if (!isElectric) {
                        // Update label/help for non-electric, but keep step="any"
                        const label = document.getElementById('fuel_efficiency_label');
                        const help = document.getElementById('fuel_efficiency_help');
                        if (label) label.textContent = 'KM/L';
                        if (help) help.textContent = 'Fuel efficiency in kilometers per liter';
                        input.placeholder = '0.00';
                    }
                }
            }
        }
        
        // Final safeguard: Ensure step="any" is set after a short delay to override any late API calls
        setTimeout(function() {
            const input = document.getElementById('fuel_efficiency');
            if (input) {
                // Only override if it's currently set to step="1" and field is empty or has decimal value
                const currentStep = input.getAttribute('step');
                const currentValue = input.value;
                if (currentStep === '1' && (!currentValue || currentValue.includes('.'))) {
                    input.setAttribute('step', 'any');
                    input.setAttribute('inputmode', 'decimal');
                    input.placeholder = '0.00';
                }
            }
        }, 100);
    }

    function initTaxInfoToggle() {
        const taxToggle = document.querySelector('[onclick="toggleSection(\'tax-info\')"]');
        if (taxToggle) {
            taxToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const content = document.getElementById('tax-info-content');
                const icon = this.querySelector('.equipment-type-icon');
                if (content) {
                    content.classList.toggle('hidden');
                    if (icon) {
                        icon.classList.toggle('rotate-180');
                    }
                }
            });
        }
    }
    
    // Equipment collapsible functionality - removed, equipment types are now simple headings
    function initEquipmentCollapsible() {
        // No longer needed - equipment types are displayed as simple headings
    }
    
    // Equipment selection handlers
    window.handleEquipmentChange = function(checkbox, equipmentId, equipmentName) {
        const isChecked = checkbox.checked;
        const label = checkbox.closest('label');
        
        // Update label styling based on checked state
        if (isChecked) {
            label.classList.add('bg-accent', 'border-primary');
        } else {
            label.classList.remove('bg-accent', 'border-primary');
        }
    };
    
    window.clearAllEquipment = function() {
        const checkboxes = document.querySelectorAll('input[name="equipment_ids[]"]:checked');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const equipmentId = parseInt(checkbox.value);
            const equipmentName = checkbox.nextElementSibling?.textContent || '';
            handleEquipmentChange(checkbox, equipmentId, equipmentName);
        });
    };
    
    // Image Upload Handlers - Rewritten with cleaner architecture
    // Single source of truth: fileMap stores File objects with unique IDs
    // fileOrder maintains the display/submission order for drag-and-drop reordering
    const imageUploadState = {
        fileMap: new Map(), // Map<fileId, File> - single source of truth
        fileOrder: [], // Array<fileId> - maintains order for drag-and-drop
        fileInput: null,
        isUpdating: false, // Flag to prevent recursive updates
        maxSize: 20 * 1024 * 1024, // 20MB
        allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
        draggedElement: null // Currently dragged element for reordering
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
            return { valid: false, error: `File "${file.name}" is too large. Maximum size is 20MB.` };
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
        
        // Add valid files to map and order array
        validFiles.forEach(file => {
            const fileId = getFileId(file);
            imageUploadState.fileMap.set(fileId, file);
            // Add to end of order array
            if (!imageUploadState.fileOrder.includes(fileId)) {
                imageUploadState.fileOrder.push(fileId);
            }
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
            // Remove from order array
            const index = imageUploadState.fileOrder.indexOf(fileId);
            if (index > -1) {
                imageUploadState.fileOrder.splice(index, 1);
            }
            syncFileInput();
            updateImagePreviews();
        }
    }
    
    // Sync the file input with the file map, maintaining order
    function syncFileInput() {
        if (!imageUploadState.fileInput) return;
        
        imageUploadState.isUpdating = true;
        try {
            const dataTransfer = new DataTransfer();
            
            // Add files in the order specified by fileOrder array
            imageUploadState.fileOrder.forEach(fileId => {
                const file = imageUploadState.fileMap.get(fileId);
                if (file) {
                    try {
                        dataTransfer.items.add(file);
                    } catch (e) {
                        console.error('Error adding file to DataTransfer:', e, file.name);
                    }
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
        
        // Initialize file map and order from existing files in input
        if (fileInput.files && fileInput.files.length > 0) {
            Array.from(fileInput.files).forEach(file => {
                const fileId = getFileId(file);
                imageUploadState.fileMap.set(fileId, file);
                if (!imageUploadState.fileOrder.includes(fileId)) {
                    imageUploadState.fileOrder.push(fileId);
                }
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
        
        // Create preview for each file in the order specified by fileOrder array
        imageUploadState.fileOrder.forEach(fileId => {
            const file = imageUploadState.fileMap.get(fileId);
            if (file) {
                const previewItem = createImagePreview(file, fileId);
                grid.appendChild(previewItem);
            }
        });
    }
    
    // Create preview element for a file with drag-and-drop support
    function createImagePreview(file, fileId) {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.dataset.fileId = fileId;
        item.draggable = true;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            img.draggable = false; // Prevent image from being draggable separately
            item.insertBefore(img, item.firstChild);
        };
        reader.readAsDataURL(file);
        
        // File info
        const fileSize = formatFileSize(file.size);
        const info = document.createElement('div');
        info.className = 'image-preview-info';
        info.textContent = `${file.name} (${fileSize})`;
        item.appendChild(info);
        
        // Drag handle indicator (positioned to not interfere with remove button)
        const dragHandle = document.createElement('div');
        dragHandle.className = 'image-drag-handle';
        dragHandle.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="9" y1="5" x2="9" y2="19"></line>
                <line x1="15" y1="5" x2="15" y2="19"></line>
            </svg>
        `;
        // Prevent drag handle from interfering with remove button clicks
        dragHandle.addEventListener('mousedown', function(e) {
            e.stopPropagation();
        });
        item.appendChild(dragHandle);
        
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
        // Prevent remove button from triggering drag
        removeBtn.addEventListener('mousedown', function(e) {
            e.stopPropagation();
        });
        removeBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        `;
        overlay.appendChild(removeBtn);
        item.appendChild(overlay);
        
        // Drag and drop event handlers
        item.addEventListener('dragstart', function(e) {
            imageUploadState.draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
        });
        
        item.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            // Remove drag-over class from all items
            document.querySelectorAll('.image-preview-item').forEach(el => {
                el.classList.remove('drag-over');
            });
        });
        
        item.addEventListener('dragover', function(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            
            // Add visual feedback
            if (this !== imageUploadState.draggedElement) {
                this.classList.add('drag-over');
            }
            return false;
        });
        
        item.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        item.addEventListener('drop', function(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            this.classList.remove('drag-over');
            
            if (imageUploadState.draggedElement !== this) {
                const grid = document.getElementById('image-preview-grid');
                const draggedFileId = imageUploadState.draggedElement.dataset.fileId;
                const targetFileId = this.dataset.fileId;
                
                // Get current positions in order array
                const draggedIndex = imageUploadState.fileOrder.indexOf(draggedFileId);
                const targetIndex = imageUploadState.fileOrder.indexOf(targetFileId);
                
                if (draggedIndex !== -1 && targetIndex !== -1) {
                    // Remove from old position
                    imageUploadState.fileOrder.splice(draggedIndex, 1);
                    // Insert at new position
                    imageUploadState.fileOrder.splice(targetIndex, 0, draggedFileId);
                    
                    // Update previews and sync file input
                    updateImagePreviews();
                    syncFileInput();
                }
            }
            
            return false;
        });
        
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
        imageUploadState.fileOrder = [];
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
    // Final safeguard: Ensure fuel_efficiency always allows decimals (step="any")
    // This runs after all initialization and watches for any changes to step attribute
    function enforceDecimalStep() {
        const input = document.getElementById('fuel_efficiency');
        if (!input) return;
        
        const currentStep = input.getAttribute('step');
        // If step is "1", check if we should keep it or change to "any"
        if (currentStep === '1') {
            const currentValue = input.value;
            const fuelTypeSelect = document.getElementById('fuel_type_id');
            const hasFuelTypeSelect = fuelTypeSelect && fuelTypeSelect.value;
            
            // Only keep step="1" if:
            // 1. Field is empty
            // 2. AND there's a fuel type select with electric value (3 or 7)
            // Otherwise, default to step="any" to allow decimals like 54.5
            if (currentValue || !hasFuelTypeSelect) {
                input.setAttribute('step', 'any');
                input.setAttribute('inputmode', 'decimal');
                input.placeholder = '0.00';
            } else {
                // Check if fuel type is explicitly electric
                const fuelTypeId = parseInt(fuelTypeSelect.value);
                const electricFuelTypes = [3, 7];
                if (!electricFuelTypes.includes(fuelTypeId)) {
                    // Not electric - use step="any"
                    input.setAttribute('step', 'any');
                    input.setAttribute('inputmode', 'decimal');
                    input.placeholder = '0.00';
                }
            }
        } else if (!currentStep || currentStep === '0.01') {
            // If no step or old step="0.01", set to "any"
            input.setAttribute('step', 'any');
            input.setAttribute('inputmode', 'decimal');
        }
    }
    
    // Run immediately and after delays to catch any late changes
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(enforceDecimalStep, 100);
            setTimeout(enforceDecimalStep, 500);
            setTimeout(enforceDecimalStep, 1000);
        });
    } else {
        setTimeout(enforceDecimalStep, 100);
        setTimeout(enforceDecimalStep, 500);
        setTimeout(enforceDecimalStep, 1000);
    }
    
    // Watch for changes to the step attribute (set up after DOM is ready)
    function setupStepWatcher() {
        const fuelEfficiencyInput = document.getElementById('fuel_efficiency');
        if (fuelEfficiencyInput) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'step') {
                        const newStep = fuelEfficiencyInput.getAttribute('step');
                        if (newStep === '1') {
                            // If changed to "1", check if we should keep it or change to "any"
                            setTimeout(enforceDecimalStep, 50);
                        }
                    }
                });
            });
            observer.observe(fuelEfficiencyInput, { attributes: true, attributeFilter: ['step'] });
        }
    }
    
    // Set up watcher after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(setupStepWatcher, 200);
        });
    } else {
        setTimeout(setupStepWatcher, 200);
    }
})();
