<header class="site-header site-header--primary bg-primary" id="navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="/" class="flex shrink-0 items-center">
                    <img src="/images/logo_white.png" alt="{{ __('messages.common.site_name') }}" class="h-7 md:h-8 w-auto">
                </a>
                <nav class="hidden items-center gap-6 md:flex" aria-label="Main navigation">
                    <a href="/vehicles" class="site-nav-link {{ request()->is('vehicles*') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.vehicles') }}</a>
                    <a href="/about" class="site-nav-link {{ request()->is('about') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.about_us') }}</a>
                    <a href="/contact" class="site-nav-link {{ request()->is('contact') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.contact') }}</a>
                    @if(isset($hasSellerRole) && $hasSellerRole && isset($sellerToken) && $sellerToken)
                    <a href="{{ route('seller.dashboard', ['token' => $sellerToken]) }}" class="site-nav-link {{ request()->is('seller-dashboard*') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.my_listings') }}</a>
                    @endif
                </nav>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                @include('components.language-switcher', ['variant' => 'dark'])
                @include('components.marketplace-notifications')
                @include('components.user-auth-status')
                <button id="mobile-menu-toggle" type="button" class="site-header-menu-toggle inline-flex h-9 w-9 items-center justify-center rounded-lg border shadow-sm transition-colors md:hidden" aria-label="{{ __('messages.common.toggle_menu') }}" aria-expanded="false" aria-controls="mobile-menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        <path id="close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <nav id="mobile-menu" class="hidden border-t border-primary-foreground/15 py-4 md:hidden" aria-label="Mobile navigation">
            <div class="flex flex-col gap-1">
                <a href="/vehicles" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.vehicles') }}</a>
                <a href="/about" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.about_us') }}</a>
                <a href="/contact" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.contact') }}</a>
                @if(isset($hasSellerRole) && $hasSellerRole && isset($sellerToken) && $sellerToken)
                <a href="{{ route('seller.dashboard', ['token' => $sellerToken]) }}" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.my_listings') }}</a>
                @endif
            </div>
        </nav>
    </div>
</header>

<script>
    (function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        function closeLanguageSwitcher() {
            document.querySelectorAll('details[data-language-switcher]').forEach(function(el) {
                el.removeAttribute('open');
            });
        }

        function closeNotificationPanel() {
            const notificationPanel = document.getElementById('notification-panel');
            if (notificationPanel) {
                notificationPanel.classList.add('hidden');
            }
        }

        document.querySelectorAll('details[data-language-switcher]').forEach(function(details) {
            details.addEventListener('toggle', function() {
                if (details.open) {
                    closeNotificationPanel();
                }
            });
        });

        const notificationBell = document.getElementById('notification-bell');
        if (notificationBell) {
            notificationBell.addEventListener('click', function() {
                closeLanguageSwitcher();
            }, true);
        }

        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', function() {
                const isHidden = mobileMenu.classList.contains('hidden');

                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    menuIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                    mobileMenuToggle.setAttribute('aria-expanded', 'true');
                } else {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        window.updateNavbarAuthStatus = async function(userData) {
            const navbar = document.getElementById('navbar');
            if (!navbar) {
                window.location.reload();
                return;
            }

            const authContainer = navbar.querySelector('.flex.items-center.gap-2, .flex.items-center.gap-3');
            if (!authContainer) {
                window.location.reload();
                return;
            }

            if (!userData) {
                try {
                    const response = await fetch('/api/v1/auth/me', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (response.ok) {
                        const result = await response.json();
                        userData = result.data || result.user || result;
                    } else {
                        window.location.reload();
                        return;
                    }
                } catch (error) {
                    window.location.reload();
                    return;
                }
            }

            if (!userData || !userData.name) {
                window.location.reload();
                return;
            }

            const name = userData.name || '';
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'U';
            const email = userData.email || '';
            const roles = userData.roles || [];
            const hasAdminRole = roles.some(r => (r.name || r) === 'admin');
            const hasDealerRole = roles.some(r => (r.name || r) === 'dealer');
            const showPanelButton = hasAdminRole || hasDealerRole;
            const panelButtonText = hasAdminRole
                ? "{{ __('messages.navigation.admin_panel') }}"
                : "{{ __('messages.navigation.dealer_panel') }}";
            const panelUrl = @json(rtrim((string) config('payments.panel_url'), '/'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            authContainer.innerHTML = `
                <div class="flex items-center gap-2 md:gap-3">
                    <a href="/sell-your-car">
                        <button type="button" class="panel-btn panel-btn--outline panel-btn--sm h-9 md:h-10">
                            <span class="hidden sm:inline">{{ __('messages.navigation.sell_your_car') }}</span>
                            <span class="sm:hidden">{{ __('messages.navigation.sell') }}</span>
                        </button>
                    </a>
                </div>
                <div class="relative">
                    <button id="user-menu-toggle" type="button" class="nav-user-avatar-btn" aria-label="{{ __('messages.common.user_menu') }}" aria-haspopup="true" aria-expanded="false">
                        <span class="nav-user-avatar">${initials}</span>
                    </button>
                    <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 rounded-[var(--radius-lg)] border border-border bg-popover p-1 text-popover-foreground shadow-card-hover z-50">
                        <div class="px-2 py-1.5">
                            <p class="text-sm font-medium leading-none">${name}</p>
                            <p class="mt-1 text-xs text-muted-foreground">${email}</p>
                        </div>
                        <div class="my-1 h-px bg-border"></div>
                        <a href="/profile" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            {{ __('messages.navigation.profile') }}
                        </a>
                        <a href="/favorites" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            {{ __('messages.navigation.my_favorites') }}
                        </a>
                        ${showPanelButton ? `
                        <a href="${panelUrl}" target="_blank" rel="noopener noreferrer" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            ${panelButtonText}
                        </a>
                        ` : ''}
                        <div class="my-1 h-px bg-border"></div>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                {{ __('messages.navigation.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            `;

            setTimeout(() => {
                const userMenuToggle = document.getElementById('user-menu-toggle');
                const userMenu = document.getElementById('user-menu');

                if (userMenuToggle && userMenu) {
                    userMenuToggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = !userMenu.classList.contains('hidden');
                        userMenu.classList.toggle('hidden');
                        userMenuToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                    });

                    document.addEventListener('click', (e) => {
                        if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                            userMenu.classList.add('hidden');
                            userMenuToggle.setAttribute('aria-expanded', 'false');
                        }
                    });
                }
            }, 100);
        };
    })();
</script>
