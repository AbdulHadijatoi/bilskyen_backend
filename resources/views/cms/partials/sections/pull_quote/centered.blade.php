<figure class="my-10 text-center max-w-2xl mx-auto">
    <blockquote class="text-2xl font-medium italic" style="color: var(--cms-text);">“{{ $content['quote'] ?? '' }}”</blockquote>
    @if(!empty($content['attribution']))
        <figcaption class="mt-3 text-sm" style="color: var(--cms-muted);">— {{ $content['attribution'] }}</figcaption>
    @endif
</figure>
