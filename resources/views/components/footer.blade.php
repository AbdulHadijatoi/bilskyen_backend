<footer class="bg-primary border-t border-primary-foreground/20 py-8 md:py-12">
    <!-- CTA Section -->

    <div class="container mx-auto px-4 md:px-6">
        <div class="flex flex-col items-center justify-between gap-8 lg:flex-row text-white">
            <div class="max-w-xl space-y-4">
                <h2 class="text-3xl font-bold tracking-tight text-white">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_title']) ? $homePageContent['footer_cta_title'] : 'Ready to Find Your Next Vehicle?' }}
                </h2>
                <p class="text-white/90">
                    {{ isset($homePageContent) && isset($homePageContent['footer_cta_description']) ? $homePageContent['footer_cta_description'] : 'Visit our showroom or browse our inventory online. Our team is ready to help you find the perfect vehicle that fits your needs and budget.' }}
                </p>
            </div>
            <div class="flex flex-col gap-4 sm:flex-row">
                <a href="/vehicles" class="inline-flex h-11 items-center justify-center rounded-md bg-primary px-8 text-sm font-medium text-white shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-white/20">
                    Browse Inventory
                </a>
                <a href="/contact" class="inline-flex h-11 items-center justify-center rounded-md bg-secondary px-8 text-sm font-medium text-primary shadow transition-colors hover:bg-secondary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Contact Us
                </a>
            </div>
        </div>
        <div class="my-8 border-b border-white/20 w-full"></div>
    </div>

    <div class="container mx-auto px-4 md:px-6">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4">
                <a href="/" class="flex items-center space-x-2">
                    <img src="/images/logo_white.png" alt="Bilskyen" class="h-8">
                </a>
                <p class="text-sm text-white">
                    {{ isset($homePageContent) && isset($homePageContent['footer_about_description']) ? $homePageContent['footer_about_description'] : 'Bilskyen - Driving trust and value with quality pre-owned vehicles for every journey.' }}
                </p>

                <ul class="flex items-center space-x-6 text-white">
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a href="#" aria-label="Instagram">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"></line>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a href="#" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a href="#" aria-label="Twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                                <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="font-medium duration-300 hover:text-white/80">
                        <a href="#" aria-label="LinkedIn">
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
                <h3 class="mb-4 text-sm font-medium text-white">Vehicles</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="/vehicles" class="text-white transition hover:text-white/80">Browse Vehicles</a></li>
                    <li><a href="/vehicles/certified-pre-owned" class="text-white transition hover:text-white/80">Certified Pre-Owned</a></li>
                    <li><a href="/vehicles/new-arrivals" class="text-white transition hover:text-white/80">New Arrivals</a></li>
                    <li><a href="/vehicles/special-offers" class="text-white transition hover:text-white/80">Special Offers</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="mb-4 text-sm font-medium text-white">Services</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="/services/vehicle-inspection" class="text-white transition hover:text-white/80">Vehicle Inspection</a></li>
                    <li><a href="/services/maintenance" class="text-white transition hover:text-white/80">Maintenance</a></li>
                    <li><a href="/services/warranty" class="text-white transition hover:text-white/80">Warranty Services</a></li>
                    <li><a href="/about" class="text-white transition hover:text-white/80">About Us</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="mb-4 text-sm font-medium text-white">Contact Us</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:info@bilskyen.dk" class="text-white transition hover:text-white/80">info@bilskyen.dk</a></li>
                    <li><a href="tel:+4512345678" class="text-white transition hover:text-white/80">+45 12 34 56 78</a></li>
                    <li class="text-white">Address: 123 Dealership Lane, Copenhagen, Denmark</li>
                </ul>
            </div>
        </div>
        <div class="mt-8 flex flex-col items-center justify-between gap-4 border-t border-white/20 pt-8 md:flex-row">
            <p class="text-xs text-white">
                © {{ date('Y') }} Bilskyen. All rights reserved.
            </p>
            <div class="flex items-center space-x-4">
                <a href="/privacy" class="text-xs text-white transition hover:text-white/80">Privacy Policy</a>
                <a href="/terms" class="text-xs text-white transition hover:text-white/80">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

