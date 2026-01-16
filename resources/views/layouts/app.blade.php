<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Revolutionizing Dealership Management with quality vehicles and exceptional customer service.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bilskyen | Revolutionizing Dealership Management')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
        :root {
            --radius: 0.5rem;
            --background: oklch(1 0 0);
            --foreground: oklch(0.145 0 0);
            --card: oklch(1 0 0);
            --card-foreground: oklch(0.145 0 0);
            --popover: oklch(1 0 0);
            --popover-foreground: oklch(0.145 0 0);
            --primary: #004aad;
            --primary-foreground: oklch(0.985 0 0);
            --secondary: oklch(0.97 0 0);
            --secondary-foreground: oklch(0.145 0 0);
            --muted: oklch(0.97 0 0);
            --muted-foreground: oklch(0.556 0 0);
            --accent: oklch(0.97 0 0);
            --accent-foreground: oklch(0.145 0 0);
            --destructive: oklch(0.577 0.245 27.325);
            --border: oklch(0.922 0 0);
            --input: oklch(0.922 0 0);
            --ring: oklch(0.708 0 0);
        }
        
        * {
            border-color: var(--border);
        }
        
        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: 'Sora', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }
        
        main {
        }
        
        .container {
            padding-inline: 1rem;
            margin-inline: auto;
            max-width: 1280px;
        }
        
        @media (min-width: 640px) {
            .container {
                padding-inline: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .container {
                padding-inline: 2rem;
            }
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
        
    </style>
    @stack('styles')
</head>
<body class="antialiased selection:bg-muted selection:text-muted-foreground">
    @if(!request()->is('auth/*') && !request()->is('dealer/*') && !request()->is('admin/*'))
        @include('components.navbar')
    @endif
    <main>
        @yield('content')
    </main>
    @if(!request()->is('auth/*') && !request()->is('dealer/*') && !request()->is('admin/*'))
        @include('components.footer')
    @endif
    
    <!-- Global Snackbar Notification System -->
    <script>
        // Snackbar notification system
        window.showSnackbar = function(message, type = 'success') {
            // Remove existing snackbar if any
            const existingSnackbar = document.getElementById('snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }

            const snackbar = document.createElement('div');
            snackbar.id = 'snackbar';
            snackbar.className = `fixed bottom-4 right-4 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg transition-all transform translate-y-0 opacity-100 ${
                type === 'success' 
                    ? 'bg-green-50 border-green-200 text-green-900'
                    : 'bg-red-50 border-red-200 text-red-900'
            }`;
            
            snackbar.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="flex-shrink-0">
                    ${type === 'success' 
                        ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'
                        : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'
                    }
                </svg>
                <span class="text-sm font-medium">${message}</span>
            `;
            
            document.body.appendChild(snackbar);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                snackbar.style.transform = 'translateY(100%)';
                snackbar.style.opacity = '0';
                setTimeout(() => snackbar.remove(), 300);
            }, 3000);
        };
    </script>
    
    @stack('scripts')
</body>
</html>

