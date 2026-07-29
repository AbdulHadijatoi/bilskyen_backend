@extends('layouts.app')

@section('title', ($seo['meta_title'] ?? $post->title) . ' | Bilskyen')

@php
    $plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($post->content_html ?? '')) ?? '');
    $wordCount = $plainText === '' ? 0 : count(preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $readingMinutes = max(1, (int) ceil($wordCount / 200));
@endphp

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted/50 border-b border-border py-7 md:py-9" aria-labelledby="blog-post-heading">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-3xl">
                <a
                    href="{{ route('blog.index') }}"
                    class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 text-sm transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    {{ __('messages.cms.back_to_blog') }}
                </a>

                <header class="mt-4">
                    @if($post->category)
                        <p class="text-primary text-[11px] font-semibold uppercase tracking-[0.16em]">
                            {{ $post->category->name }}
                        </p>
                    @endif

                    <h1 id="blog-post-heading" class="mt-2 text-balance text-3xl font-semibold tracking-tight md:text-[2.35rem] md:leading-tight">
                        {{ $post->title }}
                    </h1>

                    @if($post->excerpt)
                        <p class="text-muted-foreground mt-3 text-[0.975rem] leading-relaxed">
                            {{ $post->excerpt }}
                        </p>
                    @endif

                    <div class="text-muted-foreground mt-4 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px]">
                        @if($post->published_at)
                            <time datetime="{{ $post->published_at->toDateString() }}">
                                {{ $post->published_at->locale(app()->getLocale())->translatedFormat('j M Y') }}
                            </time>
                        @endif

                        @if($post->author)
                            <span aria-hidden="true">·</span>
                            <span>{{ __('messages.cms.by_author', ['name' => $post->author->name]) }}</span>
                        @endif

                        <span aria-hidden="true">·</span>
                        <span>{{ __('messages.cms.min_read', ['count' => $readingMinutes]) }}</span>
                    </div>
                </header>
            </div>
        </div>
    </section>

    <section class="py-7 md:py-9" aria-label="{{ $post->title }}">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto max-w-3xl">
                <article>
                    @if($post->featuredMedia)
                        <figure class="mb-7 overflow-hidden rounded-md border border-border bg-muted">
                            <img
                                src="{{ $post->featuredMedia->url() }}"
                                alt="{{ $post->featuredMedia->alt_text ?? $post->title }}"
                                class="aspect-[2/1] w-full object-cover"
                                loading="eager"
                            >
                        </figure>
                    @endif

                    <div id="blog-post-content" class="blog-prose">
                        {!! $post->content_html !!}
                    </div>
                </article>

                <footer class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-5">
                    <a
                        href="{{ route('blog.index') }}"
                        class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 text-sm transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        {{ __('messages.cms.back_to_blog') }}
                    </a>

                    <a
                        href="{{ route('vehicles') }}"
                        class="text-primary text-sm font-medium underline-offset-2 transition-colors hover:underline"
                    >
                        {{ __('messages.cms.browse_vehicles') }}
                    </a>
                </footer>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .blog-prose {
        color: var(--foreground);
        font-size: 1.0125rem;
        line-height: 1.7;
        max-width: none;
    }

    .blog-prose > * + * {
        margin-top: 1.05em;
    }

    .blog-prose > :first-child {
        margin-top: 0;
    }

    .blog-prose h1,
    .blog-prose h2,
    .blog-prose h3,
    .blog-prose h4 {
        color: var(--foreground);
        font-weight: 600;
        letter-spacing: -0.02em;
        line-height: 1.3;
        scroll-margin-top: 5rem;
    }

    .blog-prose h1 {
        font-size: 1.5rem;
        margin-top: 1.75em;
    }

    .blog-prose h2 {
        font-size: 1.25rem;
        margin-top: 1.75em;
        margin-bottom: 0.15em;
    }

    .blog-prose h3 {
        font-size: 1.1rem;
        margin-top: 1.45em;
    }

    .blog-prose h4 {
        font-size: 1rem;
        margin-top: 1.3em;
    }

    .blog-prose p,
    .blog-prose li {
        color: oklch(0.36 0.01 250);
    }

    .blog-prose a {
        color: var(--primary);
        font-weight: 500;
        text-decoration: underline;
        text-underline-offset: 2px;
        text-decoration-thickness: 1px;
    }

    .blog-prose a:hover {
        opacity: 0.85;
    }

    .blog-prose strong {
        color: var(--foreground);
        font-weight: 600;
    }

    .blog-prose em {
        font-style: italic;
    }

    .blog-prose ul,
    .blog-prose ol {
        margin-top: 0.75em;
        padding-left: 1.25em;
    }

    .blog-prose ul {
        list-style-type: disc;
    }

    .blog-prose ol {
        list-style-type: decimal;
    }

    .blog-prose li {
        margin-top: 0.35em;
        padding-left: 0.15em;
    }

    .blog-prose li::marker {
        color: var(--primary);
    }

    .blog-prose blockquote {
        border-left: 3px solid var(--primary);
        background: transparent;
        margin-top: 1.35em;
        padding: 0.15rem 0 0.15rem 1rem;
        font-style: italic;
        color: oklch(0.4 0.015 250);
    }

    .blog-prose blockquote p {
        color: inherit;
    }

    .blog-prose table {
        width: 100%;
        margin-top: 1.25em;
        border-collapse: collapse;
        font-size: 0.9375rem;
    }

    .blog-prose th,
    .blog-prose td {
        border: 1px solid var(--border);
        padding: 0.55rem 0.75rem;
        text-align: left;
        vertical-align: top;
    }

    .blog-prose th {
        background: oklch(0.975 0.005 250);
        font-weight: 600;
        color: var(--foreground);
    }

    .blog-prose hr {
        border: 0;
        border-top: 1px solid var(--border);
        margin: 1.75em 0;
    }

    .blog-prose img {
        display: block;
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        margin-top: 1.25em;
    }

    .blog-prose figure {
        margin-top: 1.25em;
    }

    .blog-prose figcaption {
        margin-top: 0.5em;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: var(--muted-foreground);
    }

    .blog-prose pre {
        overflow-x: auto;
        margin-top: 1.2em;
        padding: 0.85rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        background: oklch(0.975 0.005 250);
        font-size: 0.85rem;
        line-height: 1.6;
    }

    .blog-prose code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.9em;
    }

    .blog-prose :not(pre) > code {
        border-radius: 0.3rem;
        background: oklch(0.97 0.005 250);
        padding: 0.08em 0.3em;
    }

    @media (min-width: 768px) {
        .blog-prose {
            font-size: 1.0625rem;
            line-height: 1.75;
        }
    }
</style>
@endpush
@endsection
