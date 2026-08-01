<aside class="my-8 rounded-lg p-6 text-center border" style="background: var(--cms-muted-bg); border-color: var(--cms-border);">
    <p class="font-semibold mb-3" style="color: var(--cms-text);">{{ $content['title'] ?? '' }}</p>
    @if(!empty($content['button_text']) && !empty($content['button_url']))
        <a href="{{ $content['button_url'] }}" class="cms-btn">{{ $content['button_text'] }}</a>
    @endif
</aside>
