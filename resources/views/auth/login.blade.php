@extends('layouts.auth')

@section('title', 'Login - Bilskyen')

@section('content')
<div class="flex h-full w-full flex-col items-center justify-center gap-4">
    <div class="flex w-full flex-col space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">
            Login into your account
        </h1>
        <p class="text-sm text-muted-foreground">
            Enter your email and password to login to your account.
        </p>
    </div>

    <!-- Error Alert -->
    <div id="error-alert" class="hidden w-full rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
        <div class="flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" x2="12" y1="8" y2="12"></line>
                <line x1="12" x2="12.01" y1="16" y2="16"></line>
            </svg>
            <div>
                <h3 class="font-semibold">Login Error</h3>
                <p id="error-message" class="text-sm"></p>
            </div>
        </div>
    </div>

    <form id="login-form" class="grid w-full gap-3.5" onsubmit="event.preventDefault(); handleLogin();">
        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Email</label>
            <input id="email" name="email" type="email" placeholder="johndoe@mail.com" autocomplete="email" tabindex="1" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
            <p id="email-error" class="hidden text-sm text-destructive"></p>
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Password</label>
                <a href="/auth/forgot-password" class="ml-auto inline-block text-sm underline" tabindex="3">
                    Forgot your password?
                </a>
            </div>
            <div class="relative">
                <input id="password" name="password" type="password" placeholder="Your Password" autocomplete="current-password" tabindex="2" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                    <svg id="password-eye" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg id="password-eye-off" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 hidden">
                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                        <line x1="2" x2="22" y1="2" y2="22"></line>
                    </svg>
                </button>
            </div>
            <p id="password-error" class="hidden text-sm text-destructive"></p>
        </div>

        <button type="submit" id="login-button" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            <span id="login-button-text">Login</span>
            <svg id="login-spinner" class="hidden ml-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </form>

    <div class="relative my-4 w-full text-center text-sm after:absolute after:inset-0 after:top-1/2 after:z-0 after:flex after:items-center after:border-t after:border-border">
        <span class="relative z-10 bg-background px-2 text-muted-foreground">Or continue with</span>
    </div>

    <a href="/auth/magic-link/login" class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
        Magic Link Login
    </a>

    <div class="mt-4 text-center text-sm">
        Don&apos;t have an account? <a href="/auth/signup" class="underline">Sign up</a>
    </div>
</div>

<script>
const API_URL = '{{ url("/api") }}';

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(inputId + '-eye');
    const eyeOff = document.getElementById(inputId + '-eye-off');
    
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.add('hidden');
        eyeOff.classList.remove('hidden');
    } else {
        input.type = 'password';
        eye.classList.remove('hidden');
        eyeOff.classList.add('hidden');
    }
}

function showError(message) {
    const errorAlert = document.getElementById('error-alert');
    const errorMessage = document.getElementById('error-message');
    errorMessage.textContent = message;
    errorAlert.classList.remove('hidden');
}

function hideError() {
    const errorAlert = document.getElementById('error-alert');
    errorAlert.classList.add('hidden');
}

function clearFieldErrors() {
    document.getElementById('email-error').classList.add('hidden');
    document.getElementById('password-error').classList.add('hidden');
    document.getElementById('email').classList.remove('border-destructive');
    document.getElementById('password').classList.remove('border-destructive');
}

function setFieldError(field, message) {
    const errorElement = document.getElementById(field + '-error');
    const inputElement = document.getElementById(field);
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
    inputElement.classList.add('border-destructive');
}

function setLoading(isLoading) {
    const button = document.getElementById('login-button');
    const buttonText = document.getElementById('login-button-text');
    const spinner = document.getElementById('login-spinner');
    
    if (isLoading) {
        button.disabled = true;
        buttonText.textContent = 'Logging in...';
        spinner.classList.remove('hidden');
    } else {
        button.disabled = false;
        buttonText.textContent = 'Login';
        spinner.classList.add('hidden');
    }
}

async function handleLogin() {
    hideError();
    clearFieldErrors();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    // Basic client-side validation
    if (!email) {
        setFieldError('email', 'Email is required');
        return;
    }
    
    if (!password) {
        setFieldError('password', 'Password is required');
        return;
    }
    
    setLoading(true);
    
    try {
        const response = await fetch(`${API_URL}/auth/sign-in/email`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
            }),
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            // Handle validation errors
            if (response.status === 422 && data.errors) {
                if (data.errors.email) {
                    setFieldError('email', Array.isArray(data.errors.email) ? data.errors.email[0] : data.errors.email);
                }
                if (data.errors.password) {
                    setFieldError('password', Array.isArray(data.errors.password) ? data.errors.password[0] : data.errors.password);
                }
                showError(data.message || 'Validation failed');
            } else {
                // Handle other errors
                showError(data.message || 'Login failed. Please check your credentials.');
            }
            setLoading(false);
            return;
        }
        
        // Success - set cookie and redirect
        if (data.token) {
            // Set cookie (30 days expiry)
            const expiryDate = new Date();
            expiryDate.setTime(expiryDate.getTime() + (30 * 24 * 60 * 60 * 1000));
            document.cookie = `auth_token=${data.token}; path=/; expires=${expiryDate.toUTCString()}; SameSite=Lax`;
            
            // Redirect based on user role
            const userRole = data.user?.role;
            if (userRole === 'admin') {
                window.location.href = '/admin';
            } else if (userRole === 'dealer') {
                window.location.href = '/dealer';
            } else {
                window.location.href = '/';
            }
        } else {
            showError('Login successful but no token received');
            setLoading(false);
        }
    } catch (error) {
        console.error('Login error:', error);
        showError('Network error. Please check if the backend is running.');
        setLoading(false);
    }
}
</script>
@endsection

