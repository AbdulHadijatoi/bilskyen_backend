@extends('layouts.app')

@section('title', 'Privacy Policy | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $privacyPageContent['privacy_header_title'] ?? 'Privacy Policy' }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $privacyPageContent['privacy_header_description'] ?? 'Your privacy is important to us. This policy explains how we collect, use, and protect your personal information.' }}
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
                            {{ $privacyPageContent['privacy_intro_title'] ?? 'Introduction' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_intro_description'] ?? 'At Bilskyen, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.' }}
                        </p>
                    </div>

                    <!-- Information We Collect -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_collect_title'] ?? 'Information We Collect' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_collect_description'] ?? 'We collect information that you provide directly to us, including:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_collect_item_1'] ?? 'Personal identification information (name, email address, phone number)' }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_2'] ?? 'Vehicle inquiry and contact form submissions' }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_3'] ?? 'Account information when you create an account' }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_4'] ?? 'Payment information for transactions' }}</li>
                            <li>{{ $privacyPageContent['privacy_collect_item_5'] ?? 'Communication preferences and feedback' }}</li>
                        </ul>
                    </div>

                    <!-- How We Use Your Information -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_use_title'] ?? 'How We Use Your Information' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_use_description'] ?? 'We use the information we collect to:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_use_item_1'] ?? 'Process and respond to your inquiries and requests' }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_2'] ?? 'Provide, maintain, and improve our services' }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_3'] ?? 'Send you important updates and communications' }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_4'] ?? 'Process transactions and send related information' }}</li>
                            <li>{{ $privacyPageContent['privacy_use_item_5'] ?? 'Detect, prevent, and address technical issues and fraud' }}</li>
                        </ul>
                    </div>

                    <!-- Information Sharing -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_sharing_title'] ?? 'Information Sharing and Disclosure' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_sharing_description'] ?? 'We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_sharing_item_1'] ?? 'With service providers who assist us in operating our website and conducting our business' }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_2'] ?? 'When required by law or to protect our rights and safety' }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_3'] ?? 'In connection with a business transfer or merger' }}</li>
                            <li>{{ $privacyPageContent['privacy_sharing_item_4'] ?? 'With your explicit consent' }}</li>
                        </ul>
                    </div>

                    <!-- Data Security -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_security_title'] ?? 'Data Security' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_security_description'] ?? 'We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet or electronic storage is 100% secure.' }}
                        </p>
                    </div>

                    <!-- Your Rights -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_rights_title'] ?? 'Your Rights' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_rights_description'] ?? 'You have the right to:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $privacyPageContent['privacy_rights_item_1'] ?? 'Access and receive a copy of your personal data' }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_2'] ?? 'Request correction of inaccurate or incomplete data' }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_3'] ?? 'Request deletion of your personal data' }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_4'] ?? 'Object to processing of your personal data' }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_5'] ?? 'Request restriction of processing your personal data' }}</li>
                            <li>{{ $privacyPageContent['privacy_rights_item_6'] ?? 'Data portability' }}</li>
                        </ul>
                    </div>

                    <!-- Cookies -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_cookies_title'] ?? 'Cookies and Tracking Technologies' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_cookies_description'] ?? 'We use cookies and similar tracking technologies to track activity on our website and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.' }}
                        </p>
                    </div>

                    <!-- Changes to Privacy Policy -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_changes_title'] ?? 'Changes to This Privacy Policy' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_changes_description'] ?? 'We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date. You are advised to review this Privacy Policy periodically for any changes.' }}
                        </p>
                    </div>

                    <!-- Contact Us -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $privacyPageContent['privacy_contact_title'] ?? 'Contact Us' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $privacyPageContent['privacy_contact_description'] ?? 'If you have any questions about this Privacy Policy, please contact us at:' }}
                        </p>
                        <div class="space-y-2 text-muted-foreground">
                            <p><strong>Email:</strong> {{ $privacyPageContent['privacy_contact_email'] ?? 'privacy@bilskyen.dk' }}</p>
                            <p><strong>Address:</strong> {{ $privacyPageContent['privacy_contact_address'] ?? '123 Dealership Lane, Copenhagen, Denmark' }}</p>
                        </div>
                    </div>

                    <!-- Last Updated -->
                    <div class="border-t border-border pt-8">
                        <p class="text-sm text-muted-foreground">
                            {{ $privacyPageContent['privacy_last_updated'] ?? 'Last Updated: ' . date('F j, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
