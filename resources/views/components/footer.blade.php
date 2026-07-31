<footer class="site-footer border-t border-white/10">
    <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
        <div class="flex flex-col items-start justify-between gap-10 lg:flex-row lg:items-center">
            <div class="max-w-xl space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--footer-muted)]">
                    {{ __('messages.common.site_name') }}
                </p>
                <h2 class="text-3xl font-bold tracking-tight text-[var(--footer-foreground)] md:text-4xl">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_title']) ? $homePageContent['footer_cta_title'] : __('messages.pages.footer.cta_title') }}
                </h2>
                <p class="text-base leading-relaxed text-[var(--footer-muted)]">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_description']) ? $homePageContent['footer_cta_description'] : __('messages.pages.footer.cta_description') }}
                </p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <a href="/vehicles" class="site-footer__cta-primary inline-flex h-11 items-center justify-center rounded-lg bg-white px-6 text-sm font-medium text-primary shadow-sm transition-all hover:bg-white/90 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                    {{ __('messages.pages.footer.browse_inventory') }}
                </a>
                <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-lg border border-white/15 bg-white/5 px-6 text-sm font-medium text-[var(--footer-foreground)] transition-all hover:bg-white/10 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40">
                    {{ __('messages.pages.footer.contact_us') }}
                </a>
            </div>
        </div>
        <div class="my-10 h-px w-full bg-white/10"></div>
    </div>

    <div class="container mx-auto px-4 md:px-6 pb-12">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4">
                <a href="/" class="inline-flex items-center">
                    <img src="/images/logo_white.png" alt="{{ __('messages.common.site_name') }}" class="h-8 w-auto">
                </a>
                <p class="text-sm leading-relaxed text-[var(--footer-muted)]">
                    {{ isset($homePageContent) && isset($homePageContent['footer_about_description']) ? $homePageContent['footer_about_description'] : __('messages.pages.footer.default_about_description') }}
                </p>
                <ul class="flex items-center gap-4">
                    @php
                        $socialLinks = [
                            'instagram' => isset($homePageContent) ? ($homePageContent['footer_social_instagram_url'] ?? '') : '',
                            'facebook' => isset($homePageContent) ? ($homePageContent['footer_social_facebook_url'] ?? '') : '',
                            'twitter' => isset($homePageContent) ? ($homePageContent['footer_social_twitter_url'] ?? '') : '',
                            'linkedin' => isset($homePageContent) ? ($homePageContent['footer_social_linkedin_url'] ?? '') : '',
                        ];
                    @endphp
                    @foreach ($socialLinks as $network => $url)
                    @if(! empty($url) && $url !== '#' && preg_match('/^https?:\/\//i', $url))
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-white/5 transition-colors hover:bg-white/10" aria-label="{{ __('messages.pages.footer.social_' . $network) }}" title="{{ __('messages.pages.footer.social_' . $network) }}">
                            @if($network === 'instagram')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line></svg>
                            @elseif($network === 'facebook')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            @elseif($network === 'twitter')
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path></svg>
                            @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect width="4" height="12" x="2" y="9"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                            @endif
                        </a>
                    </li>
                    @endif
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-sm font-semibold text-[var(--footer-foreground)]">{{ __('messages.pages.footer.vehicles') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="/vehicles">{{ __('messages.pages.footer.browse_vehicles') }}</a></li>
                    <li><a href="{{ route('cities.index') }}">{{ __('messages.pages.footer.cars_by_city') }}</a></li>
                    <li><a href="/vehicles">{{ __('messages.pages.footer.pre_owned') }}</a></li>
                    <li><a href="/vehicles">{{ __('messages.pages.footer.new_arrivals') }}</a></li>
                    @if(!empty($footerCities) && $footerCities->isNotEmpty())
                        @foreach($footerCities->take(4) as $city)
                            <li><a href="{{ route('cities.cars', $city->slug) }}">{{ __('messages.pages.cities.cars_heading', ['city' => $city->name]) }}</a></li>
                        @endforeach
                    @endif
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-sm font-semibold text-[var(--footer-foreground)]">{{ __('messages.pages.footer.pages') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('for-dealers.landing') }}">{{ __('messages.navigation.for_dealers') }}</a></li>
                    <li><a href="{{ route('for-staff.landing') }}">{{ __('messages.navigation.for_staff') }}</a></li>
                    <li><a href="{{ route('blog.index') }}">{{ __('messages.pages.footer.blog') }}</a></li>
                    <li><a href="/privacy-policy">{{ __('messages.pages.footer.privacy_policy') }}</a></li>
                    <li><a href="/terms-of-service">{{ __('messages.pages.footer.terms_of_service') }}</a></li>
                    @if(!empty($faqPageEnabled))
                    <li><a href="/faq">{{ __('messages.pages.footer.faq') }}</a></li>
                    @endif
                    <li><a href="/account-deletion">{{ __('messages.pages.footer.account_deletion') }}</a></li>
                    <li><a href="/contact">{{ __('messages.pages.footer.contact_us') }}</a></li>
                    <li><a href="/about">{{ __('messages.pages.footer.about_us') }}</a></li>
                </ul>
            </div>

            <div>
                <h3 class="mb-4 text-sm font-semibold text-[var(--footer-foreground)]">{{ __('messages.pages.footer.contact_us') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="mailto:{{ isset($homePageContent) && isset($homePageContent['footer_contact_email']) ? $homePageContent['footer_contact_email'] : 'info@bilskyen.dk' }}">{{ isset($homePageContent) && isset($homePageContent['footer_contact_email']) ? $homePageContent['footer_contact_email'] : 'info@bilskyen.dk' }}</a></li>
                    <li><a href="tel:{{ isset($homePageContent) && isset($homePageContent['footer_contact_phone']) ? $homePageContent['footer_contact_phone'] : '+45 12 34 56 78' }}">{{ isset($homePageContent) && isset($homePageContent['footer_contact_phone']) ? $homePageContent['footer_contact_phone'] : '+45 12 34 56 78' }}</a></li>
                    <li class="text-[var(--footer-muted)]">{{ __('messages.pages.footer.address') }}: {{ isset($homePageContent) && isset($homePageContent['footer_about_address']) ? $homePageContent['footer_about_address'] : '123 Dealership Lane, Copenhagen, Denmark' }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 md:flex-row">
            <p class="text-xs text-[var(--footer-muted)]">
                © {{ date('Y') }} {{ __('messages.common.site_name') }}. {{ __('messages.pages.footer.all_rights_reserved') }}.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 md:justify-end">
                <a href="{{ route('for-dealers.landing') }}" class="text-xs">{{ __('messages.navigation.for_dealers') }}</a>
                <a href="{{ route('for-staff.landing') }}" class="text-xs">{{ __('messages.navigation.for_staff') }}</a>
                <a href="/privacy-policy" class="text-xs">{{ __('messages.pages.footer.privacy_policy') }}</a>
                <a href="/terms-of-service" class="text-xs">{{ __('messages.pages.footer.terms_of_service') }}</a>
                <a href="/account-deletion" class="text-xs">{{ __('messages.pages.footer.account_deletion') }}</a>
            </div>
        </div>
    </div>
</footer>
