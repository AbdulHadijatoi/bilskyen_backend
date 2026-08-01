<section class="container mx-auto px-4 py-10 max-w-5xl">
    <div class="grid md:grid-cols-2 gap-8 prose" style="color: var(--cms-text);">
        <div>{!! $content['html'] ?? '' !!}</div>
        <div>{!! $content['html_secondary'] ?? '' !!}</div>
    </div>
</section>
