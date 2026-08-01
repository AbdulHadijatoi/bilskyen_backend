@php
    $layout = $page->layout ?? 'guide';
    $style = $page->style ?? 'brand';
@endphp
<div class="cms-landing cms-layout-{{ $layout }} cms-theme-{{ $style }} min-h-screen" style="background: var(--cms-surface); color: var(--cms-text);">
    @include('cms.partials.theme-styles')
    @foreach($page->blocks ?? [] as $block)
        @include('cms.partials.render-block', ['block' => $block, 'vehicles' => $vehicles ?? collect(), 'page' => $page])
    @endforeach
</div>
