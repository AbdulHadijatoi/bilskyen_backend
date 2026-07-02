@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'panel-page-header']) }}>
    <div>
        <h1 class="panel-page-header__title">{{ $title }}</h1>
        @if($subtitle)
            <p class="panel-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if(isset($actions))
        <div class="panel-page-header__actions">
            {{ $actions }}
        </div>
    @endif
</div>
