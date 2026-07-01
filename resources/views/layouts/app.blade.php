<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/favicon.jpeg') }}">
    @isset($seo)
    <title>{{ $seo['meta_title'] ?? $seo['title'] ?? __('messages.layouts.default_title') }}</title>
    <meta name="description" content="{{ $seo['meta_description'] ?? __('messages.layouts.meta_description') }}">
    @if(!empty($seo['meta_keywords']))
    <meta name="keywords" content="{{ $seo['meta_keywords'] }}">
    @endif
    @if(!empty($seo['canonical_url']))
    <link rel="canonical" href="{{ $seo['canonical_url'] }}">
    @else
    <link rel="canonical" href="{{ url()->current() }}">
    @endif
    <meta name="robots" content="{{ $seo['robots'] ?? 'index, follow' }}">
    @php
        $defaultOgImage = asset('images/og-image.jpg');
        $ogImage = !empty($seo['og_image'])
            ? (str_starts_with($seo['og_image'], 'http') ? $seo['og_image'] : asset($seo['og_image']))
            : $defaultOgImage;
        $twitterImage = !empty($seo['twitter_image'])
            ? (str_starts_with($seo['twitter_image'], 'http') ? $seo['twitter_image'] : asset($seo['twitter_image']))
            : $ogImage;
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Bilskyen">
    @if(!empty($seo['og_title']))<meta property="og:title" content="{{ $seo['og_title'] }}">@else<meta property="og:title" content="{{ $seo['meta_title'] ?? $seo['title'] ?? __('messages.layouts.default_title') }}">@endif
    @if(!empty($seo['og_description']))<meta property="og:description" content="{{ $seo['og_description'] }}">@else<meta property="og:description" content="{{ $seo['meta_description'] ?? __('messages.layouts.meta_description') }}">@endif
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    @if(!empty($seo['twitter_title']))<meta name="twitter:title" content="{{ $seo['twitter_title'] }}">@else<meta name="twitter:title" content="{{ $seo['meta_title'] ?? $seo['title'] ?? __('messages.layouts.default_title') }}">@endif
    @if(!empty($seo['twitter_description']))<meta name="twitter:description" content="{{ $seo['twitter_description'] }}">@else<meta name="twitter:description" content="{{ $seo['meta_description'] ?? __('messages.layouts.meta_description') }}">@endif
    <meta name="twitter:image" content="{{ $twitterImage }}">
    @if(!empty($seo['schema_json']))
    <script type="application/ld+json">{!! is_array($seo['schema_json']) ? json_encode($seo['schema_json']) : $seo['schema_json'] !!}</script>
    @endif
    @else
    <title>@yield('title', __('messages.layouts.default_title'))</title>
    <meta name="description" content="{{ __('messages.layouts.meta_description') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Bilskyen">
    <meta property="og:title" content="@yield('title', __('messages.layouts.default_title'))">
    <meta property="og:description" content="{{ __('messages.layouts.meta_description') }}">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', __('messages.layouts.default_title'))">
    <meta name="twitter:description" content="{{ __('messages.layouts.meta_description') }}">
    <meta name="twitter:image" content="{{ asset('images/og-image.jpg') }}">
    @endisset
    <meta name="google-site-verification" content="UJCmMpdQRdTthyDk_rvdfCvGYIv7OETj5CYKgKtWoPc">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <main @if(request()->is('vehicles*') || request()->is('favorites*')) class="bg-muted" @endif>
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
            snackbar.className = `fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-lg border px-4 py-3 shadow-lg transition-all transform translate-y-0 opacity-100 ${
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
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                snackbar.style.transform = 'translateY(100%)';
                snackbar.style.opacity = '0';
                setTimeout(() => snackbar.remove(), 300);
            }, 5000);
        };
        
        // Enquiry Dialog Management
        window.openEnquiryDialog = function(type, vehicleId) {
            const dialogId = `${type}-dialog-${vehicleId}`;
            const dialog = document.getElementById(dialogId);
            if (dialog) {
                dialog.classList.remove('hidden');
                dialog.setAttribute('aria-hidden', 'false');
                // Prevent body scroll when dialog is open
                document.body.style.overflow = 'hidden';
                // Focus on first input
                const firstInput = dialog.querySelector('input[type="text"]');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }
        };
        
        window.closeEnquiryDialog = function(type, vehicleId) {
            const dialogId = `${type}-dialog-${vehicleId}`;
            const dialog = document.getElementById(dialogId);
            if (dialog) {
                dialog.classList.add('hidden');
                dialog.setAttribute('aria-hidden', 'true');
                // Restore body scroll
                document.body.style.overflow = '';
            }
        };
        
        // Close dialog on ESC key (handled per dialog in component)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openDialogs = document.querySelectorAll('[role="dialog"]:not(.hidden)');
                openDialogs.forEach(dialog => {
                    const dialogId = dialog.id;
                    const match = dialogId.match(/^(.+)-dialog-(\d+)$/);
                    if (match) {
                        const [, type, vehicleId] = match;
                        closeEnquiryDialog(type, parseInt(vehicleId));
                    }
                });
            }
        });
    </script>
    
    @stack('scripts')

    @if(!empty($cookieConsent['enabled']) && !empty($cookieConsent['text']))
    <div id="cookie-consent-banner" class="fixed bottom-0 left-0 right-0 z-50 bg-slate-900 text-white p-4 shadow-lg" style="display:none;">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm">{{ $cookieConsent['text'] }}</p>
            <button type="button" id="cookie-consent-accept" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-sm font-medium whitespace-nowrap">
                {{ __('messages.cms.cookie_accept') }}
            </button>
        </div>
    </div>
    <script>
        (function () {
            if (localStorage.getItem('cookie_consent_accepted')) return;
            var banner = document.getElementById('cookie-consent-banner');
            if (banner) banner.style.display = 'block';
            var btn = document.getElementById('cookie-consent-accept');
            if (btn) btn.addEventListener('click', function () {
                localStorage.setItem('cookie_consent_accepted', '1');
                banner.style.display = 'none';
            });
        })();
    </script>
    @endif
</body>
</html>

