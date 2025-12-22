@extends('layouts.auth')

@section('title', 'Verify Magic Link - Bilskyen')

@section('content')
<div class="flex h-full w-full flex-col items-center justify-center gap-4">
    @if(session('error'))
        <div class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive w-full">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                </svg>
                <div>
                    <h3 class="font-semibold">Error</h3>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        <a href="/auth/login" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Go to Login
        </a>
    @elseif(!isset($token) || empty($token))
        <div class="rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive w-full">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                </svg>
                <div>
                    <h3 class="font-semibold">Token Error</h3>
                    <p class="text-sm">No token provided. Please check your link.</p>
                </div>
            </div>
        </div>
        <a href="/auth/login" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Go to Login
        </a>
    @else
    <form method="POST" action="{{ route('magic-link.verify.post') }}" class="w-full">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="callbackURL" value="{{ $callbackURL ?? '/' }}">
        
        <div class="flex flex-col items-center gap-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            <p class="text-muted-foreground">Verifying magic link...</p>
            <button type="submit" class="hidden">Verify</button>
        </div>
    </form>
    <script>
        // Auto-submit form on page load
        document.querySelector('form').submit();
    </script>
    @endif
</div>
@endsection

