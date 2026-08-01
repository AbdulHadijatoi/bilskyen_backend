@php
    $type = $block['type'] ?? '';
    $variant = $block['variant'] ?? 'default';
    $content = $block['content'] ?? $block;
    $view = "cms.partials.sections.{$type}.{$variant}";
@endphp
@if(view()->exists($view))
    @include($view, ['content' => $content, 'vehicles' => $vehicles ?? collect(), 'page' => $page ?? null, 'post' => $post ?? null, 'relatedPosts' => $relatedPosts ?? collect()])
@elseif(view()->exists("cms.partials.sections.{$type}.default"))
    @include("cms.partials.sections.{$type}.default", ['content' => $content, 'vehicles' => $vehicles ?? collect(), 'page' => $page ?? null, 'post' => $post ?? null, 'relatedPosts' => $relatedPosts ?? collect()])
@endif
