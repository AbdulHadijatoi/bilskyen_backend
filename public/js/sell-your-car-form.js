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
            
            // Append all files from fileMap
            imageUploadState.fileMap.forEach((file) => {
                formData.append('images[]', file, file.name);
            });
            
            console.log(`Added ${imageUploadState.fileMap.size} image(s) to FormData:`, 
                Array.from(imageUploadState.fileMap.keys()).map(id => {
                    const file = imageUploadState.fileMap.get(id);
                    return file ? file.name : 'unknown';
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

    // Update fuel efficiency label based on fuel type
    function updateFuelEfficiencyLabel(fuelTypeId) {
        const label = document.getElementById('fuel_efficiency_label');
        const help = document.getElementById('fuel_efficiency_help');
        const input = document.getElementById('fuel_efficiency');
        
        if (!label || !help || !input) return;
        
        // Electric fuel types: 3 (Electric), 7 (El)
        const electricFuelTypes = [3, 7];
        // Hybrid fuel types: 4 (Hybrid), 5 (Plug-in Hybrid)
        const hybridFuelTypes = [4, 5];
        
        if (electricFuelTypes.includes(parseInt(fuelTypeId))) {
            label.textContent = 'Electric Range (km)';
            help.textContent = 'Electric range in kilometers';
            input.placeholder = '0';
            input.step = '1';
        } else if (hybridFuelTypes.includes(parseInt(fuelTypeId))) {
            label.textContent = 'Electric Range / KM/L';
            help.textContent = 'Electric range in km (for EV mode) or fuel efficiency in km/l';
            input.placeholder = '0.00';
            input.step = '0.01';
        } else {
            // Petrol, Diesel, Benzin
            label.textContent = 'KM/L';
            help.textContent = 'Fuel efficiency in kilometers per liter';
            input.placeholder = '0.00';
            input.step = '0.01';
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
        if (fuelTypeSelect) {
            fuelTypeSelect.addEventListener('change', function() {
                if (this.value) {
                    updateFuelEfficiencyLabel(this.value);
                }
            });
        }
        
        // Also check if fuel_type is already set from API response
        if (window.apiResponseData) {
            const fuelTypeId = window.apiResponseData.fuel_type?.id || window.apiResponseData.fuel_type_id;
            if (fuelTypeId) {
                updateFuelEfficiencyLabel(fuelTypeId);
            }
        }
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
