<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seo['meta_title'] ?? 'Preview' }}</title>
    @include('layouts.partials.design-tokens')
    @include('layouts.partials.site-styles')
    <style>
        body { margin: 0; font-family: Inter, system-ui, -apple-system, sans-serif; }
        .cms-preview-banner {
            position: sticky; top: 0; z-index: 50;
            background: #03418b; color: #fff;
            font-size: 12px; text-align: center;
            padding: 6px 12px; letter-spacing: 0.04em;
        }
        .prose { line-height: 1.7; }
        .prose p { margin: 0.75em 0; }
        .prose h2 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5em; }
        .prose ul { list-style: disc; padding-left: 1.25rem; }
        .prose a { color: #03418b; text-decoration: underline; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="cms-preview-banner">PREVIEW — not published</div>
    @yield('content')
</body>
</html>
