<section class="container mx-auto px-4 py-12 max-w-5xl">
    @if(!empty($content['title']))
        <h2 class="cms-section-title">{{ $content['title'] }}</h2>
    @endif
    @if(!empty($content['subtitle']))
        <p class="text-center mb-8 -mt-4" style="color: var(--cms-muted);">{{ $content['subtitle'] }}</p>
    @endif
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($content['items'] ?? [] as $item)
            <div class="rounded-lg border p-5" style="border-color: var(--cms-border); background: var(--cms-surface);">
                <div class="w-9 h-9 rounded-full flex items-center justify-center mb-3 text-white text-sm font-bold" style="background: var(--cms-accent);">✓</div>
                <h3 class="font-semibold mb-1" style="color: var(--cms-text);">{{ $item['title'] ?? '' }}</h3>
                <p class="text-sm" style="color: var(--cms-muted);">{{ $item['body'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</section>
