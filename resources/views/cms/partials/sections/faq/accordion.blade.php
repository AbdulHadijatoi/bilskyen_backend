<section class="container mx-auto px-4 py-12 max-w-2xl">
    @if(!empty($content['title']))
        <h2 class="text-2xl font-bold mb-6" style="color: var(--cms-text);">{{ $content['title'] }}</h2>
    @endif
    <div class="space-y-4">
        @foreach($content['items'] ?? [] as $item)
            <details class="border rounded-lg p-4" style="border-color: var(--cms-border); background: var(--cms-surface);">
                <summary class="font-medium cursor-pointer" style="color: var(--cms-text);">{{ $item['question'] ?? '' }}</summary>
                <p class="mt-2" style="color: var(--cms-muted);">{{ $item['answer'] ?? '' }}</p>
            </details>
        @endforeach
    </div>
</section>
