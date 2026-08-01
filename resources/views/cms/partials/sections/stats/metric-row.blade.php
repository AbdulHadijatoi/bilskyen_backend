<section class="py-12 px-4" style="background: var(--cms-surface);">
    <div class="container mx-auto max-w-5xl">
        @if(!empty($content['title']))
            <h2 class="cms-section-title">{{ $content['title'] }}</h2>
        @endif
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-center">
            @foreach($content['items'] ?? [] as $item)
                <div>
                    <div class="text-3xl font-bold" style="color: var(--cms-accent);">{{ $item['value'] ?? '' }}</div>
                    <div class="text-sm mt-1" style="color: var(--cms-muted);">{{ $item['label'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
