<header class="bg-background/95 supports-[backdrop-filter]:bg-background/60 w-full border-b border-border backdrop-blur" id="navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center space-x-2">
                    <p class="text-[27px] font-bold">Bilskyen</p>
                </a>
            </div>
            <div class="flex items-center gap-4">
                <!-- Desktop Navigation -->
                <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                    <a href="/vehicles" class="hover:text-foreground/80 transition-colors">Vehicles</a>
                    <a href="/about" class="hover:text-foreground/80 transition-colors">About Us</a>
                    <a href="/contact" class="hover:text-foreground/80 transition-colors">Contact</a>
                </nav>
                <div class="flex items-center gap-2">
                    @include('components.theme-toggle')
                    @include('components.user-auth-status')
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-md hover:bg-muted transition-colors" aria-label="Toggle menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path id="menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            <path id="close-icon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Navigation Menu -->
        <nav id="mobile-menu" class="hidden md:hidden border-t border-border py-4">
            <div class="flex flex-col space-y-4">
                <a href="/vehicles" class="text-sm font-medium hover:text-foreground/80 transition-colors py-2">Vehicles</a>
                <a href="/about" class="text-sm font-medium hover:text-foreground/80 transition-colors py-2">About Us</a>
                <a href="/contact" class="text-sm font-medium hover:text-foreground/80 transition-colors py-2">Contact</a>
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
    })();
</script>

