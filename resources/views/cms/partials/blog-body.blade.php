@php
    $layout = $post->layout ?? 'classic';
    $style = $post->style ?? 'brand';
    $sections = $post->sections ?? [];
    $plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($post->content_html ?? '')) ?? '');
    $wordCount = $plainText === '' ? 0 : count(preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $readingMinutes = max(1, (int) ceil($wordCount / 200));

    $chromeBefore = [];
    $chromeAfter = [];
    $tocSection = null;
    foreach ($sections as $section) {
        $type = $section['type'] ?? '';
        if ($type === 'toc') {
            $tocSection = $section;
        } elseif (in_array($type, ['pull_quote'], true) && ($layout === 'feature')) {
            $chromeBefore[] = $section;
        } elseif (in_array($type, ['author_box', 'related_posts', 'cta_inline'], true)) {
            $chromeAfter[] = $section;
        } elseif ($type === 'pull_quote') {
            $chromeAfter[] = $section;
        } else {
            $chromeAfter[] = $section;
        }
    }
@endphp

<div class="cms-blog cms-blog-layout-{{ $layout }} cms-theme-{{ $style }} flex min-h-screen flex-col" style="background: var(--cms-surface); color: var(--cms-text);">
    @include('cms.partials.theme-styles')

    @if($layout === 'hero' && $post->featuredMedia)
        <section class="relative min-h-[42vh] flex items-end" style="background: var(--cms-hero-bg);">
            <img
                src="{{ $post->featuredMedia->url() }}"
                alt="{{ $post->featuredMedia->alt_text ?? $post->title }}"
                class="absolute inset-0 w-full h-full object-cover opacity-60"
            >
            <div class="relative container mx-auto px-4 md:px-6 py-10 max-w-3xl text-white">
                @if($post->category)
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] opacity-90">{{ $post->category->name }}</p>
                @endif
                <h1 class="mt-2 text-3xl md:text-4xl font-semibold tracking-tight">{{ $post->title }}</h1>
                @if($post->excerpt)
                    <p class="mt-3 opacity-90">{{ $post->excerpt }}</p>
                @endif
            </div>
        </section>
    @else
        <section class="border-b py-7 md:py-9" style="background: var(--cms-muted-bg); border-color: var(--cms-border);" aria-labelledby="blog-post-heading">
            <div class="container mx-auto px-4 md:px-6">
                <div class="mx-auto {{ $layout === 'magazine' ? 'max-w-5xl' : 'max-w-3xl' }}">
                    @unless($previewMode ?? false)
                    <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm transition-colors" style="color: var(--cms-muted);">
                        ← {{ __('messages.cms.back_to_blog') }}
                    </a>
                    @endunless

                    <header class="{{ ($previewMode ?? false) ? '' : 'mt-4' }}">
                        @if($post->category)
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" style="color: var(--cms-accent);">
                                {{ $post->category->name }}
                            </p>
                        @endif

                        <h1 id="blog-post-heading" class="mt-2 text-balance font-semibold tracking-tight {{ $layout === 'feature' ? 'text-4xl md:text-5xl' : 'text-3xl md:text-[2.35rem] md:leading-tight' }}">
                            {{ $post->title }}
                        </h1>

                        @if($post->excerpt)
                            <p class="mt-3 text-[0.975rem] leading-relaxed" style="color: var(--cms-muted);">
                                {{ $post->excerpt }}
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-[13px]" style="color: var(--cms-muted);">
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
    @endif

    <section class="py-7 md:py-9" aria-label="{{ $post->title }}">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mx-auto {{ $layout === 'magazine' ? 'max-w-5xl' : 'max-w-3xl' }} {{ $layout === 'magazine' && $tocSection ? 'md:grid md:grid-cols-[220px_1fr] md:gap-10' : '' }}">
                @if($layout === 'magazine' && $tocSection)
                    <aside class="mb-6 md:mb-0 md:sticky md:top-24 md:self-start">
                        @include('cms.partials.render-block', ['block' => $tocSection, 'post' => $post])
                    </aside>
                @endif

                <article>
                    @if($layout !== 'hero' && $post->featuredMedia)
                        <figure class="mb-7 overflow-hidden rounded-md border" style="border-color: var(--cms-border); background: var(--cms-muted-bg);">
                            <img
                                src="{{ $post->featuredMedia->url() }}"
                                alt="{{ $post->featuredMedia->alt_text ?? $post->title }}"
                                class="aspect-[2/1] w-full object-cover"
                                loading="eager"
                            >
                        </figure>
                    @endif

                    @foreach($chromeBefore as $section)
                        @include('cms.partials.render-block', ['block' => $section, 'post' => $post, 'relatedPosts' => $relatedPosts ?? collect()])
                    @endforeach

                    <div id="blog-post-content" class="blog-prose">
                        {!! $post->content_html !!}
                    </div>

                    @foreach($chromeAfter as $section)
                        @include('cms.partials.render-block', ['block' => $section, 'post' => $post, 'relatedPosts' => $relatedPosts ?? collect()])
                    @endforeach
                </article>
            </div>

            @unless($previewMode ?? false)
            <div class="mx-auto {{ $layout === 'magazine' ? 'max-w-5xl' : 'max-w-3xl' }} mt-8 flex flex-wrap items-center justify-between gap-3 border-t pt-5" style="border-color: var(--cms-border);">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1.5 text-sm" style="color: var(--cms-muted);">
                    ← {{ __('messages.cms.back_to_blog') }}
                </a>
                <a href="{{ route('vehicles') }}" class="text-sm font-medium underline-offset-2 hover:underline" style="color: var(--cms-accent);">
                    {{ __('messages.cms.browse_vehicles') }}
                </a>
            </div>
            @endunless
        </div>
    </section>
