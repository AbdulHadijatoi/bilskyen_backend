<section class="container mx-auto px-4 py-12 max-w-5xl">
    @if(!empty($content['title']))
        <h2 class="cms-section-title">{{ $content['title'] }}</h2>
    @endif
    <div class="grid md:grid-cols-2 gap-4">
        @foreach($content['items'] ?? [] as $item)
            <div class="border rounded-lg p-4" style="border-color: var(--cms-border); background: var(--cms-surface);">
                <h3 class="font-semibold mb-2" style="color: var(--cms-text);">{{ $item['question'] ?? '' }}</h3>
                <p style="color: var(--cms-muted);">{{ $item['answer'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</section>
