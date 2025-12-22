@extends('layouts.auth')

@section('title', 'Verify Email - Bilskyen')

@section('content')
<div class="flex h-full w-full flex-col items-start gap-4">
    <h2 class="text-2xl font-semibold">Verify Your Email</h2>

    <p>
        We've sent a verification email to your inbox when you signed up. Please check your email and follow the instructions to verify your account. The email might be further down in your inbox depending on when you signed up, so be sure to look carefully.
    </p>

    <p class="text-muted-foreground">
        Didn't receive the email? Please check your spam or junk folder. If it has expired or hasn't arrived yet, you can request a new one <span id="timerText">now</span>.
    </p>

    @if (session('status'))
        <div class="w-full rounded-lg border border-green-500/50 bg-green-500/10 p-4 text-green-600 dark:text-green-400">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <p class="text-sm">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="w-full rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                </svg>
                <div>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Resend Verification Email
        </button>
    </form>
</div>
@endsection

