<header class="bg-background/95 supports-[backdrop-filter]:bg-background/60 w-full border-b border-border backdrop-blur" id="navbar">
    <div class="container mx-auto px-4 md:px-6">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="/" class="flex items-center space-x-2">
                    <p class="text-[27px] font-bold">Bilskyen</p>
                </a>
            </div>
            <div class="flex items-center gap-4">
                <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                    <a href="/vehicles" class="hover:text-foreground/80 transition-colors">Vehicles</a>
                    <a href="/about" class="hover:text-foreground/80 transition-colors">About Us</a>
                    <a href="/contact" class="hover:text-foreground/80 transition-colors">Contact</a>
                </nav>
                <div class="flex items-center gap-2">
                    @include('components.theme-toggle')
                    @include('components.user-auth-status')
                </div>
            </div>
        </div>
    </div>
</header>

