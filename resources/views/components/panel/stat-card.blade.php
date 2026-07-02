@props([
    'label',
    'value',
    'iconVariant' => 'primary',
])

<div {{ $attributes->merge(['class' => 'panel-stat-card']) }}>
    <div>
        <p class="panel-stat-card__label">{{ $label }}</p>
        <p class="panel-stat-card__value">{{ $value }}</p>
    </div>
    @if(isset($icon))
        <div class="panel-stat-card__icon panel-stat-card__icon--{{ $iconVariant }}">
            {{ $icon }}
        </div>
    @endif
</div>
