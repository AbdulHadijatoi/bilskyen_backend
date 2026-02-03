<header class="bg-primary w-full border-b border-border" id="navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center space-x-2">
                    <img src="/images/logo_white.png" alt="Bilskyen" class="h-6 md:h-8">
                </a>
            </div>
            <div class="flex items-center gap-2 md:gap-4">
                <!-- Desktop Navigation -->
                <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                    <a href="/vehicles" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">Vehicles</a>
                    <a href="/about" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">About Us</a>
                    <a href="/contact" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">Contact</a>
                </nav>
                <div class="flex items-center gap-2">
                    @include('components.user-auth-status')
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-toggle" class="md:hidden p-1.5 rounded-md bg-muted transition-colors" aria-label="Toggle menu">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path id="close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Navigation Menu -->
        <nav id="mobile-menu" class="hidden md:hidden border-t border-primary-foreground/20 py-4">
            <div class="flex flex-col space-y-4">
                <a href="/vehicles" class="text-sm font-medium text-primary-foreground hover:text-primary-foreground/80 transition-colors py-2">Vehicles</a>
                <a href="/about" class="text-sm font-medium text-primary-foreground hover:text-primary-foreground/80 transition-colors py-2">About Us</a>
                <a href="/contact" class="text-sm font-medium text-primary-foreground hover:text-primary-foreground/80 transition-colors py-2">Contact</a>
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
        
        if (mobileMenuToggle && mobileMenu) {
            mobileMenuToggle.addEventListener('click', function() {
                const isHidden = mobileMenu.classList.contains('hidden');
                
                if (isHidden) {
                    mobileMenu.classList.remove('hidden');
                    menuIcon.classList.add('hidden');
                    closeIcon.classList.remove('hidden');
                } else {
                    mobileMenu.classList.add('hidden');
                    menuIcon.classList.remove('hidden');
                    closeIcon.classList.add('hidden');
                }
            });
        }
        
        // Function to update navbar auth status after login
        window.updateNavbarAuthStatus = async function(userData) {
            // Find the container that includes user-auth-status
            const navbar = document.getElementById('navbar');
            if (!navbar) {
                console.warn('Navbar not found');
                window.location.reload();
                return;
            }
            
            const authContainer = navbar.querySelector('.flex.items-center.gap-2');
            if (!authContainer) {
                console.warn('Auth container not found');
                window.location.reload();
                return;
            }
            
            // If userData is provided, use it; otherwise fetch from API
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
                        // If API call fails, reload page
                        window.location.reload();
                        return;
                    }
                } catch (error) {
                    console.error('Error fetching user data:', error);
                    window.location.reload();
                    return;
                }
            }
            
            if (!userData || !userData.name) {
                // No user data, reload page
                window.location.reload();
                return;
            }
            
            // Generate user initials
            const name = userData.name || '';
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) || 'U';
            const email = userData.email || '';
            
            // Check roles
            const roles = userData.roles || [];
            const hasAdminRole = roles.some(r => (r.name || r) === 'admin');
            const hasDealerRole = roles.some(r => (r.name || r) === 'dealer');
            const showPanelButton = hasAdminRole || hasDealerRole;
            const panelButtonText = hasAdminRole ? 'Admin Panel' : 'Dealer Panel';
            const panelUrl = '{{ env("VUE_PANEL_URL", "http://localhost:5173") }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            // Generate new HTML
            const newHTML = `
                <div class="flex items-center gap-2 md:gap-3">
                    <a href="/sell-your-car">
                        <button class="inline-flex h-7 md:h-10 items-center justify-center rounded-md border border-border bg-background px-2 md:px-4 text-xs md:text-sm font-medium text-foreground shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50">
                            <span class="hidden sm:inline">Sell Your Car</span>
                            <span class="sm:hidden">Sell</span>
                        </button>
                    </a>
                </div>
                <div class="relative">
                    <button id="user-menu-toggle" class="relative inline-flex h-7 w-7 md:h-9 md:w-9 items-center justify-center rounded-full transition-colors hover:bg-[#002d6b]/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="User menu">
                        <div class="flex h-7 w-7 md:h-9 md:w-9 items-center justify-center rounded-md bg-[#002d6b]">
                            <span class="text-xs md:text-sm font-medium text-white">${initials}</span>
                        </div>
                    </button>
                    
                    <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md z-50">
                        <div class="px-2 py-1.5">
                            <div class="flex flex-col space-y-1">
                                <p class="text-sm leading-none font-medium">${name}</p>
                                <p class="text-muted-foreground text-xs leading-none" aria-label="User email">${email}</p>
                            </div>
                        </div>
                        <div class="my-1 h-px bg-border"></div>
                        <a href="/profile" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Profile
                        </a>
                        <a href="/favorites" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                            </svg>
                            My Favorites
                        </a>
                        ${showPanelButton ? `
                        <a href="${panelUrl}" target="_blank" rel="noopener noreferrer" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                                <path d="M8 21V8a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v13"></path>
                            </svg>
                            ${panelButtonText}
                        </a>
                        ` : ''}
                        <div class="my-1 h-px bg-border"></div>
                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" x2="9" y1="12" y2="12"></line>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            `;
            
            // Replace the auth status section
            authContainer.innerHTML = newHTML;
            
            // Re-initialize user menu (from user-auth-status component logic)
            setTimeout(() => {
                const userMenuToggle = document.getElementById('user-menu-toggle');
                const userMenu = document.getElementById('user-menu');
                
                if (userMenuToggle && userMenu) {
                    // Remove existing listeners by cloning
                    const newToggle = userMenuToggle.cloneNode(true);
                    userMenuToggle.parentNode.replaceChild(newToggle, userMenuToggle);
                    
                    const newMenu = userMenu.cloneNode(true);
                    userMenu.parentNode.replaceChild(newMenu, userMenu);
                    
                    // Add event listeners
                    const toggle = document.getElementById('user-menu-toggle');
                    const menu = document.getElementById('user-menu');
                    
                    toggle.addEventListener('click', (e) => {
                        e.stopPropagation();
                        menu.classList.toggle('hidden');
                    });
                    
                    document.addEventListener('click', (e) => {
                        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                }
            }, 100);
        };
    })();
</script>

