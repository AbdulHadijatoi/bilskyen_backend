<section class="my-10">
    <h2 class="text-lg font-semibold mb-4" style="color: var(--cms-text);">{{ $content['title'] ?? __('messages.cms.related_posts') }}</h2>
    <div class="grid sm:grid-cols-3 gap-4">
        @forelse($relatedPosts as $related)
            <a href="{{ route('blog.show', $related->slug) }}" class="border rounded-lg p-4 block hover:shadow-sm" style="border-color: var(--cms-border); color: var(--cms-text);">
                <h3 class="font-medium text-sm">{{ $related->title }}</h3>
            </a>
        @empty
            <p class="text-sm col-span-full" style="color: var(--cms-muted);">—</p>
        @endforelse
    </div>
</section>
