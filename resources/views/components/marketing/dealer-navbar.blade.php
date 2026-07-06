@php
    $panelUrl = $panelUrl ?? config('payments.panel_url');
    $activeNav = $activeNav ?? '';
@endphp
<header class="bg-background w-full border-b border-border sticky top-0 z-40" id="marketing-navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('for-dealers.landing') }}" class="flex items-center space-x-2">
                    <img src="/images/logo.png" alt="{{ __('messages.common.site_name') }}" class="h-6 md:h-8">
                </a>
                <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                    <a href="{{ route('for-dealers.landing') }}" class="{{ $activeNav === 'home' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.dealer_marketing.nav.overview') }}</a>
                    <a href="{{ route('for-dealers.pricing') }}" class="{{ $activeNav === 'pricing' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.dealer_marketing.nav.pricing') }}</a>
                    <a href="{{ route('for-dealers.resources') }}" class="{{ $activeNav === 'resources' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.dealer_marketing.nav.resources') }}</a>
                    <a href="{{ route('for-dealers.contact') }}" class="{{ $activeNav === 'contact' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.dealer_marketing.nav.contact') }}</a>
                </nav>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                @include('components.language-switcher')
                <a href="{{ $panelUrl }}/auth/login" class="hidden sm:inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium text-foreground hover:bg-muted transition-colors">
                    {{ __('messages.dealer_marketing.nav.login') }}
                </a>
                <a href="{{ $panelUrl }}/auth/register" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                    {{ __('messages.dealer_marketing.nav.create_account') }}
                </a>
                <button id="dealer-mobile-menu-toggle" class="md:hidden p-1.5 rounded-md bg-muted transition-colors" aria-label="{{ __('messages.common.toggle_menu') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path id="dealer-menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path id="dealer-close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <nav id="dealer-mobile-menu" class="hidden md:hidden border-t border-border py-4">
            <div class="flex flex-col space-y-3">
                <a href="{{ route('for-dealers.landing') }}" class="text-sm font-medium py-2">{{ __('messages.dealer_marketing.nav.overview') }}</a>
                <a href="{{ route('for-dealers.pricing') }}" class="text-sm font-medium py-2">{{ __('messages.dealer_marketing.nav.pricing') }}</a>
                <a href="{{ route('for-dealers.resources') }}" class="text-sm font-medium py-2">{{ __('messages.dealer_marketing.nav.resources') }}</a>
                <a href="{{ route('for-dealers.contact') }}" class="text-sm font-medium py-2">{{ __('messages.dealer_marketing.nav.contact') }}</a>
                <a href="{{ $panelUrl }}/auth/login" class="text-sm font-medium py-2">{{ __('messages.dealer_marketing.nav.login') }}</a>
            </div>
        </nav>
    </div>
</header>
<script>
(function () {
    const toggle = document.getElementById('dealer-mobile-menu-toggle');
    const menu = document.getElementById('dealer-mobile-menu');
    const menuIcon = document.getElementById('dealer-menu-icon');
    const closeIcon = document.getElementById('dealer-close-icon');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function () {
        const isHidden = menu.classList.contains('hidden');
        menu.classList.toggle('hidden');
        menuIcon.classList.toggle('hidden', !isHidden);
        closeIcon.classList.toggle('hidden', isHidden);
    });
})();
</script>
