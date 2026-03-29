// Seller Vehicle Edit Form Handler
// Handles form submission, image management (add/remove/reorder), and form validation

(function() {
    'use strict';

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSellerVehicleEditForm);
    } else {
        initSellerVehicleEditForm();
    }

    function initSellerVehicleEditForm() {
        // Initialize expandable sections
        initExpandableSections();
        
        // Initialize form submission
        initFormSubmission();
        
        // Initialize image upload
        initImageUpload();
        
        // Initialize location autocomplete
        initLocationAutocomplete();
        
        // Initialize drag and drop for images after a short delay to ensure DOM is ready
        setTimeout(() => {
            initDragAndDrop();
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
                content.classList.add('collapsed');
                header.classList.remove('active');
            } else {
                content.classList.remove('collapsed');
                content.classList.add('expanded');
                header.classList.add('active');
            }
        };
    }

    // Image management state
    const imageState = {
        existingImages: window.existingImages || [],
        newFiles: new Map(), // Map<fileId, File>
        deletedImageIds: new Set(), // Set of image IDs to delete
        imageOrder: [] // Array of {id, type: 'existing'|'new', fileId?}
    };

    /** @param {number|string} imageId */
    function normalizeVehicleImageId(imageId) {
        const n = parseInt(String(imageId), 10);
        return Number.isInteger(n) && n > 0 ? n : null;
    }

    // Initialize image order from existing images
    if (imageState.existingImages && imageState.existingImages.length > 0) {
        imageState.existingImages.forEach(img => {
            const nid = normalizeVehicleImageId(img.id);
            if (nid === null) {
                return;
            }
            imageState.imageOrder.push({
                id: nid,
                type: 'existing',
                sortOrder: img.sort_order || 0
            });
        });
        // Sort by sort_order
        imageState.imageOrder.sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0));
        
        // Initialize drag and drop for existing images
        setTimeout(() => {
            initDragAndDrop();
        }, 100);
    }

    // Remove existing image
    window.removeExistingImage = function(imageId) {
        const id = normalizeVehicleImageId(imageId);
        if (id === null) {
            return;
        }
        // Mark for deletion
        imageState.deletedImageIds.add(id);
        
        // Remove from order
        imageState.imageOrder = imageState.imageOrder.filter(item => 
            !(item.type === 'existing' && item.id === id)
        );
        
        // Hide the image element
        const imageElement = document.querySelector(`[data-image-id="${id}"]`);
        if (imageElement) {
            imageElement.remove();
        }
        
        updateImageCount();
    };

    // Clear all images
    window.clearAllImages = function() {
        // Mark all existing images for deletion
        imageState.existingImages.forEach(img => {
            const nid = normalizeVehicleImageId(img.id);
            if (nid !== null) {
                imageState.deletedImageIds.add(nid);
            }
        });
        
        // Clear new files
        imageState.newFiles.clear();
        imageState.imageOrder = [];
        
        // Remove all preview elements
        const previewGrid = document.getElementById('image-preview-grid');
        if (previewGrid) {
            previewGrid.innerHTML = '';
        }
        
        // Clear file input
        const fileInput = document.getElementById('images');
        if (fileInput) {
            fileInput.value = '';
        }
        
        updateImageCount();
    };

    // Update image count display
    function updateImageCount() {
        const countElement = document.getElementById('image-count');
        const container = document.getElementById('image-preview-container');
        
        const totalCount = imageState.imageOrder.length;
        
        if (countElement) {
            countElement.textContent = totalCount;
        }
        
        if (container) {
            if (totalCount > 0) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    }

    // Initialize image upload
    function initImageUpload() {
        const dropzone = document.getElementById('upload-dropzone');
        const fileInput = document.getElementById('images');
        
        if (!dropzone || !fileInput) return;
        
        // Click to upload
        dropzone.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Drag and drop
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('drag-over');
        });
        
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelection(files);
            }
        });
        
        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files);
            }
        });
    }

    // Handle file selection
    function handleFileSelection(files) {
        const maxSize = 20 * 1024 * 1024; // 20MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        
        Array.from(files).forEach(file => {
            // Validate file
            if (!allowedTypes.includes(file.type)) {
                alert(`File "${file.name}" is not a valid image format. Please use JPEG, PNG, or GIF.`);
                return;
            }
            
            if (file.size > maxSize) {
                alert(`File "${file.name}" is too large. Maximum size is 20MB.`);
                return;
            }
            
            // Generate unique ID for new file
            const fileId = `new_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            
            // Add to state
            imageState.newFiles.set(fileId, file);
            imageState.imageOrder.push({
                id: fileId,
                type: 'new',
                fileId: fileId
            });
            
        // Create preview
        createImagePreview(file, fileId);
    });
    
    updateImageCount();
    
    // Reinitialize drag and drop after adding new images
    setTimeout(() => {
        initDragAndDrop();
    }, 100);
}

    // Create image preview element
    function createImagePreview(file, fileId) {
        const previewGrid = document.getElementById('image-preview-grid');
        if (!previewGrid) return;
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.setAttribute('data-file-id', fileId);
            previewItem.setAttribute('draggable', 'true');
            
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <div class="image-preview-overlay">
                    <button type="button" class="image-remove-btn" onclick="removeNewImage('${fileId}')" title="Remove">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            previewGrid.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    }
    
    // Initialize drag and drop for image reordering
    function initDragAndDrop() {
        const previewGrid = document.getElementById('image-preview-grid');
        if (!previewGrid) return;
        
        const items = previewGrid.querySelectorAll('.image-preview-item');
        let draggedElement = null;
        
        items.forEach((item) => {
            // Ensure draggable attribute is set
            item.setAttribute('draggable', 'true');
            
            // Remove existing listeners by cloning (clean slate)
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            newItem.setAttribute('draggable', 'true');
            
            newItem.addEventListener('dragstart', (e) => {
                draggedElement = newItem;
                newItem.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', newItem.innerHTML);
            });
            
            newItem.addEventListener('dragend', () => {
                newItem.classList.remove('dragging');
                previewGrid.querySelectorAll('.image-preview-item').forEach(el => {
                    el.classList.remove('drag-over');
                });
                draggedElement = null;
                
                // Update image order after drag ends
                updateImageOrderFromDOM();
            });
            
            newItem.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                
                if (!draggedElement || draggedElement === newItem) return;
                
                const afterElement = getDragAfterElement(previewGrid, e.clientY);
                
                if (afterElement == null) {
                    previewGrid.appendChild(draggedElement);
                } else {
                    previewGrid.insertBefore(draggedElement, afterElement);
                }
                
                newItem.classList.add('drag-over');
            });
            
            newItem.addEventListener('dragleave', () => {
                newItem.classList.remove('drag-over');
            });
            
            newItem.addEventListener('drop', (e) => {
                e.preventDefault();
                newItem.classList.remove('drag-over');
            });
        });
    }
    
    // Get element after which to insert dragged element
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.image-preview-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    // Update image order from DOM order
    function updateImageOrderFromDOM() {
        const previewGrid = document.getElementById('image-preview-grid');
        if (!previewGrid) return;
        
        const newOrder = [];
        const items = previewGrid.querySelectorAll('.image-preview-item');
        
        items.forEach(item => {
            const imageId = item.getAttribute('data-image-id');
            const fileId = item.getAttribute('data-file-id');
            
            if (imageId) {
                const nid = normalizeVehicleImageId(imageId);
                if (nid === null) {
                    return;
                }
                newOrder.push({
                    id: nid,
                    type: 'existing'
                });
            } else if (fileId) {
                // New image
                newOrder.push({
                    id: fileId,
                    type: 'new',
                    fileId: fileId
                });
            }
        });
        
        // Update state
        imageState.imageOrder = newOrder;
    }

    // Remove new image
    window.removeNewImage = function(fileId) {
        // Remove from state
        imageState.newFiles.delete(fileId);
        imageState.imageOrder = imageState.imageOrder.filter(item => 
            !(item.type === 'new' && item.id === fileId)
        );
        
        // Remove preview element
        const previewItem = document.querySelector(`[data-file-id="${fileId}"]`);
        if (previewItem) {
            previewItem.remove();
        }
        
        updateImageCount();
    };

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
        const formAction = form.getAttribute('data-action');
        
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
                const section = firstInvalidField.closest('.expandable-section');
                if (section) {
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
        
        // Disable submit button
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';
        }
        
        // Create FormData - manually build to avoid including file input files
        const formData = new FormData();
        
        // Add all form fields except file inputs
        const formElements = form.elements;
        for (let element of formElements) {
            if (element.name && element.type !== 'file' && element.type !== 'submit' && element.type !== 'button') {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    if (element.checked) {
                        if (element.name.endsWith('[]')) {
                            formData.append(element.name, element.value);
                        } else {
                            formData.append(element.name, element.value);
                        }
                    }
                } else if (element.type === 'select-multiple') {
                    Array.from(element.selectedOptions).forEach(option => {
                        formData.append(element.name + '[]', option.value);
                    });
                } else if (element.tagName === 'SELECT') {
                    // Always send selects (including empty) so nullable FKs can be cleared on the server
                    formData.append(element.name, element.value);
                } else {
                    if (element.value) {
                        formData.append(element.name, element.value);
                    }
                }
            }
        }
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                         document.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            formData.append('_token', csrfToken);
        }
        
        // Update image order from DOM before submission (in case user reordered)
        updateImageOrderFromDOM();
        
        // Add existing image IDs (those not deleted)
        const existingImageIds = imageState.imageOrder
            .filter(item => item.type === 'existing' && !imageState.deletedImageIds.has(item.id))
            .map(item => normalizeVehicleImageId(item.id))
            .filter((id) => id !== null);
        existingImageIds.forEach(id => {
            formData.append('existing_image_ids[]', String(id));
        });
        
        // Add deleted image IDs (only valid DB ids — avoids validation errors)
        Array.from(imageState.deletedImageIds)
            .map(normalizeVehicleImageId)
            .filter((id) => id !== null)
            .forEach((id) => {
                formData.append('deleted_image_ids[]', String(id));
            });
        
        // Add new images (only new files, not from file input)
        imageState.imageOrder
            .filter(item => item.type === 'new')
            .forEach(item => {
                const file = imageState.newFiles.get(item.fileId);
                if (file) {
                    formData.append('images[]', file);
                }
            });
        
        // Add image sort order based on current order
        imageState.imageOrder.forEach((item, index) => {
            if (item.type === 'existing' && normalizeVehicleImageId(item.id) !== null) {
                formData.append(`image_sort_order[${item.id}]`, String(index));
            }
        });
        
        try {
            const response = await fetch(formAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                // Show validation errors
                if (result.errors) {
                    displayErrors(result.errors);
                } else {
                    displayGeneralError(result.message || 'Failed to update vehicle. Please try again.');
                }
            } else {
                // Success
                const successMsg = result.message || 'Vehicle updated successfully!';
                displaySuccess(successMsg);
                
                // Redirect to dashboard after 1.5 seconds
                setTimeout(() => {
                    // Extract token from form action and redirect to dashboard
                    const match = formAction.match(/\/seller-dashboard\/([^\/]+)/);
                    if (match && match[1]) {
                        window.location.href = `/seller-dashboard/${match[1]}`;
                    } else {
                        // Fallback: try to go back
                        window.history.back();
                    }
                }, 1500);
            }
        } catch (error) {
            console.error('Error updating vehicle:', error);
            displayGeneralError('An error occurred. Please try again.');
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Update Vehicle Listing';
            }
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
            errorContainer.className = 'w-full rounded-md border p-3 mb-4 error-container';
            form.insertBefore(errorContainer, form.firstChild);
        }
        
        errorContainer.innerHTML = `<p class="text-sm font-medium">${escapeHtml(message)}</p>`;
        errorContainer.classList.remove('hidden');
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function displayErrors(errors) {
        clearErrors();
        
        if (typeof errors === 'object' && !Array.isArray(errors)) {
            Object.keys(errors).forEach(field => {
                const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                fieldErrors.forEach(error => {
                    displayFieldError(field, error);
                });
            });
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

    function displaySuccess(message) {
        // Create success message element
        const successElement = document.createElement('div');
        successElement.className = 'w-full rounded-md border p-3 mb-4';
        successElement.style.cssText = 'border-color: oklch(0.8 0.15 145); background: oklch(0.95 0.1 145); color: oklch(0.4 0.2 145);';
        successElement.innerHTML = `<p class="text-sm font-medium">${escapeHtml(message)}</p>`;
        
        const form = document.getElementById('vehicle-form');
        if (form) {
            form.insertBefore(successElement, form.firstChild);
            successElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Location autocomplete (aligned with sell-your-car-form.js: city + region + postcode matching, mousedown on dropdown)
    function initLocationAutocomplete() {
        const locationInput = document.getElementById('seller_address');
        const postcodeInput = document.getElementById('seller_postcode');
        const locationDropdown = document.getElementById('location-autocomplete');
        const postcodeDropdown = document.getElementById('postcode-autocomplete');

        if (!locationInput || !postcodeInput || !locationDropdown || !postcodeDropdown) {
            return;
        }

        const locationsData = window.locationsData || [];
        if (!locationsData.length) {
            return;
        }

        function createLocationAutocomplete(input, dropdown, isLocationField) {
            let selectedIndex = -1;
            let currentMatches = [];
            let blurTimeout = null;

            function filterLocations(query) {
                if (!query || query.trim().length === 0) {
                    return [];
                }

                const queryLower = query.toLowerCase().trim();
                const matches = [];

                for (let i = 0; i < locationsData.length && matches.length < 10; i++) {
                    const location = locationsData[i];

                    if (isLocationField) {
                        const cityMatch = location.city && location.city.toLowerCase().includes(queryLower);
                        const regionMatch = location.region && location.region.toLowerCase().includes(queryLower);
                        if (cityMatch || regionMatch) {
                            matches.push({
                                ...location,
                                displayText: `${location.city}, ${location.region}`
                            });
                        }
                    } else if (
                        location.postcode &&
                        (location.postcode.toLowerCase().startsWith(queryLower) ||
                            location.postcode.toLowerCase().includes(queryLower))
                    ) {
                        matches.push({
                            ...location,
                            displayText: location.postcode
                        });
                    }
                }

                return matches;
            }

            function renderDropdown(matches) {
                if (matches.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }

                dropdown.innerHTML = '';
                currentMatches = matches;
                selectedIndex = -1;

                matches.forEach((location, index) => {
                    const item = document.createElement('div');
                    item.className = 'location-autocomplete-item';
                    item.dataset.index = String(index);
                    item.innerHTML = `<div class="location-autocomplete-item-text">${escapeHtml(location.displayText)}</div>`;

                    item.addEventListener('click', function () {
                        selectLocation(location);
                    });

                    item.addEventListener('mouseenter', function () {
                        selectedIndex = index;
                        updateHighlight();
                    });

                    dropdown.appendChild(item);
                });

                dropdown.classList.add('show');
            }

            function updateHighlight() {
                const items = dropdown.querySelectorAll('.location-autocomplete-item');
                items.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }

            function selectLocation(location) {
                if (isLocationField) {
                    input.value = location.displayText;
                    if (location.postcode && postcodeInput.value.trim() === '') {
                        postcodeInput.value = location.postcode;
                    }
                } else {
                    input.value = location.postcode;
                    if (location.city && locationInput.value.trim() === '') {
                        locationInput.value = `${location.city}, ${location.region}`;
                    }
                }

                dropdown.classList.remove('show');
                input.focus();
            }

            function handleInput() {
                const query = input.value;
                const matches = filterLocations(query);
                renderDropdown(matches);
            }

            function handleKeyDown(e) {
                if (!dropdown.classList.contains('show') || currentMatches.length === 0) {
                    return;
                }

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, currentMatches.length - 1);
                    updateHighlight();
                    const items = dropdown.querySelectorAll('.location-autocomplete-item');
                    if (items[selectedIndex]) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateHighlight();
                    if (selectedIndex >= 0) {
                        const items = dropdown.querySelectorAll('.location-autocomplete-item');
                        if (items[selectedIndex]) {
                            items[selectedIndex].scrollIntoView({ block: 'nearest' });
                        }
                    }
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    selectLocation(currentMatches[selectedIndex]);
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            }

            function handleFocus() {
                if (blurTimeout) {
                    clearTimeout(blurTimeout);
                    blurTimeout = null;
                }
                const query = input.value;
                if (query && query.trim().length > 0) {
                    const matches = filterLocations(query);
                    renderDropdown(matches);
                }
            }

            function handleBlur() {
                blurTimeout = setTimeout(function () {
                    dropdown.classList.remove('show');
                }, 200);
            }

            input.addEventListener('input', handleInput);
            input.addEventListener('keydown', handleKeyDown);
            input.addEventListener('focus', handleFocus);
            input.addEventListener('blur', handleBlur);

            dropdown.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
        }

        createLocationAutocomplete(locationInput, locationDropdown, true);
        createLocationAutocomplete(postcodeInput, postcodeDropdown, false);
    }
})();
