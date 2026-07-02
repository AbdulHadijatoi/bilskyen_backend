@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'button',
])

@php
    $classes = 'panel-btn panel-btn--' . $variant;
    if ($size === 'sm') {
        $classes .= ' panel-btn--sm';
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
