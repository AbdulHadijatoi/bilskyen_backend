@extends('layouts.app')

@section('title', $seo['meta_title'] ?? __('messages.dealer_marketing.contact.page_title'))

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $content['contact_header_title'] ?? __('messages.dealer_marketing.contact.title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $content['contact_header_description'] ?? __('messages.dealer_marketing.contact.subtitle') }}
            </p>
        </div>
    </section>

    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16 max-w-5xl mx-auto">
                <div class="rounded-lg border border-border bg-card p-6 md:p-8">
                    @if(session('success'))
                        <div class="mb-6 rounded-md border p-3 bg-green-50 text-green-800 border-green-200">
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 rounded-md border p-3 bg-red-50 text-red-800 border-red-200">
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="mb-6 rounded-md border p-3 bg-red-50 text-red-800 border-red-200">
                            <ul class="list-inside list-disc text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form class="space-y-6" method="POST" action="{{ route('for-dealers.contact.submit') }}">
                        @csrf
                        @include('components.bot-protection')
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label for="name" class="text-sm font-medium">{{ __('messages.forms.full_name') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label for="email" class="text-sm font-medium">{{ __('messages.forms.email') }}</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="subject" class="text-sm font-medium">{{ __('messages.forms.subject') }}</label>
                            <select id="subject" name="subject" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                @php $selectedSubject = old('subject', request('subject', 'general')); @endphp
                                <option value="general" @selected($selectedSubject === 'general')>{{ __('messages.dealer_marketing.contact.subject_general') }}</option>
                                <option value="pricing" @selected($selectedSubject === 'pricing')>{{ __('messages.dealer_marketing.contact.subject_pricing') }}</option>
                                <option value="enterprise" @selected($selectedSubject === 'enterprise')>{{ __('messages.dealer_marketing.contact.subject_enterprise') }}</option>
                                <option value="onboarding" @selected($selectedSubject === 'onboarding')>{{ __('messages.dealer_marketing.contact.subject_onboarding') }}</option>
                                <option value="support" @selected($selectedSubject === 'support')>{{ __('messages.dealer_marketing.contact.subject_support') }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="message" class="text-sm font-medium">{{ __('messages.forms.message') }}</label>
                            <textarea id="message" name="message" rows="5" required class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-md bg-primary text-sm font-medium text-primary-foreground hover:bg-primary/90">
                            {{ __('messages.pages.contact.send_message') }}
                        </button>
                    </form>
                </div>

                <div class="space-y-8">
                    <div>
                        <h2 class="text-xl font-semibold mb-4">{{ __('messages.dealer_marketing.contact.details_title') }}</h2>
                        <ul class="space-y-4 text-sm text-muted-foreground">
                            @if(!empty($contactContent['contact_email']))
                                <li><strong class="text-foreground">{{ __('messages.forms.email') }}:</strong> {{ $contactContent['contact_email'] }}</li>
                            @endif
                            @if(!empty($contactContent['contact_phone']))
                                <li><strong class="text-foreground">{{ __('messages.forms.phone') }}:</strong> {{ $contactContent['contact_phone'] }}</li>
                            @endif
                            @if(!empty($contactContent['contact_address']))
                                <li><strong class="text-foreground">{{ __('messages.forms.address') }}:</strong> {{ $contactContent['contact_address'] }}</li>
                            @endif
                        </ul>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/40 p-6">
                        <h3 class="font-semibold mb-2">{{ __('messages.dealer_marketing.contact.already_account') }}</h3>
                        <p class="text-sm text-muted-foreground mb-4">{{ __('messages.dealer_marketing.contact.login_hint') }}</p>
                        <a href="{{ $panelUrl }}/auth/login" class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-background px-4 text-sm font-medium hover:bg-muted">
                            {{ __('messages.dealer_marketing.nav.login') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
