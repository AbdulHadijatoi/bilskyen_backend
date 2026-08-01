<section class="py-16 px-4 text-center" style="background: var(--cms-hero-bg); color: var(--cms-hero-text);">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ $content['headline'] ?? ($page->title ?? '') }}</h1>
        @if(!empty($content['subheadline']))
            <p class="text-lg opacity-90 mb-6">{{ $content['subheadline'] }}</p>
        @endif
        @if(!empty($content['cta_text']) && !empty($content['cta_url']))
            <a href="{{ $content['cta_url'] }}" class="cms-btn">{{ $content['cta_text'] }}</a>
        @endif
    </div>
</section>
