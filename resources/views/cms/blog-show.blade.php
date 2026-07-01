@extends('layouts.app')

@section('content')
<article class="container mx-auto px-4 py-10 max-w-3xl">
    @if($post->featuredMedia)
        <img src="{{ $post->featuredMedia->url() }}" alt="{{ $post->featuredMedia->alt_text ?? $post->title }}" class="w-full rounded-lg mb-6 max-h-96 object-cover" loading="lazy">
    @endif
    <header class="mb-6">
        @if($post->category)
            <span class="text-sm text-blue-600">{{ $post->category->name }}</span>
        @endif
        <h1 class="text-4xl font-bold mt-1">{{ $post->title }}</h1>
        <p class="text-sm text-gray-500 mt-2">{{ $post->published_at?->format('d F Y') }}@if($post->author) · {{ $post->author->name }}@endif</p>
    </header>
    <div class="prose max-w-none">
        {!! $post->content_html !!}
    </div>
    <p class="mt-8"><a href="{{ route('blog.index') }}" class="text-blue-600 hover:underline">← {{ __('messages.cms.back_to_blog') }}</a></p>
</article>
@endsection
