@php
    $showFavorites = $showFavorites ?? false;
@endphp
<a
    id="navbar-favorites"
    href="{{ route('favorites') }}"
    class="{{ $showFavorites ? 'inline-flex' : 'hidden' }} relative h-9 w-9 items-center justify-center rounded-full bg-primary-foreground/10 text-primary-foreground hover:bg-primary-foreground/20{{ request()->routeIs('favorites') ? ' bg-primary-foreground/20' : '' }}"
    aria-label="{{ __('messages.navigation.my_favorites') }}"
    title="{{ __('messages.navigation.my_favorites') }}"
    @if(request()->routeIs('favorites')) aria-current="page" @endif
>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
    </svg>
</a>
