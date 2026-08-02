<header class="site-header site-header--primary bg-primary" id="navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="/" class="flex shrink-0 items-center">
                    <img src="/images/logo_white.png" alt="{{ __('messages.common.site_name') }}" class="h-7 md:h-8 w-auto">
                </a>
                <nav class="hidden items-center gap-6 md:flex" aria-label="Main navigation">
                    <a href="{{ route('vehicles') }}" class="site-nav-link {{ request()->is('biler') || request()->is('biler/*') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.vehicles') }}</a>
                    <a href="{{ route('find-perfect-car') }}" class="site-nav-link {{ request()->is('find-din-bil') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.find_perfect_car') }}</a>
                    <a href="{{ route('about') }}" class="site-nav-link {{ request()->is('om-os') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.about_us') }}</a>
                    <a href="{{ route('contact') }}" class="site-nav-link {{ request()->is('kontakt') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.contact') }}</a>
                    @if(isset($hasSellerRole) && $hasSellerRole && isset($sellerToken) && $sellerToken)
                    <a href="{{ route('seller.dashboard', ['token' => $sellerToken]) }}" class="site-nav-link {{ request()->is('seller-dashboard*') ? 'site-nav-link--active' : '' }}">{{ __('messages.navigation.my_listings') }}</a>
                    @endif
                </nav>
            </div>
            <div class="flex items-center gap-2 md:gap-3">
                <div class="navbar-ai-search relative hidden md:block" data-public-ai="{{ !empty($publicAiEnabled) ? '1' : '0' }}">
                    <form id="navbar-ai-search-form" class="flex items-center gap-1" role="search" aria-label="{{ __('messages.pages.home.navbar_search_aria') }}">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-muted-foreground">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                            <input
                                type="search"
                                id="navbar-search-input"
                                class="h-9 w-44 lg:w-56 rounded-lg border border-border bg-card pl-8 pr-2 text-sm text-foreground caret-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-white/40"
                                placeholder="{{ !empty($publicAiEnabled) ? __('messages.pages.home.navbar_search_placeholder_ai') : __('messages.pages.home.navbar_search_placeholder') }}"
                                autocomplete="off"
                                aria-label="{{ __('messages.pages.home.navbar_search_aria') }}"
                            >
                            @if(!empty($publicAiEnabled))
                            <div id="navbar-ai-suggest" class="ai-suggest-dropdown hidden" role="listbox"></div>
                            @endif
                        </div>
                        <button type="submit" class="navbar-ai-search-btn inline-flex h-9 items-center rounded-lg border border-primary-foreground/25 bg-primary-foreground/15 px-2.5 text-xs font-semibold text-primary-foreground hover:bg-primary-foreground/25">
                            {{ __('messages.common.search') }}
                        </button>
                    </form>
                </div>
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
                <form id="navbar-ai-search-form-mobile" class="mb-2 flex gap-2 px-1" role="search">
                    <input
                        type="search"
                        id="navbar-search-input-mobile"
                        class="h-10 flex-1 rounded-lg border border-border bg-card px-3 text-sm text-foreground caret-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-white/40"
                        placeholder="{{ !empty($publicAiEnabled) ? __('messages.pages.home.navbar_search_placeholder_ai') : __('messages.pages.home.navbar_search_placeholder') }}"
                        autocomplete="off"
                    >
                    <button type="submit" class="navbar-ai-search-btn inline-flex h-10 items-center rounded-lg bg-primary-foreground/15 px-3 text-xs font-semibold text-primary-foreground">
                        {{ __('messages.common.search') }}
                    </button>
                </form>
                <a href="{{ route('vehicles') }}" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.vehicles') }}</a>
                <a href="{{ route('find-perfect-car') }}" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.find_perfect_car') }}</a>
                <a href="{{ route('about') }}" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.about_us') }}</a>
                <a href="{{ route('contact') }}" class="site-header-mobile-link rounded-lg px-3 py-2.5 text-sm font-medium transition-colors">{{ __('messages.navigation.contact') }}</a>
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

            const sellYourCarUrl = @json(route('sell-your-car'));
            const profileUrl = @json(route('profile'));
            const favoritesUrl = @json(route('favorites'));

            authContainer.innerHTML = `
                <div class="flex items-center gap-2 md:gap-3">
                    <a href="${sellYourCarUrl}">
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
                        <a href="${profileUrl}" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            {{ __('messages.navigation.profile') }}
                        </a>
                        <a href="${favoritesUrl}" class="flex w-full items-center rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
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

    (function initNavbarAiSearch() {
        const publicAiEnabled = @json(!empty($publicAiEnabled));
        const vehiclesUrl = @json(route('vehicles'));

        function bindForm(formId, inputId, suggestId) {
            const form = document.getElementById(formId);
            const input = document.getElementById(inputId);
            if (!form || !input) return;

            if (publicAiEnabled && window.BilskyenAiSearch && suggestId) {
                window.BilskyenAiSearch.bindAutocomplete(
                    input,
                    document.getElementById(suggestId),
                    {
                        onExample: function (label) { input.value = label; },
                        onBrand: function (item) { input.value = item.name || ''; },
                        onModel: function (item) { input.value = item.name || ''; },
                    }
                );
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const q = input.value.trim();
                if (!q) {
                    window.location.href = vehiclesUrl;
                    return;
                }
                const btn = form.querySelector('button[type="submit"]');
                if (btn) btn.classList.add('is-loading');
                try {
                    if (publicAiEnabled && window.BilskyenAiSearch) {
                        await window.BilskyenAiSearch.navigateWithAiSearch(q);
                    } else {
                        window.location.href = vehiclesUrl + '?search=' + encodeURIComponent(q);
                    }
                } catch (err) {
                    window.location.href = vehiclesUrl + '?search=' + encodeURIComponent(q);
                }
            });
        }

        function boot() {
            bindForm('navbar-ai-search-form', 'navbar-search-input', publicAiEnabled ? 'navbar-ai-suggest' : null);
            bindForm('navbar-ai-search-form-mobile', 'navbar-search-input-mobile', null);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { setTimeout(boot, 0); });
        } else {
            setTimeout(boot, 0);
        }
    })();
</script>
