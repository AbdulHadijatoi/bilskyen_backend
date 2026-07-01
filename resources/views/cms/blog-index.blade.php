@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-10 max-w-5xl">
    <h1 class="text-3xl font-bold mb-2">{{ __('messages.cms.blog_title') }}</h1>
    <p class="text-gray-600 mb-8">{{ __('messages.cms.blog_description') }}</p>

    <div class="grid gap-6 md:grid-cols-2">
        @forelse($posts as $post)
            <article class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                @if($post->featuredMedia)
                    <img src="{{ $post->featuredMedia->url() }}" alt="{{ $post->featuredMedia->alt_text ?? $post->title }}" class="w-full h-48 object-cover" loading="lazy">
                @endif
                <div class="p-5">
                    @if($post->category)
                        <span class="text-xs uppercase tracking-wide text-blue-600">{{ $post->category->name }}</span>
                    @endif
                    <h2 class="text-xl font-semibold mt-1 mb-2">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                    </h2>
                    <p class="text-gray-600 text-sm mb-3">{{ $post->excerpt }}</p>
                    <p class="text-xs text-gray-400">{{ $post->published_at?->format('d M Y') }}</p>
                </div>
            </article>
        @empty
            <p>{{ __('messages.cms.no_posts') }}</p>
        @endforelse
    </div>

    <div class="mt-8">{{ $posts->links() }}</div>
</div>
@endsection
