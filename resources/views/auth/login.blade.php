@extends('layouts.auth')

@section('title', __('messages.pages.login.title') . ' - Bilskyen')

@section('content')
<div class="flex w-full flex-col gap-6">
    <div class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight text-foreground">
            {{ __('messages.pages.login.title') }}
        </h1>
        <p class="text-sm text-muted-foreground">
            {{ __('messages.pages.login.description') }}
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-destructive">
            <h3 class="font-semibold">{{ __('messages.pages.login.login_error') }}</h3>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" class="grid gap-4">
        @csrf
        @if(!empty($returnUrl))
            <input type="hidden" name="return_url" value="{{ $returnUrl }}">
        @endif
        @include('components.bot-protection')
        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium">{{ __('messages.forms.email') }}</label>
            <input id="email" name="email" type="email" placeholder="{{ __('messages.forms.enter_email') }}" autocomplete="email" tabindex="1" required class="site-input">
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between gap-3">
                <label for="password" class="text-sm font-medium">{{ __('messages.forms.password') }}</label>
                <a href="/auth/forgot-password" class="text-sm text-primary hover:underline" tabindex="3">
                    {{ __('messages.forms.forgot_password') }}
                </a>
            </div>
            <div class="relative">
                <input id="password" name="password" type="password" placeholder="{{ __('messages.forms.placeholders.password') }}" autocomplete="current-password" tabindex="2" required class="site-input pr-10">
                <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground" aria-label="Toggle password visibility">
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

        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-hover">
            {{ __('messages.navigation.login') }}
        </button>
    </form>

    <div class="relative text-center text-sm">
        <span class="relative z-10 bg-card px-3 text-muted-foreground">{{ __('messages.forms.or_continue_with') }}</span>
        <div class="absolute inset-x-0 top-1/2 h-px bg-border"></div>
    </div>

    <a href="/auth/magic-link/login" class="inline-flex h-11 w-full items-center justify-center rounded-lg border border-border bg-card px-4 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-muted">
        {{ __('messages.pages.login.magic_link_login') }}
    </a>

    <p class="text-center text-sm text-muted-foreground">
        {{ __('messages.pages.login.no_account') }}
        <a href="/auth/signup" class="font-medium text-primary hover:underline">{{ __('messages.navigation.signup') }}</a>
    </p>
</div>

<script>
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
</script>
@endsection

