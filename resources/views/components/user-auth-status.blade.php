@php
    // Variables are provided by ViewServiceProvider view composer:
    // $user, $initials, $showUserMenu
    $userName = $user?->name ?? '';
    $userEmail = $user?->email ?? '';
    $panelUrl = env('VUE_PANEL_URL', 'http://localhost:5173');
    
    // Check user roles using Spatie Permission
    $hasAdminRole = $user?->hasRole('admin') ?? false;
    $hasDealerRole = $user?->hasRole('dealer') ?? false;
    $showPanelButton = $hasAdminRole || $hasDealerRole;
    $panelButtonText = $hasAdminRole ? 'Admin Panel' : 'Dealer Panel';
@endphp

@if(!$showUserMenu)
    <div class="flex items-center gap-2">
        @if(!request()->is('auth/login'))
            <a href="/auth/login">
                <button class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" x2="3" y1="12" y2="12"></line>
                    </svg>
                    Login
                </button>
            </a>
        @endif

        <a href="/sell-your-car">
            <button class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                Sell Your Car
            </button>
        </a>
    </div>
@else
    <!-- User dropdown menu -->
    <div class="relative">
        <button id="user-menu-toggle" class="relative inline-flex h-9 w-9 items-center justify-center rounded-full transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="User menu">
            <div class="flex h-9 w-9 items-center justify-center rounded-md bg-muted">
                <span class="text-sm font-medium">{{ $initials }}</span>
            </div>
        </button>
        
        <!-- Dropdown Menu -->
        <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md z-50">
            <div class="px-2 py-1.5">
                <div class="flex flex-col space-y-1">
                    <p class="text-sm leading-none font-medium">
                        {{ $userName }}
                    </p>
                    <p class="text-muted-foreground text-xs leading-none" aria-label="User email">
                        {{ $userEmail }}
                    </p>
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
            @if($showPanelButton)
            <a href="{{ $panelUrl }}" target="_blank" rel="noopener noreferrer" class="flex w-full items-center rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                    <path d="M8 21V8a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v13"></path>
                </svg>
                {{ $panelButtonText }}
            </a>
            @endif
            <div class="my-1 h-px bg-border"></div>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
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
    
    <script>
    (function() {
        function initUserMenu() {
            const userMenuToggle = document.getElementById('user-menu-toggle');
            const userMenu = document.getElementById('user-menu');
            
            if (!userMenuToggle || !userMenu) {
                setTimeout(initUserMenu, 100);
                return;
            }
            
            // Toggle menu
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!userMenuToggle.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initUserMenu);
        } else {
            initUserMenu();
        }
    })();
    </script>
@endif

