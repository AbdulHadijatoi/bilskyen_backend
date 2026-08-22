@extends('layouts.app')

@section('title', __('messages.pages.favorites.title') . ' | Bilskyen')

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <!-- Page Header -->
    <div class="space-y-2">
        <h1 class="text-3xl font-bold text-foreground">{{ __('messages.pages.favorites.title') }}</h1>
        <p class="text-muted-foreground">{{ __('messages.pages.favorites.description') }}</p>
    </div>

    <!-- Favorites Grid -->
    @if($favorites->count() > 0)
    <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @foreach($favorites as $favorite)
        @php
            $vehicle = $favorite->vehicle;
        @endphp
        @if($vehicle)
            @php
                $firstImage = $vehicle->images->first();
                $imgUrl = $firstImage?->thumbnail_url ?? $firstImage?->image_url ?? '/placeholder-vehicle.jpg';
                $badges = $listingPresentation->badgeFields($vehicle);
            @endphp
            <x-vehicle-listing-item
                :vehicle="$vehicle"
                :img-url="$imgUrl"
                :img-alt="$vehicle->title"
                :sales-type-name="$vehicle->salesType?->name"
                :trust-badge="$badges['trust_badge'] ?? false"
                :price-dropped-recently="$badges['price_dropped_recently'] ?? false"
                :premium-dealer-badge="$badges['premium_dealer_badge'] ?? false"
                :is-boosted="$badges['is_boosted'] ?? false"
                :is-favorited="true"
            />
        @endif
        @endforeach
    </div>

    <!-- Enquiry Dialogs for Favorite Vehicles -->
    @foreach($favorites as $favorite)
        @if($favorite->vehicle)
            <x-enquiry-dialog type="enquiry" :vehicle="$favorite->vehicle" />
        @endif
    @endforeach

    <!-- Pagination -->
    @if($favorites->hasPages())
    <div class="mt-8 flex items-center justify-center gap-2">
        @if($favorites->onFirstPage())
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            {{ __('messages.common.previous') }}
        </button>
        @else
        <a href="{{ $favorites->previousPageUrl() }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            {{ __('messages.common.previous') }}
        </a>
        @endif

        @foreach($favorites->getUrlRange(1, $favorites->lastPage()) as $page => $url)
        <a href="{{ $url }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $page == $favorites->currentPage() ? 'bg-accent' : '' }}">
            {{ $page }}
        </a>
        @endforeach

        @if($favorites->hasMorePages())
        <a href="{{ $favorites->nextPageUrl() }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            {{ __('messages.common.next') }}
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a>
        @else
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            {{ __('messages.common.next') }}
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </button>
        @endif
    </div>
    @endif
    @else
    <!-- Empty State -->
    <div class="flex items-center justify-center py-12">
        <div class="flex flex-col items-center justify-center text-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-16 w-16 text-muted-foreground">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
            </svg>
            <h3 class="text-lg font-semibold">{{ __('messages.pages.vehicles.no_favorites') }}</h3>
            <p class="text-muted-foreground mt-1">
                {{ __('messages.pages.vehicles.no_favorites_description') }}
            </p>
            <a href="{{ route('vehicles') }}" class="mt-4 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90">
                {{ __('messages.pages.vehicles.browse_vehicles') }}
            </a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    const favoritesUrl = @json(route('favorites'));
    const favoritesStoreUrl = @json(route('favorites.store'));
    const favoritesDestroyUrl = (id) => @json(rtrim(route('favorites.destroy', ['vehicleId' => '__ID__']), '/')).replace('__ID__', encodeURIComponent(id));
    // Toggle favorite function
    window.toggleFavorite = async function(vehicleId, event) {
        // Prevent any default behavior and stop propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        try {
            const heartIcon = document.querySelector(`.heart-icon[data-vehicle-id="${vehicleId}"]`);
            if (!heartIcon) {
                console.error('Heart icon not found for vehicle:', vehicleId);
                return false;
            }
            
            const path = heartIcon.querySelector('path');
            const isFavorited = heartIcon.classList.contains('filled') || path?.getAttribute('fill') === 'currentColor';

            if (isFavorited) {
                // Remove from favorites
                const response = await fetch(favoritesDestroyUrl(vehicleId), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json().catch(() => ({}));
                    heartIcon.classList.remove('filled');
                    heartIcon.classList.remove('text-red-500');
                    // Restore original color based on dealer status
                    const dealerId = heartIcon.getAttribute('data-dealer-id');
                    if (dealerId && dealerId !== '') {
                        heartIcon.classList.add('text-blue-600');
                        heartIcon.classList.remove('text-orange-600');
                    } else {
                        heartIcon.classList.add('text-orange-600');
                        heartIcon.classList.remove('text-blue-600');
                    }
                    if (path) path.setAttribute('fill', 'none');
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || '{{ __('messages.messages.removed_from_favorites') }}', 'success');
                    }
                    
                    // If on favorites page, reload to update list
                    if (window.location.pathname === @json(parse_url(route('favorites'), PHP_URL_PATH))) {
                        setTimeout(() => window.location.reload(), 500);
                    }
                } else {
                    if (response.status === 401) {
                        if (window.showSnackbar) {
                            window.showSnackbar('{{ __('messages.errors.please_login') }}', 'error');
                        }
                        setTimeout(() => {
                            window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                        return false;
                    }
                    const data = await response.json().catch(() => ({}));
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_remove_favorites') }}', 'error');
                    }
                }
            } else {
                // Add to favorites
                const response = await fetch(favoritesStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ vehicle_id: vehicleId }),
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json().catch(() => ({}));
                    heartIcon.classList.add('filled');
                    heartIcon.classList.remove('text-blue-600', 'text-orange-600');
                    heartIcon.classList.add('text-red-500');
                    if (path) path.setAttribute('fill', 'currentColor');
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || '{{ __('messages.messages.saved_to_favorites') }}', 'success');
                    }
                } else {
                    if (response.status === 401) {
                        if (window.showSnackbar) {
                            window.showSnackbar('{{ __('messages.errors.please_login_to_save') }}', 'error');
                        }
                        setTimeout(() => {
                            window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                        return false;
                    }
                    const data = await response.json().catch(() => ({}));
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || '{{ __('messages.errors.failed_to_save_favorites') }}', 'error');
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
            }
        }
        
        return false;
    };

</script>
@endpush
@endsection
