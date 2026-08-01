<section class="py-14 px-4 border-b" style="background: var(--cms-surface); border-color: var(--cms-border);">
    <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-3xl md:text-4xl font-semibold tracking-tight mb-3" style="color: var(--cms-text);">{{ $content['headline'] ?? ($page->title ?? '') }}</h1>
        @if(!empty($content['subheadline']))
            <p class="text-base mb-6" style="color: var(--cms-muted);">{{ $content['subheadline'] }}</p>
        @endif
        @if(!empty($content['cta_text']) && !empty($content['cta_url']))
            <a href="{{ $content['cta_url'] }}" class="cms-btn">{{ $content['cta_text'] }}</a>
        @endif
    </div>
</section>
