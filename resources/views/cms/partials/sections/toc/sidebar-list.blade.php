@php
    $html = $post->content_html ?? '';
    preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER);
@endphp
@if(count($matches) > 0)
<nav class="rounded-lg border p-4 text-sm" style="border-color: var(--cms-border); background: var(--cms-muted-bg);" aria-label="{{ $content['title'] ?? 'TOC' }}">
    <p class="font-semibold mb-2" style="color: var(--cms-text);">{{ $content['title'] ?? 'In this article' }}</p>
    <ol class="space-y-1.5 list-decimal list-inside" style="color: var(--cms-muted);">
        @foreach($matches as $i => $m)
            @php $text = trim(strip_tags($m[2])); $anchor = 'toc-'.($i+1); @endphp
            <li><a href="#{{ $anchor }}" class="hover:underline" style="color: var(--cms-accent);">{{ $text }}</a></li>
        @endforeach
    </ol>
</nav>
@endif
