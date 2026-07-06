@php
    $panelUrl = $panelUrl ?? config('payments.panel_url');
    $activeNav = $activeNav ?? '';
@endphp
<header class="bg-background w-full border-b border-border sticky top-0 z-40" id="marketing-navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('for-staff.landing') }}" class="flex items-center space-x-2">
                    <img src="/images/logo.png" alt="{{ __('messages.common.site_name') }}" class="h-6 md:h-8">
                </a>
                <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                    <a href="{{ route('for-staff.landing') }}" class="{{ $activeNav === 'home' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.staff_marketing.nav.overview') }}</a>
                    <a href="{{ route('for-staff.resources') }}" class="{{ $activeNav === 'resources' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.staff_marketing.nav.resources') }}</a>
                    <a href="{{ route('for-dealers.contact') }}" class="{{ $activeNav === 'contact' ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }} transition-colors">{{ __('messages.staff_marketing.nav.contact') }}</a>
                </nav>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                @include('components.language-switcher')
                <a href="{{ route('for-dealers.landing') }}" class="hidden lg:inline-flex h-9 items-center justify-center rounded-md px-3 text-sm font-medium text-foreground/70 hover:text-foreground transition-colors">
                    {{ __('messages.staff_marketing.nav.for_dealers') }}
                </a>
                <a href="{{ $panelUrl }}/auth/staff-login" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                    {{ __('messages.staff_marketing.nav.staff_login') }}
                </a>
                <button id="staff-mobile-menu-toggle" class="md:hidden p-1.5 rounded-md bg-muted transition-colors" aria-label="{{ __('messages.common.toggle_menu') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path id="staff-menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path id="staff-close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <nav id="staff-mobile-menu" class="hidden md:hidden border-t border-border py-4">
            <div class="flex flex-col space-y-3">
                <a href="{{ route('for-staff.landing') }}" class="text-sm font-medium py-2">{{ __('messages.staff_marketing.nav.overview') }}</a>
                <a href="{{ route('for-staff.resources') }}" class="text-sm font-medium py-2">{{ __('messages.staff_marketing.nav.resources') }}</a>
                <a href="{{ route('for-dealers.contact') }}" class="text-sm font-medium py-2">{{ __('messages.staff_marketing.nav.contact') }}</a>
                <a href="{{ $panelUrl }}/auth/staff-login" class="text-sm font-medium py-2">{{ __('messages.staff_marketing.nav.staff_login') }}</a>
            </div>
        </nav>
    </div>
</header>
<script>
(function () {
    const toggle = document.getElementById('staff-mobile-menu-toggle');
    const menu = document.getElementById('staff-mobile-menu');
    const menuIcon = document.getElementById('staff-menu-icon');
    const closeIcon = document.getElementById('staff-close-icon');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function () {
        const isHidden = menu.classList.contains('hidden');
        menu.classList.toggle('hidden');
        menuIcon.classList.toggle('hidden', !isHidden);
        closeIcon.classList.toggle('hidden', isHidden);
    });
})();
</script>
