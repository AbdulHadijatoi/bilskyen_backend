@extends('layouts.app')

@section('title', __('messages.pages.contact.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $contactPageContent['contact_header_title'] ?? __('messages.pages.contact.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $contactPageContent['contact_header_description'] ?? __('messages.pages.contact.description') }}
            </p>
        </div>
    </section>

    <!-- Contact Form and Details Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Contact Form -->
                <div class="rounded-lg border border-border bg-card">
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold tracking-tight">{{ $contactPageContent['contact_form_title'] ?? __('messages.pages.contact.form_title') }}</h2>
                        <p class="text-muted-foreground mt-2">
                            {{ $contactPageContent['contact_form_description'] ?? __('messages.pages.contact.form_description') }}
                        </p>
                    </div>
                    <div class="p-6 pt-0">
                        @if(session('success'))
                            <div class="mb-6 rounded-md border p-3" style="border-color: oklch(0.8 0.15 145); background: oklch(0.95 0.1 145); color: oklch(0.4 0.2 145);">
                                <p class="text-sm font-medium">{{ session('success') }}</p>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="mb-6 rounded-md border p-3" style="border-color: oklch(0.8 0.2 27); background: oklch(0.95 0.1 27); color: oklch(0.4 0.2 27);">
                                <p class="text-sm font-medium">{{ session('error') }}</p>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="mb-6 rounded-md border p-3" style="border-color: oklch(0.8 0.2 27); background: oklch(0.95 0.1 27); color: oklch(0.4 0.2 27);">
                                <ul class="list-inside list-disc text-sm">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form class="space-y-6" method="POST" action="{{ route('contact.submit') }}">
                            @csrf
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">{{ __('messages.forms.full_name') }}</label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        value="{{ old('name') }}"
                                        placeholder="{{ __('messages.forms.enter_full_name') }}"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">{{ __('messages.forms.email') }}</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email') }}"
                                        placeholder="{{ __('messages.forms.enter_email') }}"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="subject" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">{{ __('messages.forms.subject') }}</label>
                                <select
                                    id="subject"
                                    name="subject"
                                    required
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="" @selected(old('subject') === '')>{{ __('messages.pages.contact.select_subject') }}</option>
                                    <option value="vehicle-inquiry" @selected(old('subject') === 'vehicle-inquiry')>{{ __('messages.pages.contact.vehicle_inquiry') }}</option>
                                    <option value="financing" @selected(old('subject') === 'financing')>{{ __('messages.pages.contact.financing_question') }}</option>
                                    <option value="service-appointment" @selected(old('subject') === 'service-appointment')>{{ __('messages.pages.contact.service_appointment') }}</option>
                                    <option value="general" @selected(old('subject') === 'general')>{{ __('messages.pages.contact.general_question') }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="message" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">{{ __('messages.forms.message') }}</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    placeholder="{{ __('messages.pages.contact.write_message_here') }}"
                                    rows="6"
                                    required
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                    <path d="m22 2-7 20-4-9-9-4Z"></path>
                                    <path d="M22 2 11 13"></path>
                                </svg>
                                {{ __('messages.pages.contact.send_message') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="space-y-8">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold">{{ $contactPageContent['contact_info_title'] ?? __('messages.pages.contact.info_title') }}</h2>
                        <p class="text-muted-foreground">
                            {{ $contactPageContent['contact_info_description'] ?? __('messages.pages.contact.info_description') }}
                        </p>
                    </div>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <path d="M20 10c0 6-1 9-9 9s-9-3-9-9 1-9 9-9 9 3 9 9Z"></path>
                                    <path d="M20 10c0 3.866-4 7-9 7s-9-3.134-9-7 4-7 9-7 9 3.134 9 7Z"></path>
                                    <path d="M6 10h12"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">{{ __('messages.pages.contact.our_address') }}</h3>
                                <p class="text-muted-foreground">
                                    {{ $contactPageContent['contact_address'] ?? '123 Dealership Lane, Copenhagen, Denmark' }}
                                </p>
                                <a
                                    href="#"
                                    class="text-primary mt-1 inline-block text-sm font-medium hover:underline"
                                >
                                    {{ __('messages.pages.contact.get_directions') }}
                                </a>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">{{ __('messages.pages.contact.phone') }}</h3>
                                <p class="text-muted-foreground">
                                    {{ $contactPageContent['contact_phone'] ?? '+45 12 34 56 78' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">{{ __('messages.pages.contact.email') }}</h3>
                                <p class="text-muted-foreground">
                                    {{ $contactPageContent['contact_email'] ?? 'info@bilskyen.dk' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">{{ __('messages.pages.contact.business_hours') }}</h3>
                                <p class="text-muted-foreground">
                                    {{ $contactPageContent['contact_hours_weekdays'] ?? __('messages.pages.contact.default_hours_weekdays') }}
                                </p>
                                <p class="text-muted-foreground">
                                    {{ $contactPageContent['contact_hours_weekend'] ?? __('messages.pages.contact.default_hours_weekend') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="bg-muted">
        <div class="relative h-96 w-full">
            @php
                $mapImage = isset($contactPageImages['contact_map_image']) && count($contactPageImages['contact_map_image']) > 0 
                    ? $contactPageImages['contact_map_image'][0] 
                    : null;
            @endphp
            <img
                src="{{ $mapImage && isset($mapImage['image_url']) ? $mapImage['image_url'] : '/images/showroom.jpg' }}"
                alt="{{ $mapImage && isset($mapImage['alt_text']) ? $mapImage['alt_text'] : __('messages.pages.contact.showroom') }}"
                class="h-full w-full object-cover"
                onerror="this.src='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'"
            />
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white">
                <h2 class="text-3xl font-bold">{{ $contactPageContent['contact_map_title'] ?? __('messages.pages.contact.visit_showroom') }}</h2>
                <p class="mt-2 max-w-md">{{ $contactPageContent['contact_map_address'] ?? ($contactPageContent['contact_address'] ?? '123 Dealership Lane, Copenhagen, Denmark') }}</p>
            </div>
        </div>
    </section>
</div>
@endsection

