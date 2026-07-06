@props([
    'items' => [],
])

<nav {{ $attributes->merge(['class' => 'panel-breadcrumb', 'aria-label' => 'Breadcrumb']) }}>
    <ol class="panel-breadcrumb__list">
        @foreach ($items as $item)
            <li class="panel-breadcrumb__item">
                @if (!empty($item['current']))
                    <span class="panel-breadcrumb__current" aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a href="{{ $item['url'] ?? '#' }}" class="panel-breadcrumb__link">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
