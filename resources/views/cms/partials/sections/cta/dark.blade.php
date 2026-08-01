<section class="py-12 px-4 text-center" style="background: var(--cms-hero-bg); color: var(--cms-hero-text);">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-2">{{ $content['title'] ?? '' }}</h2>
        @if(!empty($content['subtitle']))
            <p class="mb-4 opacity-90">{{ $content['subtitle'] }}</p>
        @endif
        @if(!empty($content['button_text']) && !empty($content['button_url']))
            <a href="{{ $content['button_url'] }}" class="cms-btn">{{ $content['button_text'] }}</a>
        @endif
    </div>
</section>
