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
    @php $seoIndexingEnabled = app()->environment('production'); @endphp
    <meta name="robots" content="{{ $seoIndexingEnabled ? ($seo['robots'] ?? 'index, follow') : 'noindex, nofollow' }}">
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
    @if($seoIndexingEnabled && !empty($seo['schema_json']))
    <script type="application/ld+json">{!! is_array($seo['schema_json']) ? json_encode($seo['schema_json']) : $seo['schema_json'] !!}</script>
    @endif
    @if($seoIndexingEnabled && !empty($seo['breadcrumbs_json']))
    <script type="application/ld+json">{!! is_array($seo['breadcrumbs_json']) ? json_encode($seo['breadcrumbs_json']) : $seo['breadcrumbs_json'] !!}</script>
    @endif
    @else
    @php $seoIndexingEnabled = app()->environment('production'); @endphp
    <title>@yield('title', __('messages.layouts.default_title'))</title>
    <meta name="description" content="{{ __('messages.layouts.meta_description') }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="{{ $seoIndexingEnabled ? 'index, follow' : 'noindex, nofollow' }}">
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
    @php $seoIndexingEnabled = $seoIndexingEnabled ?? app()->environment('production'); @endphp
    @if($seoIndexingEnabled)
    <script type="application/ld+json">{!! json_encode(app(\App\Services\Seo\SchemaBuilderService::class)->sitewideGraph()) !!}</script>
    @endif
    @if(app()->environment('production'))
    <meta name="google-site-verification" content="UJCmMpdQRdTthyDk_rvdfCvGYIv7OETj5CYKgKtWoPc">
    @endif
    @php
        $facebookDomainVerification = app(\App\Services\Marketing\MetaConversionsApiService::class)->domainVerificationCode();
    @endphp
    @if($facebookDomainVerification !== '')
    <meta name="facebook-domain-verification" content="{{ $facebookDomainVerification }}">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.bot-protection-scripts')
    @include('layouts.partials.meta-pixel')
    @include('layouts.partials.google-ads')
    @include('layouts.partials.design-tokens')
    @include('layouts.partials.site-styles')
    @include('layouts.partials.panel-blade-styles')
    @stack('styles')
    @stack('head')
</head>
<body class="antialiased selection:bg-accent selection:text-accent-foreground">
    @if(!request()->is('auth/*') && !request()->is('dealer/*') && !request()->is('admin/*'))
        @include($navComponent ?? 'components.navbar')
    @endif
    <main @if(request()->is('biler') || request()->is('biler/*') || request()->is('favoritter*')) class="bg-muted" @endif>
        @yield('content')
    </main>
    @if(!request()->is('auth/*') && !request()->is('dealer/*') && !request()->is('admin/*'))
        @include($footerComponent ?? 'components.footer')
    @endif

    <script>
        window.showSnackbar = function(message, type = 'success') {
            const existingSnackbar = document.getElementById('snackbar');
            if (existingSnackbar) {
                existingSnackbar.remove();
            }

            const snackbar = document.createElement('div');
            snackbar.id = 'snackbar';
            snackbar.className = `site-snackbar ${type === 'success' ? 'site-snackbar--success' : 'site-snackbar--error'}`;
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

            setTimeout(() => {
                snackbar.style.opacity = '0';
                snackbar.style.transform = 'translateX(-50%) translateY(12px)';
                setTimeout(() => snackbar.remove(), 300);
            }, 5000);
        };

        window.openEnquiryDialog = function(type, vehicleId) {
            let dialog = document.getElementById(`${type}-dialog-${vehicleId}`);
            if (!dialog) {
                dialog = document.getElementById(`${type}-dialog-shared`);
                if (dialog) {
                    const template = dialog.getAttribute('data-endpoint-template') || '';
                    const form = dialog.querySelector('form');
                    if (form) {
                        form.dataset.endpoint = template.replace('__SLUG__', encodeURIComponent(vehicleId));
                    }
                    const hidden = dialog.querySelector('input[name="vehicle_id"]');
                    if (hidden) hidden.value = vehicleId;
                }
            }
            if (dialog) {
                dialog.classList.remove('hidden');
                dialog.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
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
                document.body.style.overflow = '';
            }
        };

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

    @if(!empty($publicAiEnabled))
    @include('components.ai-search-helpers')
    @endif

    @stack('scripts')

    @if(!empty($cookieConsent['enabled']) && !empty($cookieConsent['text']))
    <div id="cookie-consent-banner" class="fixed bottom-0 left-0 right-0 z-50 border-t border-border bg-card/95 p-4 shadow-nav backdrop-blur-md" style="display:none;">
        <div class="container mx-auto flex flex-col items-center justify-between gap-4 sm:flex-row">
            <p class="text-sm text-muted-foreground">{{ $cookieConsent['text'] }}</p>
            <button type="button" id="cookie-consent-accept" class="inline-flex h-10 items-center justify-center rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary-hover">
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
