<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/favicon.jpeg') }}">
    <meta name="description" content="{{ __('messages.layouts.meta_description') }}">
    <title>@yield('title', __('messages.layouts.default_title'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('layouts.partials.tailwind-config')
    @include('layouts.partials.design-tokens')
    @include('layouts.partials.site-styles')
    @include('layouts.partials.panel-blade-styles')
    @stack('styles')
</head>
<body class="site-page--auth antialiased selection:bg-accent selection:text-accent-foreground">
    <div class="site-auth-shell">
        <aside class="site-auth-brand">
            <div class="relative z-10">
                <a href="/" class="inline-flex items-center">
                    <img src="/images/logo_white.png" alt="{{ __('messages.layouts.logo_alt') }}" class="h-8 w-auto">
                </a>
            </div>
            <div class="relative z-10 max-w-md space-y-4">
                <p class="text-sm font-medium uppercase tracking-[0.18em] text-white/70">{{ __('messages.common.site_name') }}</p>
                <h1 class="text-4xl font-bold tracking-tight">{{ __('messages.layouts.auth_headline') }}</h1>
                <p class="text-base leading-relaxed text-white/80">{{ __('messages.layouts.auth_subheadline') }}</p>
            </div>
            <p class="relative z-10 text-sm text-white/60">© {{ date('Y') }} {{ __('messages.common.site_name') }}</p>
        </aside>

        <div class="site-auth-panel relative flex min-h-screen flex-col !p-0">
            <header class="shrink-0 border-b border-border bg-card/90 backdrop-blur-md">
                <div class="mx-auto flex h-16 w-full max-w-none items-center justify-between px-4 md:px-6">
                    <a href="/" class="inline-flex items-center lg:hidden">
                        <img src="/images/logo.png" alt="{{ __('messages.layouts.logo_alt') }}" class="h-7 w-auto">
                    </a>
                    <nav class="hidden items-center gap-6 text-sm font-medium md:flex md:ml-auto">
                        <a href="/vehicles" class="site-nav-link">{{ __('messages.navigation.vehicles') }}</a>
                        <a href="{{ route('blog.index') }}" class="site-nav-link">{{ __('messages.navigation.blog') }}</a>
                        <a href="/about" class="site-nav-link">{{ __('messages.navigation.about_us') }}</a>
                        <a href="/contact" class="site-nav-link">{{ __('messages.navigation.contact') }}</a>
                    </nav>
                    <div class="flex items-center gap-2 md:ml-4">
                        @include('components.language-switcher')
                        @include('components.user-auth-status')
                    </div>
                </div>
            </header>

            <main class="site-auth-main flex flex-1 items-center justify-center px-6 pb-12 pt-8 sm:px-10">
                <div class="site-auth-card">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>
