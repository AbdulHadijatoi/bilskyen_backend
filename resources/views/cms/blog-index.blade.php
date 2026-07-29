@extends('layouts.app')

@section('title', ($seo['meta_title'] ?? __('messages.cms.blog_title')) . ' | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <section class="bg-muted py-16 text-center md:py-20">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                {{ __('messages.cms.blog_title') }}
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg leading-relaxed">
                {{ __('messages.cms.blog_description') }}
            </p>
        </div>
    </section>

    <section class="py-12 md:py-16">
        <div class="container mx-auto px-4 md:px-6">
            @if($posts->isEmpty())
                <div class="rounded-lg border border-border bg-card px-6 py-16 text-center shadow-sm">
                    <p class="text-muted-foreground text-base">{{ __('messages.cms.no_posts') }}</p>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        <article class="group flex flex-col overflow-hidden rounded-lg border border-border bg-card shadow-sm transition-shadow hover:shadow-md">
                            <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden">
                                @if($post->featuredMedia)
                                    <img
                                        src="{{ $post->featuredMedia->url() }}"
                                        alt="{{ $post->featuredMedia->alt_text ?? $post->title }}"
                                        class="aspect-[16/10] w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="bg-muted flex aspect-[16/10] w-full items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted-foreground/50" aria-hidden="true">
                                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
                                        </svg>
                                    </div>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-5 md:p-6">
                                @if($post->category)
                                    <span class="text-primary text-xs font-semibold uppercase tracking-wider">
                                        {{ $post->category->name }}
                                    </span>
                                @endif
                                <h2 class="mt-2 text-xl font-semibold tracking-tight">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="text-foreground transition-colors hover:text-primary">
                                        {{ $post->title }}
                                    </a>
                                </h2>
                                @if($post->excerpt)
                                    <p class="text-muted-foreground mt-2 line-clamp-3 flex-1 text-sm leading-relaxed">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif
                                <div class="mt-4 flex items-center justify-between gap-3 border-t border-border pt-4">
                                    <time class="text-muted-foreground text-xs" datetime="{{ $post->published_at?->toDateString() }}">
                                        {{ $post->published_at?->locale(app()->getLocale())->translatedFormat('j M Y') }}
                                    </time>
                                    <a href="{{ route('blog.show', $post->slug) }}" class="text-primary text-sm font-medium underline-offset-2 hover:underline">
                                        {{ __('messages.cms.read_more') }}
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($posts->hasPages())
                    <div class="mt-10 flex justify-center">
                        {{ $posts->links() }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>
@endsection
