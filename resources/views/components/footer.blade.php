<footer class="bg-primary border-t border-primary-foreground/20 py-8 md:py-12">
    <!-- CTA Section -->

    <div class="container mx-auto px-4 md:px-6">
        <div class="flex flex-col items-center justify-between gap-8 lg:flex-row text-white">
            <div class="max-w-xl space-y-4">
                <h2 class="text-3xl font-bold tracking-tight text-white">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_title']) ? $homePageContent['footer_cta_title'] : __('messages.pages.footer.cta_title') }}
                </h2>
                <p class="text-white/90">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_description']) ? $homePageContent['footer_cta_description'] : __('messages.pages.footer.cta_description') }}
                </p>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row">
                <a href="/vehicles" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-white shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-white/20">
                    {{ __('messages.pages.footer.browse_inventory') }}
                </a>
                <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-md bg-secondary px-8 text-sm font-medium text-primary shadow transition-colors hover:bg-secondary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    {{ __('messages.pages.footer.contact_us') }}
                </a>
            </div>
        </div>
        <div class="my-8 border-b border-white/20 w-full"></div>
    </div>

    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4">
                <a href="/" class="flex items-center space-x-2">
                    <img src="/images/logo_white.png" alt="{{ __('messages.common.site_name') }}" class="h-8">
                </a>
                <p class="text-sm text-white">
                    {{ isset($homePageContent) && isset($homePageContent['footer_about_description']) ? $homePageContent['footer_about_description'] : __('messages.pages.footer.default_about_description') }}
                </p>

                <ul class="flex items-center space-x-6 text-white">
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a
                            href="{{ isset($homePageContent) && isset($homePageContent['footer_social_instagram_url']) && !empty($homePageContent['footer_social_instagram_url']) ? $homePageContent['footer_social_instagram_url'] : '#' }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('messages.pages.footer.social_instagram') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a
                            href="{{ isset($homePageContent) && isset($homePageContent['footer_social_facebook_url']) && !empty($homePageContent['footer_social_facebook_url']) ? $homePageContent['footer_social_facebook_url'] : '#' }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('messages.pages.footer.social_facebook') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a
                            href="{{ isset($homePageContent) && isset($homePageContent['footer_social_twitter_url']) && !empty($homePageContent['footer_social_twitter_url']) ? $homePageContent['footer_social_twitter_url'] : '#' }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('messages.pages.footer.social_twitter') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a
                            href="{{ isset($homePageContent) && isset($homePageContent['footer_social_linkedin_url']) && !empty($homePageContent['footer_social_linkedin_url']) ? $homePageContent['footer_social_linkedin_url'] : '#' }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ __('messages.pages.footer.social_linkedin') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                                <rect width="4" height="12" x="2" y="9"></rect>
                                <circle cx="4" cy="4" r="2"></circle>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div>
                <h3 class="mb-4 text-sm font-medium text-white">{{ __('messages.pages.footer.vehicles') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="/vehicles" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.browse_vehicles') }}</a></li>
                    <li><a href="/vehicles" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.pre_owned') }}</a></li>
                    <li><a href="/vehicles" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.new_arrivals') }}</a></li>
                    <li><a href="/vehicles" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.special_offers') }}</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="mb-4 text-sm font-medium text-white">{{ __('messages.pages.footer.pages') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="/privacy-policy" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.privacy_policy') }}</a></li>
                    <li><a href="/terms-of-service" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.terms_of_service') }}</a></li>
                    <li><a href="/account-deletion" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.account_deletion') }}</a></li>
                    <li><a href="/contact" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.contact_us') }}</a></li>
                    <li><a href="/about" class="text-white transition hover:text-white/80">{{ __('messages.pages.footer.about_us') }}</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="mb-4 text-sm font-medium text-white">{{ __('messages.pages.footer.contact_us') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:{{ isset($homePageContent) && isset($homePageContent['footer_contact_email']) ? $homePageContent['footer_contact_email'] : 'info@bilskyen.dk' }}" class="text-white transition hover:text-white/80">{{ isset($homePageContent) && isset($homePageContent['footer_contact_email']) ? $homePageContent['footer_contact_email'] : 'info@bilskyen.dk' }}</a></li>
                    <li><a href="tel:{{ isset($homePageContent) && isset($homePageContent['footer_contact_phone']) ? $homePageContent['footer_contact_phone'] : '+45 12 34 56 78' }}" class="text-white transition hover:text-white/80">{{ isset($homePageContent) && isset($homePageContent['footer_contact_phone']) ? $homePageContent['footer_contact_phone'] : '+45 12 34 56 78' }}</a></li>
                    <li class="text-white">{{ __('messages.pages.footer.address') }}: {{ isset($homePageContent) && isset($homePageContent['footer_about_address']) ? $homePageContent['footer_about_address'] : '123 Dealership Lane, Copenhagen, Denmark' }}</li>
                </ul>
            </div>
        </div>
        <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-white/20 pt-8 md:flex-row">
            <p class="text-xs text-white">
                © {{ date('Y') }} {{ __('messages.common.site_name') }}. {{ __('messages.pages.footer.all_rights_reserved') }}.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 md:justify-end">
                <a href="/privacy-policy" class="text-xs text-white transition hover:text-white/80">{{ __('messages.pages.footer.privacy_policy') }}</a>
                <a href="/terms-of-service" class="text-xs text-white transition hover:text-white/80">{{ __('messages.pages.footer.terms_of_service') }}</a>
                <a href="/account-deletion" class="text-xs text-white transition hover:text-white/80">{{ __('messages.pages.footer.account_deletion') }}</a>
            </div>
        </div>
    </div>
</footer>