</div>

@push('styles')
<style>
    .blog-prose {
        color: var(--cms-text, var(--foreground));
        font-size: 1.0125rem;
        line-height: 1.7;
        max-width: none;
    }
    .blog-prose > * + * { margin-top: 1.05em; }
    .blog-prose > :first-child { margin-top: 0; }
    .blog-prose h1, .blog-prose h2, .blog-prose h3, .blog-prose h4 {
        color: var(--cms-text, var(--foreground));
        font-weight: 600;
        letter-spacing: -0.02em;
        line-height: 1.3;
        scroll-margin-top: 5rem;
    }
    .blog-prose h1 { font-size: 1.5rem; margin-top: 1.75em; }
    .blog-prose h2 { font-size: 1.25rem; margin-top: 1.75em; margin-bottom: 0.15em; }
    .blog-prose h3 { font-size: 1.1rem; margin-top: 1.45em; }
    .blog-prose p, .blog-prose li { color: var(--cms-muted, oklch(0.36 0.01 250)); }
    .blog-prose a { color: var(--cms-accent, var(--primary)); font-weight: 500; text-decoration: underline; text-underline-offset: 2px; }
    .blog-prose strong { color: var(--cms-text, var(--foreground)); font-weight: 600; }
    .blog-prose ul, .blog-prose ol { margin-top: 0.75em; padding-left: 1.25em; }
    .blog-prose ul { list-style-type: disc; }
    .blog-prose ol { list-style-type: decimal; }
    .blog-prose li { margin-top: 0.35em; }
    .blog-prose blockquote {
        border-left: 3px solid var(--cms-accent, var(--primary));
        margin-top: 1.35em;
        padding: 0.15rem 0 0.15rem 1rem;
        font-style: italic;
        color: var(--cms-muted);
    }
    .blog-prose img {
        display: block; max-width: 100%; height: auto;
        border-radius: 0.5rem; border: 1px solid var(--cms-border, var(--border));
        margin-top: 1.25em;
    }
    @media (min-width: 768px) {
        .blog-prose { font-size: 1.0625rem; line-height: 1.75; }
    }
</style>
@endpush
