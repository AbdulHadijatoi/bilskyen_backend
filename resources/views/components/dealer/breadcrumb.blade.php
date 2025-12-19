@php
    $segments = explode('/', request()->path());
    $dealerSegments = array_slice($segments, 1); // Remove 'dealer' from segments
    $base = '/dealer';
@endphp

<nav class="flex items-center space-x-1 text-sm">
    <a href="{{ $base }}" class="text-muted-foreground hover:text-foreground transition-colors">Dashboard</a>
    @if(count($dealerSegments) > 0)
        <span class="text-muted-foreground">/</span>
        @foreach($dealerSegments as $index => $segment)
            @php
                $href = $base . '/' . implode('/', array_slice($dealerSegments, 0, $index + 1));
                $label = ucwords(str_replace('-', ' ', $segment));
                $isLast = $index === count($dealerSegments) - 1;
            @endphp
            @if($isLast)
                <span class="text-foreground font-medium">{{ $label }}</span>
            @else
                <a href="{{ $href }}" class="text-muted-foreground hover:text-foreground transition-colors">{{ $label }}</a>
                <span class="text-muted-foreground">/</span>
            @endif
        @endforeach
    @endif
</nav>

