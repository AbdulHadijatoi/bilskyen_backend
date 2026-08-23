@php
    $showSavedSearches = $showSavedSearches ?? false;
@endphp
<a
    id="navbar-saved-searches"
    href="{{ route('saved-searches.index') }}"
    class="{{ $showSavedSearches ? 'inline-flex' : 'hidden' }} relative h-9 w-9 items-center justify-center rounded-full bg-primary-foreground/10 text-primary-foreground hover:bg-primary-foreground/20{{ request()->routeIs('saved-searches.index') ? ' bg-primary-foreground/20' : '' }}"
    aria-label="{{ __('messages.navigation.my_saved_searches') }}"
    title="{{ __('messages.navigation.my_saved_searches') }}"
    @if(request()->routeIs('saved-searches.index')) aria-current="page" @endif
>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"></path>
    </svg>
</a>
