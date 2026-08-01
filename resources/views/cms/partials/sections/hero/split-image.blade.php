<section class="py-12 md:py-16 px-4" style="background: var(--cms-muted-bg);">
    <div class="container mx-auto max-w-6xl grid md:grid-cols-2 gap-8 items-center">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-4" style="color: var(--cms-text);">{{ $content['headline'] ?? ($page->title ?? '') }}</h1>
            @if(!empty($content['subheadline']))
                <p class="text-lg mb-6" style="color: var(--cms-muted);">{{ $content['subheadline'] }}</p>
            @endif
            @if(!empty($content['cta_text']) && !empty($content['cta_url']))
                <a href="{{ $content['cta_url'] }}" class="cms-btn">{{ $content['cta_text'] }}</a>
            @endif
        </div>
        <div class="rounded-lg overflow-hidden border" style="border-color: var(--cms-border); background: var(--cms-surface); min-height: 220px;">
            @if(!empty($content['image_url']))
                <img src="{{ $content['image_url'] }}" alt="" class="w-full h-full object-cover aspect-[4/3]">
            @else
                <div class="w-full aspect-[4/3] flex items-center justify-center" style="background: var(--cms-muted-bg); color: var(--cms-muted);">Image</div>
            @endif
        </div>
    </div>
</section>
