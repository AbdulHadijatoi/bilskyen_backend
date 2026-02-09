@extends('layouts.app')

@section('title', __('messages.pages.terms_of_service.page_title') . ' | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $termsPageContent['terms_header_title'] ?? __('messages.pages.terms_of_service.header_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $termsPageContent['terms_header_description'] ?? __('messages.pages.terms_of_service.header_description') }}
            </p>
        </div>
    </section>

    <!-- Terms of Service Content Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-4xl">
                <div class="prose prose-lg max-w-none space-y-8">
                    <!-- Agreement to Terms -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_agreement_title'] ?? __('messages.pages.terms_of_service.agreement_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_agreement_description'] ?? __('messages.pages.terms_of_service.agreement_description') }}
                        </p>
                    </div>

                    <!-- Use License -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_license_title'] ?? __('messages.pages.terms_of_service.license_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_license_description'] ?? __('messages.pages.terms_of_service.license_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_license_item_1'] ?? __('messages.pages.terms_of_service.license_item_1') }}</li>
                            <li>{{ $termsPageContent['terms_license_item_2'] ?? __('messages.pages.terms_of_service.license_item_2') }}</li>
                            <li>{{ $termsPageContent['terms_license_item_3'] ?? __('messages.pages.terms_of_service.license_item_3') }}</li>
                            <li>{{ $termsPageContent['terms_license_item_4'] ?? __('messages.pages.terms_of_service.license_item_4') }}</li>
                            <li>{{ $termsPageContent['terms_license_item_5'] ?? __('messages.pages.terms_of_service.license_item_5') }}</li>
                        </ul>
                    </div>

                    <!-- User Accounts -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_accounts_title'] ?? __('messages.pages.terms_of_service.accounts_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_accounts_description'] ?? __('messages.pages.terms_of_service.accounts_description') }}
                        </p>
                    </div>

                    <!-- Vehicle Listings -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_listings_title'] ?? __('messages.pages.terms_of_service.listings_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_listings_description'] ?? __('messages.pages.terms_of_service.listings_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_listings_item_1'] ?? __('messages.pages.terms_of_service.listings_item_1') }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_2'] ?? __('messages.pages.terms_of_service.listings_item_2') }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_3'] ?? __('messages.pages.terms_of_service.listings_item_3') }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_4'] ?? __('messages.pages.terms_of_service.listings_item_4') }}</li>
                        </ul>
                    </div>

                    <!-- Prohibited Uses -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_prohibited_title'] ?? __('messages.pages.terms_of_service.prohibited_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_prohibited_description'] ?? __('messages.pages.terms_of_service.prohibited_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_prohibited_item_1'] ?? __('messages.pages.terms_of_service.prohibited_item_1') }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_2'] ?? __('messages.pages.terms_of_service.prohibited_item_2') }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_3'] ?? __('messages.pages.terms_of_service.prohibited_item_3') }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_4'] ?? __('messages.pages.terms_of_service.prohibited_item_4') }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_5'] ?? __('messages.pages.terms_of_service.prohibited_item_5') }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_6'] ?? __('messages.pages.terms_of_service.prohibited_item_6') }}</li>
                        </ul>
                    </div>

                    <!-- Disclaimer -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_disclaimer_title'] ?? __('messages.pages.terms_of_service.disclaimer_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_disclaimer_description'] ?? __('messages.pages.terms_of_service.disclaimer_description') }}
                        </p>
                    </div>

                    <!-- Limitations -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_limitations_title'] ?? __('messages.pages.terms_of_service.limitations_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_limitations_description'] ?? __('messages.pages.terms_of_service.limitations_description') }}
                        </p>
                    </div>

                    <!-- Accuracy of Materials -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_accuracy_title'] ?? __('messages.pages.terms_of_service.accuracy_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_accuracy_description'] ?? __('messages.pages.terms_of_service.accuracy_description') }}
                        </p>
                    </div>

                    <!-- Links -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_links_title'] ?? __('messages.pages.terms_of_service.links_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_links_description'] ?? __('messages.pages.terms_of_service.links_description') }}
                        </p>
                    </div>

                    <!-- Modifications -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_modifications_title'] ?? __('messages.pages.terms_of_service.modifications_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_modifications_description'] ?? __('messages.pages.terms_of_service.modifications_description') }}
                        </p>
                    </div>

                    <!-- Governing Law -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_governing_title'] ?? __('messages.pages.terms_of_service.governing_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_governing_description'] ?? __('messages.pages.terms_of_service.governing_description') }}
                        </p>
                    </div>

                    <!-- Contact Us -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_contact_title'] ?? __('messages.pages.terms_of_service.contact_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_contact_description'] ?? __('messages.pages.terms_of_service.contact_description') }}
                        </p>
                        <div class="space-y-2 text-muted-foreground">
                            <p><strong>{{ __('messages.pages.terms_of_service.contact_email_label') }}:</strong> {{ $termsPageContent['terms_contact_email'] ?? __('messages.pages.terms_of_service.contact_email') }}</p>
                            <p><strong>{{ __('messages.pages.terms_of_service.contact_address_label') }}:</strong> {{ $termsPageContent['terms_contact_address'] ?? __('messages.pages.terms_of_service.contact_address') }}</p>
                        </div>
                    </div>

                    <!-- Last Updated -->
                    <div class="border-t border-border pt-8">
                        <p class="text-sm text-muted-foreground">
                            {{ $termsPageContent['terms_last_updated'] ?? __('messages.pages.terms_of_service.last_updated', ['date' => date('F j, Y')]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
