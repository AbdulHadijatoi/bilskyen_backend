<section class="py-12 px-4" style="background: var(--cms-muted-bg);">
    <div class="container mx-auto max-w-5xl">
        @if(!empty($content['title']))
            <h2 class="cms-section-title">{{ $content['title'] }}</h2>
        @endif
        <div class="grid md:grid-cols-3 gap-4">
            @foreach($content['items'] ?? [] as $item)
                <blockquote class="rounded-lg border p-5" style="border-color: var(--cms-border); background: var(--cms-surface);">
                    <p class="italic mb-4" style="color: var(--cms-text);">“{{ $item['quote'] ?? '' }}”</p>
                    <footer>
                        <cite class="not-italic font-semibold text-sm" style="color: var(--cms-text);">{{ $item['author'] ?? '' }}</cite>
                        @if(!empty($item['role']))
                            <span class="block text-xs" style="color: var(--cms-muted);">{{ $item['role'] }}</span>
                        @endif
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
