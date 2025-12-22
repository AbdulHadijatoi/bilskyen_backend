@php
    use App\Helpers\ViewHelper;
    
    // Generate breadcrumbs from current route
    $segments = explode('/', request()->path());
    $dealerSegments = array_slice($segments, 1); // Remove 'dealer' from segments
    $base = '/dealer';
    $breadcrumbs = ViewHelper::generateBreadcrumbs($base, $dealerSegments);
@endphp

<nav class="flex items-center space-x-1 text-sm">
    <a href="{{ $base }}" class="text-muted-foreground hover:text-foreground transition-colors">Dashboard</a>
    @if(count($breadcrumbs) > 0)
        <span class="text-muted-foreground">/</span>
        @foreach($breadcrumbs as $breadcrumb)
            @if($breadcrumb['isLast'])
                <span class="text-foreground font-medium">{{ $breadcrumb['label'] }}</span>
            @else
                <a href="{{ $breadcrumb['href'] }}" class="text-muted-foreground hover:text-foreground transition-colors">{{ $breadcrumb['label'] }}</a>
                <span class="text-muted-foreground">/</span>
            @endif
        @endforeach
    @endif
</nav>

