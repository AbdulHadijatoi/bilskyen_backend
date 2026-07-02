@extends('layouts.app')

@section('title', __('messages.pages.profile.title') . ' | Bilskyen')

@section('content')
<div class="panel-content panel-page">
    <x-panel.page-header
        :title="__('messages.pages.profile.title')"
        :subtitle="__('messages.pages.profile.description')"
    />

    <div class="flex h-full w-full flex-col items-center justify-center gap-4">
        @if(session('status'))
            <div class="w-full rounded-md border border-primary/50 bg-primary/10 p-4 text-primary">
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
                <p class="text-sm font-medium mb-2">{{ __('messages.pages.profile.fix_errors') }}</p>
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
                    {{ __('messages.pages.profile.full_name_label') }}
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    placeholder="{{ __('messages.forms.placeholders.full_name') }}"
                    value="{{ old('name', $user?->name ?? '') }}"
                    class="flex h-10 w-full rounded-md border {{ $errors->has('name') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                @error('name')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    {{ __('messages.pages.profile.full_name_help') }}
                </p>
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {{ __('messages.pages.profile.email_label') }}
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    placeholder="{{ __('messages.forms.placeholders.email') }}"
                    value="{{ old('email', $user?->email ?? '') }}"
                    class="flex h-10 w-full rounded-md border {{ $errors->has('email') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                />
                @error('email')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    {{ __('messages.pages.profile.email_help') }}
                </p>
            </div>

            <!-- Phone Field -->
            <div class="space-y-2 flex flex-col items-start">
                <label for="phone" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {{ __('messages.pages.profile.phone_label') }}
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
                    {{ __('messages.pages.profile.phone_help') }}
                </p>
            </div>

            <!-- Address Field -->
            <div class="space-y-2">
                <label for="address" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                    {{ __('messages.pages.profile.address_label') }}
                </label>
                <textarea
                    id="address"
                    name="address"
                    placeholder="{{ __('messages.forms.address') }}"
                    rows="3"
                    class="flex min-h-[80px] w-full rounded-md border {{ $errors->has('address') ? 'border-red-500' : 'border-input' }} bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                >{{ old('address', $user?->address ?? '') }}</textarea>
                @error('address')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-muted-foreground text-sm">
                    {{ __('messages.pages.profile.address_help') }}
                </p>
            </div>

            <!-- Submit Button -->
            <div class="col-span-2 flex w-full items-center justify-end">
                <button
                    type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50"
                >
                    {{ __('messages.pages.profile.update_profile') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

