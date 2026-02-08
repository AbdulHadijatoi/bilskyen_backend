@extends('layouts.app')

@section('title', 'Terms of Service | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ $termsPageContent['terms_header_title'] ?? 'Terms of Service' }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                {{ $termsPageContent['terms_header_description'] ?? 'Please read these terms and conditions carefully before using our website and services.' }}
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
                            {{ $termsPageContent['terms_agreement_title'] ?? 'Agreement to Terms' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_agreement_description'] ?? 'By accessing or using Bilskyen\'s website and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.' }}
                        </p>
                    </div>

                    <!-- Use License -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_license_title'] ?? 'Use License' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_license_description'] ?? 'Permission is granted to temporarily download one copy of the materials on Bilskyen\'s website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_license_item_1'] ?? 'Modify or copy the materials' }}</li>
                            <li>{{ $termsPageContent['terms_license_item_2'] ?? 'Use the materials for any commercial purpose or for any public display' }}</li>
                            <li>{{ $termsPageContent['terms_license_item_3'] ?? 'Attempt to reverse engineer any software contained on the website' }}</li>
                            <li>{{ $termsPageContent['terms_license_item_4'] ?? 'Remove any copyright or other proprietary notations from the materials' }}</li>
                            <li>{{ $termsPageContent['terms_license_item_5'] ?? 'Transfer the materials to another person or "mirror" the materials on any other server' }}</li>
                        </ul>
                    </div>

                    <!-- User Accounts -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_accounts_title'] ?? 'User Accounts' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_accounts_description'] ?? 'When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for all activities that occur under your account.' }}
                        </p>
                    </div>

                    <!-- Vehicle Listings -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_listings_title'] ?? 'Vehicle Listings' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_listings_description'] ?? 'All vehicle listings on our platform must be accurate and truthful. Sellers are responsible for:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_listings_item_1'] ?? 'Providing accurate information about vehicles' }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_2'] ?? 'Ensuring all vehicles are legally owned and can be sold' }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_3'] ?? 'Maintaining the accuracy of listing information' }}</li>
                            <li>{{ $termsPageContent['terms_listings_item_4'] ?? 'Responding to inquiries in a timely manner' }}</li>
                        </ul>
                    </div>

                    <!-- Prohibited Uses -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_prohibited_title'] ?? 'Prohibited Uses' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_prohibited_description'] ?? 'You may not use our service:' }}
                        </p>
                        <ul class="list-disc space-y-2 pl-6 text-muted-foreground">
                            <li>{{ $termsPageContent['terms_prohibited_item_1'] ?? 'For any unlawful purpose or to solicit others to perform unlawful acts' }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_2'] ?? 'To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances' }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_3'] ?? 'To infringe upon or violate our intellectual property rights or the intellectual property rights of others' }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_4'] ?? 'To harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate' }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_5'] ?? 'To submit false or misleading information' }}</li>
                            <li>{{ $termsPageContent['terms_prohibited_item_6'] ?? 'To upload or transmit viruses or any other type of malicious code' }}</li>
                        </ul>
                    </div>

                    <!-- Disclaimer -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_disclaimer_title'] ?? 'Disclaimer' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_disclaimer_description'] ?? 'The materials on Bilskyen\'s website are provided on an \'as is\' basis. Bilskyen makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.' }}
                        </p>
                    </div>

                    <!-- Limitations -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_limitations_title'] ?? 'Limitations' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_limitations_description'] ?? 'In no event shall Bilskyen or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Bilskyen\'s website, even if Bilskyen or a Bilskyen authorized representative has been notified orally or in writing of the possibility of such damage.' }}
                        </p>
                    </div>

                    <!-- Accuracy of Materials -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_accuracy_title'] ?? 'Accuracy of Materials' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_accuracy_description'] ?? 'The materials appearing on Bilskyen\'s website could include technical, typographical, or photographic errors. Bilskyen does not warrant that any of the materials on its website are accurate, complete, or current. Bilskyen may make changes to the materials contained on its website at any time without notice.' }}
                        </p>
                    </div>

                    <!-- Links -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_links_title'] ?? 'Links' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_links_description'] ?? 'Bilskyen has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Bilskyen of the site. Use of any such linked website is at the user\'s own risk.' }}
                        </p>
                    </div>

                    <!-- Modifications -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_modifications_title'] ?? 'Modifications' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_modifications_description'] ?? 'Bilskyen may revise these terms of service for its website at any time without notice. By using this website you are agreeing to be bound by the then current version of these terms of service.' }}
                        </p>
                    </div>

                    <!-- Governing Law -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_governing_title'] ?? 'Governing Law' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_governing_description'] ?? 'These terms and conditions are governed by and construed in accordance with the laws of Denmark and you irrevocably submit to the exclusive jurisdiction of the courts in that location.' }}
                        </p>
                    </div>

                    <!-- Contact Us -->
                    <div class="space-y-4">
                        <h2 class="text-2xl font-bold tracking-tight">
                            {{ $termsPageContent['terms_contact_title'] ?? 'Contact Us' }}
                        </h2>
                        <p class="text-muted-foreground">
                            {{ $termsPageContent['terms_contact_description'] ?? 'If you have any questions about these Terms of Service, please contact us at:' }}
                        </p>
                        <div class="space-y-2 text-muted-foreground">
                            <p><strong>Email:</strong> {{ $termsPageContent['terms_contact_email'] ?? 'legal@bilskyen.dk' }}</p>
                            <p><strong>Address:</strong> {{ $termsPageContent['terms_contact_address'] ?? '123 Dealership Lane, Copenhagen, Denmark' }}</p>
                        </div>
                    </div>

                    <!-- Last Updated -->
                    <div class="border-t border-border pt-8">
                        <p class="text-sm text-muted-foreground">
                            {{ $termsPageContent['terms_last_updated'] ?? 'Last Updated: ' . date('F j, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
