@extends('layouts.auth')

@section('title', 'Forgot Password - Bilskyen')

@section('content')
<div class="flex w-full flex-col items-center justify-center gap-4">
    <div class="flex w-full flex-col space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">
            Forgot Password
        </h1>
        <p class="text-sm text-muted-foreground">
            Enter your email address to receive a password reset link.
        </p>
    </div>

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

    @if ($errors->any())
        <div class="w-full rounded-lg border border-destructive/50 bg-destructive/10 p-4 text-destructive">
            <div class="flex">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-5 w-5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                </svg>
                <div>
                    <h3 class="font-semibold">Error</h3>
                    <ul class="mt-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="grid w-full gap-3.5">
        @csrf
        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Email</label>
            <input id="email" name="email" type="email" placeholder="johndoe@mail.com" autocomplete="email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
        </div>

        <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Send Password Reset Email
        </button>
    </form>

    <div class="mt-4 text-center text-sm">
        Remember your password? <a href="/auth/login" class="underline">Login</a>
    </div>
</div>
@endsection

