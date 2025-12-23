@extends('layouts.app')

@section('title', 'Profile - Bilskyen')

@section('content')
<div class="container py-4 md:py-8">
    <div>
        <h2 class="text-xl font-bold">Profile</h2>
        <p class="text-muted-foreground max-w-xl">
            Update your personal details below. This information helps us contact
            you and verify your identity when needed.
        </p>
    </div>

    <hr class="mt-3 mb-6 border-border" />

    <div class="flex h-full w-full flex-col items-center justify-center gap-4">
        @if(session('status'))
            <div class="w-full rounded-md border border-green-200 bg-green-50 p-4 text-green-800">
                <p class="text-sm font-medium">{{ session('status') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="w-full rounded-md border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="text-sm font-medium">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="w-full rounded-md border border-red-200 bg-red-50 p-4 text-red-800">
                <p class="text-sm font-medium mb-2">Please fix the following errors:</p>
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="flex w-full flex-col gap-3.5 md:grid md:grid-cols-2" method="POST" action="{{ route('profile.update') }}">
            @csrf
            
            <!-- Full Name Field -->
            <div class="space-y-2">
                <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Full Name
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    placeholder="John Doe"
                    value="{{ old('name', $user?->name ?? '') }}"
                    class="flex h-10 w-full rounded-md border {{ $errors->has('name') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                @error('name')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    Enter your legal full name as it appears on official
                    documents.
                </p>
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Email
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="email@mail.com"
                    value="{{ old('email', $user?->email ?? '') }}"
                    class="flex h-10 w-full rounded-md border {{ $errors->has('email') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                @error('email')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    Provide a valid email address for account notifications and
                    recovery.
                </p>
            </div>

            <!-- Phone Field -->
            <div class="space-y-2 flex flex-col items-start">
                <label for="phone" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Phone number
                </label>
                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    placeholder="+45 12 34 56 78"
                    value="{{ old('phone', $user?->phone ?? '') }}"
                    class="flex h-10 w-full rounded-md border {{ $errors->has('phone') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                @error('phone')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    Enter your active phone number including country code.
                </p>
            </div>

            <!-- Address Field -->
            <div class="space-y-2">
                <label for="address" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    Address
                </label>
                <textarea
                    id="address"
                    name="address"
                    placeholder="Address"
                    rows="3"
                    class="flex min-h-[80px] w-full rounded-md border {{ $errors->has('address') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                >{{ old('address', $user?->address ?? '') }}</textarea>
                @error('address')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    Enter your current residential or business address.
                </p>
            </div>

            <!-- Submit Button -->
            <div class="col-span-2 flex w-full items-center justify-end">
                <button
                    type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

