<div id="login-dialog" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="login-dialog-title">
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        onclick="closeLoginDialog()"
        aria-hidden="true"
    ></div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-background rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <div class="flex-1">
                    <h2 id="login-dialog-title" class="text-xl font-semibold text-foreground">
                        {{ __('messages.dialogs.login_to_account') }}
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ __('messages.dialogs.login_description') }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeLoginDialog()"
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
                <!-- Error Display Container -->
                <div id="login-errors" class="hidden w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                    <ul id="login-error-list" class="list-disc list-inside text-sm text-red-800"></ul>
                </div>

                <!-- Success Message -->
                <div id="login-success" class="hidden w-full rounded-md border border-green-200 bg-green-50 p-3 mb-4">
                    <p class="text-sm text-green-800"></p>
                </div>

                <!-- Form -->
                <form id="login-form" method="POST" action="{{ route('login.post') }}" class="space-y-4">
                    @csrf
                    
                    @if (isset($errors) && $errors->any())
                        <div id="login-errors" class="w-full rounded-md border border-red-200 bg-red-50 p-3 mb-4">
                            <ul id="login-error-list" class="list-disc list-inside text-sm text-red-800">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="space-y-2">
                        <label for="login-email" class="text-sm font-medium leading-none">
                            {{ __('messages.forms.email') }} <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="login-email" 
                            name="email" 
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="{{ __('messages.forms.enter_email') }}"
                        >
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="login-password" class="text-sm font-medium leading-none">
                                {{ __('messages.forms.password') }} <span class="text-red-500">*</span>
                            </label>
                            <a href="/auth/forgot-password" class="text-xs text-muted-foreground hover:text-foreground underline" onclick="event.stopPropagation(); closeLoginDialog();">
                                {{ __('messages.forms.forgot_password') }}
                            </a>
                        </div>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="login-password" 
                                name="password" 
                                required
                                autocomplete="current-password"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="{{ __('messages.forms.password') }}"
                            >
                            <button 
                                type="button" 
                                onclick="toggleLoginPassword()" 
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <svg id="login-password-eye" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="login-password-eye-off" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 hidden">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                    <line x1="2" x2="22" y1="2" y2="22"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button 
                            type="submit" 
                            id="login-submit-btn"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50"
                        >
                            <span id="login-submit-text">{{ __('messages.navigation.login') }}</span>
                        </button>
                        <button 
                            type="button"
                            onclick="closeLoginDialog()"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            {{ __('messages.common.cancel') }}
                        </button>
                    </div>
                </form>

                <div class="relative my-4 text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
                    <span class="relative z-10 bg-background px-2 text-muted-foreground">{{ __('messages.forms.or_continue_with') }}</span>
                </div>

                <a href="/auth/magic-link/login" onclick="event.stopPropagation(); closeLoginDialog();" class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    {{ __('messages.pages.login.magic_link_login') }}
                </a>

                <div class="mt-4 text-center text-sm text-muted-foreground">
                    {{ __('messages.pages.login.no_account') }} <a href="/auth/signup" onclick="event.stopPropagation(); closeLoginDialog();" class="text-foreground underline hover:text-primary">{{ __('messages.navigation.signup') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const dialogId = 'login-dialog';
    const formId = 'login-form';
    const form = document.getElementById(formId);
    const dialog = document.getElementById(dialogId);
    
    if (!form || !dialog) return;
    
    // Store callback for after login
    let loginCallback = null;
    
    // Global functions to open/close dialog
    window.openLoginDialog = function(callback) {
        loginCallback = callback || null;
        dialog.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        // Focus on email field
        setTimeout(() => {
            document.getElementById('login-email')?.focus();
        }, 100);
    };
    
    window.closeLoginDialog = function() {
        dialog.classList.add('hidden');
        document.body.style.overflow = '';
        // Reset form
        form.reset();
        // Clear callback
        loginCallback = null;
    };
    
    // Toggle password visibility
    window.toggleLoginPassword = function() {
        const input = document.getElementById('login-password');
        const eye = document.getElementById('login-password-eye');
        const eyeOff = document.getElementById('login-password-eye-off');
        
        if (input.type === 'password') {
            input.type = 'text';
            eye.classList.add('hidden');
            eyeOff.classList.remove('hidden');
        } else {
            input.type = 'password';
            eye.classList.remove('hidden');
            eyeOff.classList.add('hidden');
        }
    };
    
    // Handle ESC key to close dialog
    dialog.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeLoginDialog();
        }
    });
    
    // Handle form submission via AJAX
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('login-submit-btn');
        const submitText = document.getElementById('login-submit-text');
        const errorContainer = document.getElementById('login-errors');
        const errorList = document.getElementById('login-error-list');
        const successMessage = document.getElementById('login-success');
        
        // Hide previous messages
        if (errorContainer) errorContainer.classList.add('hidden');
        if (successMessage) successMessage.classList.add('hidden');
        if (errorList) errorList.innerHTML = '';
        
        // Disable submit button
        if (submitBtn) submitBtn.disabled = true;
        if (submitText) submitText.textContent = '{{ __('messages.dialogs.logging_in') }}';
        
        // Get form data
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            });
            
            // Check content type to determine response format
            const contentType = response.headers.get('content-type') || '';
            const isJson = contentType.includes('application/json');
            
            if (isJson) {
                // JSON response
                const result = await response.json();
                
                if (response.ok || response.status === 200) {
                    // Success - show success message
                    if (successMessage) {
                        successMessage.querySelector('p').textContent = result.message || '{{ __('messages.dialogs.login_successful') }}';
                        successMessage.classList.remove('hidden');
                    }
                    
                    // Close dialog
                    window.closeLoginDialog();
                    
                    // Show snackbar if available
                    if (window.showSnackbar) {
                        window.showSnackbar(result.message || '{{ __('messages.dialogs.login_successful') }}', 'success');
                    }
                    
                    // Execute callback if provided (after a short delay to ensure auth state is updated)
                    if (loginCallback && typeof loginCallback === 'function') {
                        setTimeout(() => {
                            try {
                                loginCallback();
                            } catch (error) {
                                console.error('Error executing login callback:', error);
                            }
                        }, 500);
                    } else {
                        // If no callback, reload page to refresh authentication state
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    // Error response
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
                        const errorMsgText = result.message || '{{ __('messages.dialogs.login_failed') }}';
                        if (errorList) {
                            errorList.innerHTML = `<li>${errorMsgText}</li>`;
                            if (errorContainer) errorContainer.classList.remove('hidden');
                        }
                    }
                }
            } else {
                // HTML response (redirect) - Laravel redirects on successful login
                // Reload page to get authenticated state
                if (response.redirected || response.status === 302 || response.status === 200) {
                    // If we have a callback, store it and execute after reload
                    if (loginCallback && typeof loginCallback === 'function') {
                        // Store callback info in sessionStorage
                        sessionStorage.setItem('pendingLoginCallback', 'true');
                    }
                    window.location.reload();
                } else {
                    // Error - try to show error message
                    const errorMsgText = '{{ __('messages.dialogs.login_failed') }}';
                    if (errorList) {
                        errorList.innerHTML = `<li>${errorMsgText}</li>`;
                        if (errorContainer) errorContainer.classList.remove('hidden');
                    }
                }
            }
        } catch (error) {
            console.error('Error submitting login form:', error);
            if (errorList) {
                errorList.innerHTML = '<li>{{ __('messages.dialogs.error_occurred') }}</li>';
                if (errorContainer) errorContainer.classList.remove('hidden');
            }
        } finally {
            // Re-enable submit button
            if (submitBtn) submitBtn.disabled = false;
            if (submitText) submitText.textContent = '{{ __('messages.navigation.login') }}';
        }
    });
    
    // Check for pending callback after page load (for redirect-based login)
    window.addEventListener('load', function() {
        if (sessionStorage.getItem('pendingLoginCallback') === 'true') {
            sessionStorage.removeItem('pendingLoginCallback');
            // Execute callback if it was stored
            if (loginCallback && typeof loginCallback === 'function') {
                setTimeout(() => {
                    try {
                        loginCallback();
                    } catch (error) {
                        console.error('Error executing login callback:', error);
                    }
                }, 500);
            }
        }
    });
})();
</script>
