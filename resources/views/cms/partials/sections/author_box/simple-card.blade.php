@if($post?->author)
<aside class="my-8 flex gap-4 items-start rounded-lg border p-4" style="border-color: var(--cms-border); background: var(--cms-muted-bg);">
    <div class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-white shrink-0" style="background: var(--cms-accent);">
        {{ strtoupper(mb_substr($post->author->name, 0, 1)) }}
    </div>
    <div>
        <p class="font-semibold" style="color: var(--cms-text);">{{ $post->author->name }}</p>
        @if(!empty($content['show_bio']))
            <p class="text-sm mt-1" style="color: var(--cms-muted);">{{ __('messages.cms.author_default_bio') }}</p>
        @endif
    </div>
</aside>
@endif
