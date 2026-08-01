@extends('layouts.app')

@section('title', __('messages.pages.privacy_policy.header_title') . ' | Bilskyen')

@section('content')
@php
    $privacyBody = trim($privacyPageContent['privacy_body'] ?? '');
    $contactEmail = __('messages.pages.privacy_policy.contact_email');
    $contactHref = 'mailto:' . $contactEmail;
@endphp
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-16 text-center md:py-20" aria-labelledby="privacy-policy-heading">
        <div class="container mx-auto px-4 md:px-6">
            <h1 id="privacy-policy-heading" class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ __('messages.pages.privacy_policy.header_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg leading-relaxed">
                {{ __('messages.pages.privacy_policy.header_description') }}
            </p>
        </div>
    </section>

    <section class="py-12 md:py-16" aria-labelledby="privacy-policy-content-heading">
        <div class="container mx-auto px-4 md:px-6">
            <!-- <div class="mx-auto max-w-5xl"> -->
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_15.5rem] lg:gap-10 xl:grid-cols-[minmax(0,1fr)_17.5rem]">
                    <article class="min-w-0 overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <header class="border-b border-border bg-muted/40 px-6 py-5 md:px-8 md:py-6">
                            <h2 id="privacy-policy-content-heading" class="sr-only">
                                {{ __('messages.pages.privacy_policy.header_title') }}
                            </h2>
                            @if($privacyLastUpdated)
                                <p class="text-sm font-medium text-foreground">
                                    {{ __('messages.pages.privacy_policy.last_updated', [
                                        'date' => $privacyLastUpdated->locale(app()->getLocale())->translatedFormat('j F Y'),
                                    ]) }}
                                </p>
                            @endif
                            <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                                {{ __('messages.pages.privacy_policy.intro_description') }}
                            </p>
                        </header>

                        <div class="px-6 py-8 md:px-10 md:py-10">
                            @if($privacyBody !== '')
                                <div id="privacy-policy-content" class="legal-prose">
                                    {!! $privacyBody !!}
                                </div>
                            @else
                                <p class="text-muted-foreground text-base leading-relaxed">
                                    {{ __('messages.pages.privacy_policy.empty_content') }}
                                </p>
                            @endif
                        </div>
                    </article>

                    <aside class="space-y-6 lg:sticky lg:top-8 lg:self-start">
                        <nav
                            id="privacy-policy-toc"
                            class="hidden rounded-lg border border-border bg-card p-5 shadow-sm md:p-6"
                            aria-labelledby="privacy-policy-toc-heading"
                        >
                            <h2 id="privacy-policy-toc-heading" class="text-sm font-semibold tracking-tight">
                                {{ __('messages.pages.privacy_policy.toc_title') }}
                            </h2>
                            <ol id="privacy-policy-toc-list" class="mt-4 space-y-2 text-sm"></ol>
                        </nav>

                        <div class="rounded-lg border border-border bg-card p-5 shadow-sm md:p-6">
                            <h2 class="text-sm font-semibold tracking-tight">
                                {{ __('messages.pages.privacy_policy.related_documents_title') }}
                            </h2>
                            <ul class="mt-4 space-y-2.5 text-sm">
                                <li>
                                    <a href="{{ route('terms-of-service') }}" class="text-primary font-medium underline-offset-2 transition hover:underline">
                                        {{ __('messages.pages.footer.terms_of_service') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('account-deletion') }}" class="text-primary font-medium underline-offset-2 transition hover:underline">
                                        {{ __('messages.pages.footer.account_deletion') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('contact') }}" class="text-primary font-medium underline-offset-2 transition hover:underline">
                                        {{ __('messages.pages.footer.contact') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </aside>
                </div>

                <aside class="mt-10 rounded-lg border-l-4 border-primary bg-primary/5 p-6 md:p-8" aria-labelledby="privacy-contact-heading">
                    <h2 id="privacy-contact-heading" class="text-lg font-semibold tracking-tight">
                        {{ __('messages.pages.privacy_policy.contact_title') }}
                    </h2>
                    <p class="text-muted-foreground mt-3 text-sm leading-relaxed">
                        {{ __('messages.pages.privacy_policy.contact_description') }}
                    </p>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div>
                            <dt class="font-medium text-foreground">{{ __('messages.pages.privacy_policy.contact_email_label') }}</dt>
                            <dd class="text-muted-foreground mt-1">
                                <a href="{{ $contactHref }}" class="text-primary font-medium underline-offset-2 hover:underline">{{ $contactEmail }}</a>
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-foreground">{{ __('messages.pages.privacy_policy.contact_address_label') }}</dt>
                            <dd class="text-muted-foreground mt-1 leading-relaxed">
                                {{ __('messages.pages.privacy_policy.contact_address') }}
                            </dd>
                        </div>
                    </dl>
                </aside>
            <!-- </div> -->
        </div>
    </section>
</div>

@push('styles')
<style>
    .legal-prose {
        color: var(--foreground);
        font-size: 1rem;
        line-height: 1.75;
        max-width: 65ch;
    }

    .legal-prose > * + * {
        margin-top: 1.25em;
    }

    .legal-prose h1,
    .legal-prose h2,
    .legal-prose h3,
    .legal-prose h4 {
        color: var(--foreground);
        font-weight: 600;
        letter-spacing: -0.02em;
        line-height: 1.3;
        scroll-margin-top: 6rem;
    }

    .legal-prose h1 {
        font-size: 1.875rem;
        margin-top: 0;
    }

    .legal-prose h2 {
        font-size: 1.5rem;
        margin-top: 2.25em;
        padding-bottom: 0.35em;
        border-bottom: 1px solid var(--border);
    }

    .legal-prose h3 {
        font-size: 1.25rem;
        margin-top: 1.75em;
    }

    .legal-prose h4 {
        font-size: 1.125rem;
        margin-top: 1.5em;
    }

    .legal-prose p,
    .legal-prose li {
        color: oklch(0.35 0 0);
    }

    .legal-prose a {
        color: var(--primary);
        font-weight: 500;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .legal-prose a:hover {
        opacity: 0.85;
    }

    .legal-prose strong {
        color: var(--foreground);
        font-weight: 600;
    }

    .legal-prose ul,
    .legal-prose ol {
        margin-top: 0.75em;
        padding-left: 1.5em;
    }

    .legal-prose ul {
        list-style-type: disc;
    }

    .legal-prose ol {
        list-style-type: decimal;
    }

    .legal-prose li {
        margin-top: 0.5em;
        padding-left: 0.25em;
    }

    .legal-prose li::marker {
        color: var(--primary);
    }

    .legal-prose blockquote {
        border-left: 4px solid var(--primary);
        background: oklch(0.97 0 0);
        margin-top: 1.5em;
        padding: 1rem 1.25rem;
        border-radius: 0 0.5rem 0.5rem 0;
        font-style: italic;
    }

    .legal-prose table {
        width: 100%;
        margin-top: 1.5em;
        border-collapse: collapse;
        font-size: 0.9375rem;
    }

    .legal-prose th,
    .legal-prose td {
        border: 1px solid var(--border);
        padding: 0.75rem 1rem;
        text-align: left;
        vertical-align: top;
    }

    .legal-prose th {
        background: oklch(0.97 0 0);
        font-weight: 600;
    }

    .legal-prose hr {
        border: 0;
        border-top: 1px solid var(--border);
        margin: 2.5em 0;
    }

    .legal-prose img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        margin-top: 1.5em;
    }

    #privacy-policy-toc-list a {
        display: block;
        color: oklch(0.45 0 0);
        line-height: 1.5;
        text-decoration: none;
        transition: color 0.15s ease;
    }

    #privacy-policy-toc-list a:hover,
    #privacy-policy-toc-list a.is-active {
        color: var(--primary);
    }

    #privacy-policy-toc-list .toc-h3 {
        padding-left: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var content = document.getElementById('privacy-policy-content');
        var toc = document.getElementById('privacy-policy-toc');
        var tocList = document.getElementById('privacy-policy-toc-list');
        if (!content || !toc || !tocList) return;

        var headings = content.querySelectorAll('h2, h3');
        if (!headings.length) return;

        headings.forEach(function (heading, index) {
            if (!heading.id) {
                heading.id = 'privacy-section-' + (index + 1);
            }

            var item = document.createElement('li');
            if (heading.tagName === 'H3') {
                item.className = 'toc-h3';
            }

            var link = document.createElement('a');
            link.href = '#' + heading.id;
            link.textContent = heading.textContent.trim();
            item.appendChild(link);
            tocList.appendChild(item);
        });

        toc.classList.remove('hidden');

        var tocLinks = tocList.querySelectorAll('a');
        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var target = document.querySelector(link.getAttribute('href'));
                if (!target) return;
                event.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', link.getAttribute('href'));
            });
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var id = entry.target.id;
                    tocLinks.forEach(function (link) {
                        link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
                    });
                });
            }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });

            headings.forEach(function (heading) {
                observer.observe(heading);
            });
        }
    });
})();
</script>
@endpush
@endsection
