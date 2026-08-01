<section class="container mx-auto px-4 py-12 max-w-3xl">
    @if(!empty($content['title']))
        <h2 class="text-2xl font-bold mb-2" style="color: var(--cms-text);">{{ $content['title'] }}</h2>
    @endif
    @if(!empty($content['subtitle']))
        <p class="mb-6" style="color: var(--cms-muted);">{{ $content['subtitle'] }}</p>
    @endif
    <ul class="space-y-4">
        @foreach($content['items'] ?? [] as $item)
            <li class="flex gap-3 items-start">
                <span class="mt-0.5 shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-white text-xs" style="background: var(--cms-accent);">✓</span>
                <div>
                    <h3 class="font-semibold" style="color: var(--cms-text);">{{ $item['title'] ?? '' }}</h3>
                    <p class="text-sm" style="color: var(--cms-muted);">{{ $item['body'] ?? '' }}</p>
                </div>
            </li>
        @endforeach
    </ul>
</section>
