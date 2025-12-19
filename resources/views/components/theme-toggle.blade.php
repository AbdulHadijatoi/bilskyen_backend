<div class="relative">
    <button id="theme-toggle" type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-md transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" aria-label="Toggle theme">
        <svg id="sun-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-[1.2rem] w-[1.2rem] scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 2v2"></path>
            <path d="M12 20v2"></path>
            <path d="m4.93 4.93 1.41 1.41"></path>
            <path d="m17.66 17.66 1.41 1.41"></path>
            <path d="M2 12h2"></path>
            <path d="M20 12h2"></path>
            <path d="m6.34 17.66-1.41 1.41"></path>
            <path d="m19.07 4.93-1.41 1.41"></path>
        </svg>
        <svg id="moon-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute h-[1.2rem] w-[1.2rem] scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
        </svg>
        <span class="sr-only">Toggle theme</span>
    </button>
    
    <!-- Theme Dropdown -->
    <div id="theme-menu" class="hidden absolute right-0 mt-2 w-32 rounded-md border bg-popover p-1 text-popover-foreground shadow-md z-50">
        <button data-theme="light" class="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">Light</button>
        <button data-theme="dark" class="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">Dark</button>
        <button data-theme="system" class="w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground">System</button>
    </div>
</div>

<script>
(function() {
    function initThemeToggle() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeMenu = document.getElementById('theme-menu');
        
        if (!themeToggle || !themeMenu) {
            // If elements don't exist yet, try again after a short delay
            setTimeout(initThemeToggle, 100);
            return;
        }
        
        // Get theme from localStorage or default to 'system'
        function getTheme() {
            return localStorage.getItem('theme') || 'system';
        }
        
        // Set theme
        function setTheme(theme) {
            localStorage.setItem('theme', theme);
            applyTheme(theme);
        }
        
        // Apply theme
        function applyTheme(theme) {
            const root = document.documentElement;
            
            if (theme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                root.classList.toggle('dark', prefersDark);
            } else {
                root.classList.toggle('dark', theme === 'dark');
            }
        }
        
        // Initialize theme
        applyTheme(getTheme());
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (getTheme() === 'system') {
                applyTheme('system');
            }
        });
        
        // Toggle menu
        themeToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            themeMenu.classList.toggle('hidden');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!themeToggle.contains(e.target) && !themeMenu.contains(e.target)) {
                themeMenu.classList.add('hidden');
            }
        });
        
        // Handle theme selection
        themeMenu.querySelectorAll('button[data-theme]').forEach(button => {
            button.addEventListener('click', () => {
                const theme = button.getAttribute('data-theme');
                setTheme(theme);
                themeMenu.classList.add('hidden');
            });
        });
        
        // Update active state
        function updateActiveState() {
            const currentTheme = getTheme();
            themeMenu.querySelectorAll('button[data-theme]').forEach(button => {
                if (button.getAttribute('data-theme') === currentTheme) {
                    button.classList.add('bg-accent', 'text-accent-foreground');
                } else {
                    button.classList.remove('bg-accent', 'text-accent-foreground');
                }
            });
        }
        
        updateActiveState();
        setInterval(updateActiveState, 100);
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();
</script>

