@extends('layouts.app')

@section('title', 'Privacy Policy | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $privacyPageContent['privacy_header_title'] ?? __('messages.pages.privacy_policy.header_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $privacyPageContent['privacy_header_description'] ?? __('messages.pages.privacy_policy.header_description') }}
            </p>
        </div>
    </section>

    <!-- Privacy Policy Content Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-4xl">
                <div class="prose prose-lg max-w-none space-y-8">
                    <!-- Introduction -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_intro_title'] ?? __('messages.pages.privacy_policy.intro_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_intro_description'] ?? __('messages.pages.privacy_policy.intro_description') }}
                        </p>
                    </div>

                    <!-- Information We Collect -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_collect_title'] ?? __('messages.pages.privacy_policy.collect_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_collect_description'] ?? __('messages.pages.privacy_policy.collect_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_collect_item_1'] ?? __('messages.pages.privacy_policy.collect_item_1') }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_2'] ?? __('messages.pages.privacy_policy.collect_item_2') }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_3'] ?? __('messages.pages.privacy_policy.collect_item_3') }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_4'] ?? __('messages.pages.privacy_policy.collect_item_4') }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_5'] ?? __('messages.pages.privacy_policy.collect_item_5') }}</li>
                        </ul>
                    </div>

                    <!-- How We Use Your Information -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_use_title'] ?? __('messages.pages.privacy_policy.use_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_use_description'] ?? __('messages.pages.privacy_policy.use_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_use_item_1'] ?? __('messages.pages.privacy_policy.use_item_1') }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_2'] ?? __('messages.pages.privacy_policy.use_item_2') }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_3'] ?? __('messages.pages.privacy_policy.use_item_3') }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_4'] ?? __('messages.pages.privacy_policy.use_item_4') }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_5'] ?? __('messages.pages.privacy_policy.use_item_5') }}</li>
                        </ul>
                    </div>

                    <!-- Information Sharing -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_sharing_title'] ?? __('messages.pages.privacy_policy.sharing_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_sharing_description'] ?? __('messages.pages.privacy_policy.sharing_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_sharing_item_1'] ?? __('messages.pages.privacy_policy.sharing_item_1') }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_2'] ?? __('messages.pages.privacy_policy.sharing_item_2') }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_3'] ?? __('messages.pages.privacy_policy.sharing_item_3') }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_4'] ?? __('messages.pages.privacy_policy.sharing_item_4') }}</li>
                        </ul>
                    </div>

                    <!-- Data Security -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_security_title'] ?? __('messages.pages.privacy_policy.security_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_security_description'] ?? __('messages.pages.privacy_policy.security_description') }}
                        </p>
                    </div>

                    <!-- Your Rights -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_rights_title'] ?? __('messages.pages.privacy_policy.rights_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_rights_description'] ?? __('messages.pages.privacy_policy.rights_description') }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_rights_item_1'] ?? __('messages.pages.privacy_policy.rights_item_1') }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_2'] ?? __('messages.pages.privacy_policy.rights_item_2') }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_3'] ?? __('messages.pages.privacy_policy.rights_item_3') }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_4'] ?? __('messages.pages.privacy_policy.rights_item_4') }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_5'] ?? __('messages.pages.privacy_policy.rights_item_5') }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_6'] ?? __('messages.pages.privacy_policy.rights_item_6') }}</li>
                        </ul>
                    </div>

                    <!-- Cookies -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_cookies_title'] ?? __('messages.pages.privacy_policy.cookies_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_cookies_description'] ?? __('messages.pages.privacy_policy.cookies_description') }}
                        </p>
                    </div>

                    <!-- Changes to Privacy Policy -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_changes_title'] ?? __('messages.pages.privacy_policy.changes_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_changes_description'] ?? __('messages.pages.privacy_policy.changes_description') }}
                        </p>
                    </div>

                    <!-- Contact Us -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_contact_title'] ?? __('messages.pages.privacy_policy.contact_title') }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_contact_description'] ?? __('messages.pages.privacy_policy.contact_description') }}
                        </p>
                        <div class="space-y-2 text-muted-foreground">
                            <p><strong>{{ __('messages.pages.privacy_policy.contact_email_label') }}:</strong> {{ $privacyPageContent['privacy_contact_email'] ?? __('messages.pages.privacy_policy.contact_email') }}</p>
                            <p><strong>{{ __('messages.pages.privacy_policy.contact_address_label') }}:</strong> {{ $privacyPageContent['privacy_contact_address'] ?? __('messages.pages.privacy_policy.contact_address') }}</p>
                        </div>
                    </div>

                    <!-- Last Updated -->
                    <div class="border-t border-border pt-8">
                        <p class="text-sm text-muted-foreground">
                            {{ $privacyPageContent['privacy_last_updated'] ?? __('messages.pages.privacy_policy.last_updated', ['date' => date('F j, Y')]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
