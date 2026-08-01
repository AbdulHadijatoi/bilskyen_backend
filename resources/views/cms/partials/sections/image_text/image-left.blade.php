<section class="container mx-auto px-4 py-12 max-w-5xl">
    <div class="grid md:grid-cols-2 gap-8 items-center">
        <div class="rounded-lg overflow-hidden border order-1" style="border-color: var(--cms-border); min-height: 200px; background: var(--cms-muted-bg);">
            @if(!empty($content['image_url']))
                <img src="{{ $content['image_url'] }}" alt="" class="w-full h-full object-cover aspect-[4/3]">
            @endif
        </div>
        <div class="order-2">
            <h2 class="text-2xl font-bold mb-3" style="color: var(--cms-text);">{{ $content['title'] ?? '' }}</h2>
            <p class="mb-4 whitespace-pre-line" style="color: var(--cms-muted);">{{ $content['body'] ?? '' }}</p>
            @if(!empty($content['cta_text']) && !empty($content['cta_url']))
                <a href="{{ $content['cta_url'] }}" class="cms-btn">{{ $content['cta_text'] }}</a>
            @endif
        </div>
    </div>
</section>
