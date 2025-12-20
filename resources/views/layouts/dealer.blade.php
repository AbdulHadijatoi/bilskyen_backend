<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Bilskyen Dealer Panel - Manage your dealership operations">
    <title>@yield('title', 'Dealer Panel - Bilskyen')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sora', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
                    },
                    colors: {
                        border: "var(--border)",
                        input: "var(--input)",
                        ring: "var(--ring)",
                        background: "var(--background)",
                        foreground: "var(--foreground)",
                        primary: {
                            DEFAULT: "var(--primary)",
                            foreground: "var(--primary-foreground)",
                        },
                        secondary: {
                            DEFAULT: "var(--secondary)",
                            foreground: "var(--secondary-foreground)",
                        },
                        destructive: {
                            DEFAULT: "var(--destructive)",
                            foreground: "var(--destructive-foreground)",
                        },
                        muted: {
                            DEFAULT: "var(--muted)",
                            foreground: "var(--muted-foreground)",
                        },
                        accent: {
                            DEFAULT: "var(--accent)",
                            foreground: "var(--accent-foreground)",
                        },
                        popover: {
                            DEFAULT: "var(--popover)",
                            foreground: "var(--popover-foreground)",
                        },
                        card: {
                            DEFAULT: "var(--card)",
                            foreground: "var(--card-foreground)",
                        },
                        sidebar: {
                            DEFAULT: "var(--sidebar)",
                            foreground: "var(--sidebar-foreground)",
                            primary: "var(--sidebar-primary)",
                            "primary-foreground": "var(--sidebar-primary-foreground)",
                            accent: "var(--sidebar-accent)",
                            "accent-foreground": "var(--sidebar-accent-foreground)",
                            border: "var(--sidebar-border)",
                            ring: "var(--sidebar-ring)",
                        },
                    },
                    borderRadius: {
                        lg: "var(--radius)",
                        md: "calc(var(--radius) - 2px)",
                        sm: "calc(var(--radius) - 4px)",
                    },
                },
            },
        }
    </script>
    <style>
        :root,
        .light {
            --radius: 0.5rem;
            --background: oklch(1 0 0);
            --foreground: oklch(0.145 0 0);
            --card: oklch(1 0 0);
            --card-foreground: oklch(0.145 0 0);
            --popover: oklch(1 0 0);
            --popover-foreground: oklch(0.145 0 0);
            --primary: oklch(0.205 0 0);
            --primary-foreground: oklch(0.985 0 0);
            --secondary: oklch(0.97 0 0);
            --secondary-foreground: oklch(0.205 0 0);
            --muted: oklch(0.97 0 0);
            --muted-foreground: oklch(0.556 0 0);
            --accent: oklch(0.97 0 0);
            --accent-foreground: oklch(0.205 0 0);
            --destructive: oklch(0.577 0.245 27.325);
            --border: oklch(0.922 0 0);
            --input: oklch(0.922 0 0);
            --ring: oklch(0.708 0 0);
            --sidebar: oklch(0.985 0 0);
            --sidebar-foreground: oklch(0.145 0 0);
            --sidebar-primary: oklch(0.205 0 0);
            --sidebar-primary-foreground: oklch(0.985 0 0);
            --sidebar-accent: oklch(0.97 0 0);
            --sidebar-accent-foreground: oklch(0.205 0 0);
            --sidebar-border: oklch(0.922 0 0);
            --sidebar-ring: oklch(0.708 0 0);
        }
        
        .dark {
            --background: oklch(0.145 0 0);
            --foreground: oklch(0.985 0 0);
            --card: oklch(0.205 0 0);
            --card-foreground: oklch(0.985 0 0);
            --popover: oklch(0.205 0 0);
            --popover-foreground: oklch(0.985 0 0);
            --primary: oklch(0.922 0 0);
            --primary-foreground: oklch(0.205 0 0);
            --secondary: oklch(0.269 0 0);
            --secondary-foreground: oklch(0.985 0 0);
            --muted: oklch(0.269 0 0);
            --muted-foreground: oklch(0.708 0 0);
            --accent: oklch(0.269 0 0);
            --accent-foreground: oklch(0.985 0 0);
            --destructive: oklch(0.704 0.191 22.216);
            --border: oklch(1 0 0 / 10%);
            --input: oklch(1 0 0 / 15%);
            --ring: oklch(0.556 0 0);
            --sidebar: oklch(0.205 0 0);
            --sidebar-foreground: oklch(0.985 0 0);
            --sidebar-primary: oklch(0.922 0 0);
            --sidebar-primary-foreground: oklch(0.205 0 0);
            --sidebar-accent: oklch(0.269 0 0);
            --sidebar-accent-foreground: oklch(0.985 0 0);
            --sidebar-border: oklch(1 0 0 / 10%);
            --sidebar-ring: oklch(0.556 0 0);
        }
        
        * {
            border-color: var(--border);
        }
        
        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: 'Sora', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
        
        /* Remove all focus outlines and add subtle background change instead */
        input:focus,
        input:focus-visible,
        textarea:focus,
        textarea:focus-visible,
        select:focus,
        select:focus-visible,
        button:focus,
        button:focus-visible,
        *:focus,
        *:focus-visible {
            outline: none !important;
            box-shadow: none !important;
            --tw-ring-shadow: none !important;
        }
        
        /* Remove ring utilities on focus */
        .focus\:ring-0:focus,
        .focus\:ring-1:focus,
        .focus\:ring-2:focus,
        .focus\:ring-4:focus,
        .focus-visible\:ring-0:focus-visible,
        .focus-visible\:ring-1:focus-visible,
        .focus-visible\:ring-2:focus-visible,
        .focus-visible\:ring-4:focus-visible {
            --tw-ring-shadow: none !important;
            box-shadow: none !important;
        }
        
        /* Input focus background change - light mode (slightly lighter than default input) */
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        input[type="tel"]:focus,
        input[type="url"]:focus,
        input[type="search"]:focus,
        input[type="date"]:focus,
        input[type="datetime-local"]:focus,
        input[type="month"]:focus,
        input[type="time"]:focus,
        input[type="week"]:focus,
        textarea:focus,
        select:focus {
            background-color: oklch(0.95 0 0);
            transition: background-color 0.15s ease-in-out;
        }
        
        /* Input focus background change - dark mode (slightly more opaque) */
        .dark input[type="text"]:focus,
        .dark input[type="email"]:focus,
        .dark input[type="password"]:focus,
        .dark input[type="number"]:focus,
        .dark input[type="tel"]:focus,
        .dark input[type="url"]:focus,
        .dark input[type="search"]:focus,
        .dark input[type="date"]:focus,
        .dark input[type="datetime-local"]:focus,
        .dark input[type="month"]:focus,
        .dark input[type="time"]:focus,
        .dark input[type="week"]:focus,
        .dark textarea:focus,
        .dark select:focus {
            background-color: oklch(1 0 0 / 0.2);
            transition: background-color 0.15s ease-in-out;
        }
    </style>
    <script>
        // Apply theme immediately in head to prevent flash
        (function() {
            function getTheme() {
                return localStorage.getItem('theme') || 'system';
            }
            
            function applyTheme(theme) {
                const root = document.documentElement;
                
                if (theme === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        root.classList.add('dark');
                    } else {
                        root.classList.remove('dark');
                    }
                } else if (theme === 'dark') {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            }
            
            // Apply theme immediately
            applyTheme(getTheme());
        })();
    </script>
    @stack('styles')
