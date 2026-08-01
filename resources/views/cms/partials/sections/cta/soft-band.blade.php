<section class="py-12 px-4 text-center border-y" style="background: var(--cms-muted-bg); border-color: var(--cms-border);">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-2" style="color: var(--cms-text);">{{ $content['title'] ?? '' }}</h2>
        @if(!empty($content['subtitle']))
            <p class="mb-4" style="color: var(--cms-muted);">{{ $content['subtitle'] }}</p>
        @endif
        @if(!empty($content['button_text']) && !empty($content['button_url']))
            <a href="{{ $content['button_url'] }}" class="cms-btn">{{ $content['button_text'] }}</a>
        @endif
    </div>
</section>
