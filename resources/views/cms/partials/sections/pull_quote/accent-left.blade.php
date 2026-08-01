<figure class="my-8 border-l-4 pl-5 py-1" style="border-color: var(--cms-accent);">
    <blockquote class="text-xl italic" style="color: var(--cms-text);">“{{ $content['quote'] ?? '' }}”</blockquote>
    @if(!empty($content['attribution']))
        <figcaption class="mt-2 text-sm" style="color: var(--cms-muted);">— {{ $content['attribution'] }}</figcaption>
    @endif
</figure>