</head>
<body class="antialiased selection:bg-muted selection:text-muted-foreground overflow-hidden">
    <div class="flex h-screen w-full">
        <!-- Desktop Sidebar -->
        @include('components.dealer.sidebar')
        
        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-sidebar-overlay" class="hidden fixed inset-0 z-[60] bg-black/50 md:hidden"></div>
        
        <!-- Mobile Sidebar Drawer -->
        <aside id="mobile-sidebar" class="fixed inset-y-0 left-0 z-[70] w-72 flex flex-col bg-sidebar text-sidebar-foreground border-r border-sidebar-border transition-transform duration-300 ease-in-out -translate-x-full md:hidden">
            @include('components.dealer.sidebar', ['isMobile' => true])
        </aside>
        
        <!-- Sidebar Gap for Desktop -->
        <div id="sidebar-gap" class="hidden w-64 shrink-0 transition-all duration-300 md:block"></div>
        
        <!-- Main Content Area -->
        <div class="flex flex-1 flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-background sticky top-0 z-[55] flex shrink-0 items-center gap-2 border-b border-border px-4 py-2.5">
                <div class="flex w-full items-center gap-2">
                    <button id="sidebar-toggle" class="-ml-1 inline-flex h-9 w-9 items-center justify-center rounded-md transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <line x1="3" x2="21" y1="6" y2="6"></line>
                            <line x1="3" x2="21" y1="12" y2="12"></line>
                            <line x1="3" x2="21" y1="18" y2="18"></line>
                        </svg>
                    </button>
                    <button id="desktop-sidebar-toggle" class="-ml-1 hidden h-9 w-9 items-center justify-center rounded-md transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring md:inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                            <path d="M9 3v18"></path>
                        </svg>
                    </button>
                    <div class="h-4 w-px bg-border mr-2"></div>
                    @include('components.dealer.breadcrumb')
                    <div class="flex-grow"></div>
                    @include('components.theme-toggle')
                    @include('components.dealer.notifications-button')
                </div>
            </header>
            
            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto">
                <!-- Page Content -->
                <div class="p-6 pb-12">
                    @yield('content')
                </div>
                
                <!-- Footer -->
                @include('components.footer')
            </div>
        </div>
    </div>
    
    <script>
        // Theme initialization - must run first
        (function() {
            function getTheme() {
                return localStorage.getItem('theme') || 'system';
            }
            
            function applyTheme(theme) {
                const root = document.documentElement;
                
                if (theme === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    root.classList.toggle('dark', prefersDark);
                } else {
                    root.classList.toggle('dark', theme === 'dark');
                }
            }
            
            // Apply theme immediately on page load (before DOM is ready)
            applyTheme(getTheme());
            
            // Listen for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (getTheme() === 'system') {
                    applyTheme('system');
                }
            });
        })();
        
        // Sidebar toggle functionality
        (function() {
            const mobileSidebarToggle = document.getElementById('sidebar-toggle');
            const desktopSidebarToggle = document.getElementById('desktop-sidebar-toggle');
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const mobileOverlay = document.getElementById('mobile-sidebar-overlay');
            const desktopSidebar = document.getElementById('sidebar');
            const sidebarState = localStorage.getItem('sidebar_state') === 'true';
            
            // Desktop sidebar collapse/expand
            const sidebarGap = document.getElementById('sidebar-gap');
            
            if (desktopSidebar) {
                if (sidebarState) {
                    desktopSidebar.classList.remove('collapsed');
                    if (sidebarGap) sidebarGap.classList.remove('w-16');
                } else {
                    desktopSidebar.classList.add('collapsed');
                    if (sidebarGap) sidebarGap.classList.add('w-16');
                }
            }
            
            if (desktopSidebarToggle && desktopSidebar) {
                desktopSidebarToggle.addEventListener('click', () => {
                    desktopSidebar.classList.toggle('collapsed');
                    const isCollapsed = desktopSidebar.classList.contains('collapsed');
                    localStorage.setItem('sidebar_state', !isCollapsed);
                    if (sidebarGap) {
                        if (isCollapsed) {
                            sidebarGap.classList.add('w-16');
                        } else {
                            sidebarGap.classList.remove('w-16');
                        }
                    }
                });
            }
            
            // Mobile sidebar drawer
            function openMobileSidebar() {
                if (mobileSidebar) mobileSidebar.classList.remove('-translate-x-full');
                if (mobileOverlay) mobileOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            
            function closeMobileSidebar() {
                if (mobileSidebar) mobileSidebar.classList.add('-translate-x-full');
                if (mobileOverlay) mobileOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            if (mobileSidebarToggle) {
                mobileSidebarToggle.addEventListener('click', openMobileSidebar);
            }
            
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', closeMobileSidebar);
            }
            
            // Close mobile sidebar when clicking a link
            if (mobileSidebar) {
                mobileSidebar.addEventListener('click', (e) => {
                    if (e.target.tagName === 'A') {
                        closeMobileSidebar();
                    }
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
                    closeMobileSidebar();
                }
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>

