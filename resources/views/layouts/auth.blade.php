<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ __('messages.layouts.meta_description') }}">
    <title>@yield('title', __('messages.layouts.default_title'))</title>
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
    </style>
</head>
<body class="antialiased selection:bg-muted selection:text-muted-foreground">
    <!-- Header for Auth Pages -->
    <header class="bg-primary absolute top-0 right-0 left-0 z-50 w-full border-b border-primary-foreground/20 sm:absolute">
        <div class="container mx-auto px-4 md:px-6">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-2">
                    <a href="/" class="flex items-center space-x-2">
                        <img src="/images/logo.png" alt="{{ __('messages.layouts.logo_alt') }}" class="h-8">
                    </a>
                </div>
                <div class="flex items-center gap-4">
                    <nav class="hidden items-center space-x-6 text-sm font-medium md:flex">
                        <a href="/vehicles" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">{{ __('messages.navigation.vehicles') }}</a>
                        <a href="/about" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">{{ __('messages.navigation.about_us') }}</a>
                        <a href="/contact" class="text-primary-foreground hover:text-primary-foreground/80 transition-colors">{{ __('messages.navigation.contact') }}</a>
                    </nav>
                    <div class="flex items-center gap-2">
                        @include('components.user-auth-status')
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="relative container flex flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0 min-h-screen">
        <!-- Left Sidebar with Testimonial -->
        <div class="bg-muted text-foreground relative hidden h-full flex-col border-r border-border p-10 lg:flex">
            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-foreground text-lg">
                        &ldquo;{{ __('messages.layouts.testimonial_quote') }}&rdquo;
                    </p>
                    <footer class="text-foreground text-sm">
                        Rahif
                    </footer>
                </blockquote>
            </div>
        </div>

        <!-- Right Content Area -->
        <div class="mx-auto h-full w-full py-[10vh] sm:max-w-[350px]">
            @yield('content')
        </div>
    </div>
    
    <!-- Footer for Auth Pages -->
    @include('components.footer')
</body>
</html>

