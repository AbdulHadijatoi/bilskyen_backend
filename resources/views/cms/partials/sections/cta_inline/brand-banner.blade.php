<aside class="my-8 rounded-lg p-6 text-center" style="background: var(--cms-accent); color: #fff;">
    <p class="font-semibold mb-3">{{ $content['title'] ?? '' }}</p>
    @if(!empty($content['button_text']) && !empty($content['button_url']))
        <a href="{{ $content['button_url'] }}" class="inline-block bg-white px-5 py-2.5 rounded-lg font-medium" style="color: var(--cms-accent);">{{ $content['button_text'] }}</a>
    @endif
</aside>
