@extends('layouts.app')

@section('title', 'My Favorites | Bilskyen')

@php
    use App\Helpers\FormatHelper;
@endphp

@section('content')
<div class="container mx-auto flex flex-col gap-6 py-8">
    <!-- Page Header -->
    <div class="space-y-2">
        <h1 class="text-3xl font-bold text-foreground">My Favorites</h1>
        <p class="text-muted-foreground">Vehicles you've saved for later</p>
    </div>

    <!-- Favorites Grid -->
    @if($favorites->count() > 0)
    <div class="grid w-full grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @foreach($favorites as $favorite)
        @php
            $vehicle = $favorite->vehicle;
        @endphp
        @if($vehicle)
        <div class="flex flex-col rounded-lg border border-border bg-card overflow-hidden p-0 cursor-pointer h-full">
            <a href="/vehicles/{{ $vehicle->id }}" class="block flex-1">
                <!-- Vehicle Image -->
                <div class="relative aspect-video overflow-hidden">
                    <img
                        src="{{ $vehicle->images->first()?->thumbnail_url ?? '/placeholder-vehicle.jpg' }}"
                        alt="{{ $vehicle->brand_name }} {{ $vehicle->model_name }}"
                        class="h-full w-full object-cover transition-transform hover:scale-105"
                    />
                    @if($vehicle->dealer_id)
                    <!-- Dealer Label - Top Left -->
                    <span class="absolute top-2 left-2 z-10 inline-flex items-center rounded-md bg-primary/90 backdrop-blur-sm px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                        Dealer
                    </span>
                    @endif
                    <!-- Heart Icon - Top Right (Filled) -->
                    <button type="button" class="absolute top-2 right-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 backdrop-blur-sm transition-all hover:bg-white hover:scale-110 focus:outline-none focus:ring-2 focus:ring-ring" onclick="event.preventDefault(); event.stopPropagation(); toggleFavorite({{ $vehicle->id }})" aria-label="Remove from favorites">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-red-500 heart-icon filled" data-vehicle-id="{{ $vehicle->id }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                    </button>
                </div>
                
                <!-- Vehicle Details -->
                <div class="px-4 py-4 space-y-4">
                    <div class="flex flex-col gap-1">
                        <h3 class="flex items-center gap-2 text-lg font-bold">
                            {{ $vehicle->title }}
                        </h3>
                        @if($vehicle->version)
                        <p class="text-muted-foreground -mt-1.5 text-xs font-normal">
                            {{ $vehicle->version }}
                        </p>
                        @endif
                        <p class="text-primary text-lg font-bold">
                            {{ FormatHelper::formatCurrency($vehicle->price ?? null) }}
                        </p>
                    </div>

                    <div class="-mt-2 flex flex-wrap gap-1 text-xs font-light">
                        @if($vehicle->mileage || $vehicle->km_driven)
                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->mileage ?? $vehicle->km_driven ?? 0) }} km</span>
                        @endif
                        @if($vehicle->engine_power_hp)
                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ number_format($vehicle->engine_power_hp, 0) }} HP</span>
                        @endif
                        @if($vehicle->first_registration_date)
                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ \Carbon\Carbon::parse($vehicle->first_registration_date)->format('M Y') }}</span>
                        @endif
                        @if($vehicle->fuel_type_name)
                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->fuel_type_name }}</span>
                        @endif
                        @if($vehicle->gear_type_name)
                        <span class="inline-flex items-center rounded-md border border-border px-2 py-1 text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">{{ $vehicle->gear_type_name }}</span>
                        @endif
                    </div>
                </div>
            </a>
            
            <!-- Card Footer -->
            <div class="mt-auto" onclick="event.stopPropagation()">
                <!-- Vehicle Actions -->
                <div class="p-4 pt-2">
                    <div class="flex w-full flex-col gap-2 sm:flex-row">
                        <a href="/vehicles/{{ $vehicle->id }}" class="flex-1" onclick="event.stopPropagation()">
                            <button class="inline-flex h-9 w-full items-center justify-center gap-2 whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-all hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border">
                                View Details
                            </button>
                        </a>
                        <button class="inline-flex h-9 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md border border-border bg-background px-4 py-2 text-sm font-medium shadow-xs transition-all hover:bg-accent hover:text-accent-foreground dark:bg-input/30 dark:border-input dark:hover:bg-input/50 disabled:pointer-events-none disabled:opacity-50 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] box-border" onclick="event.stopPropagation(); handleEnquire({{ $vehicle->id }}, event);">
                            Enquire
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>

    <!-- Pagination -->
    @if($favorites->hasPages())
    <div class="mt-8 flex items-center justify-center gap-2">
        @if($favorites->onFirstPage())
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Previous
        </button>
        @else
        <a href="{{ $favorites->previousPageUrl() }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Previous
        </a>
        @endif

        @foreach($favorites->getUrlRange(1, $favorites->lastPage()) as $page => $url)
        <a href="{{ $url }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $page == $favorites->currentPage() ? 'bg-accent' : '' }}">
            {{ $page }}
        </a>
        @endforeach

        @if($favorites->hasMorePages())
        <a href="{{ $favorites->nextPageUrl() }}" class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2 h-4 w-4">
                <path d="m9 18 6-6-6-6"></path>
            </svg>
        </a>
        @else
        <button class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50" disabled>
            Next
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
            <h3 class="text-lg font-semibold">No favorites yet</h3>
            <p class="text-muted-foreground mt-1">
                Start saving vehicles you like by clicking the heart icon.
            </p>
            <a href="/vehicles" class="mt-4 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90">
                Browse Vehicles
            </a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
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
                const response = await fetch(`/favorites/${vehicleId}`, {
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
                    heartIcon.classList.add('text-gray-700');
                    if (path) path.setAttribute('fill', 'none');
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || 'Removed from favorites', 'success');
                    }
                    
                    // If on favorites page, reload to update list
                    if (window.location.pathname === '/favorites') {
                        setTimeout(() => window.location.reload(), 500);
                    }
                } else {
                    if (response.status === 401) {
                        if (window.showSnackbar) {
                            window.showSnackbar('Please login to manage favorites', 'error');
                        }
                        setTimeout(() => {
                            window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                        return false;
                    }
                    const data = await response.json().catch(() => ({}));
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || 'Failed to remove from favorites', 'error');
                    }
                }
            } else {
                // Add to favorites
                const response = await fetch('/favorites', {
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
                    heartIcon.classList.remove('text-gray-700');
                    heartIcon.classList.add('text-red-500');
                    if (path) path.setAttribute('fill', 'currentColor');
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || 'Saved to favorites', 'success');
                    }
                } else {
                    if (response.status === 401) {
                        if (window.showSnackbar) {
                            window.showSnackbar('Please login to save favorites', 'error');
                        }
                        setTimeout(() => {
                            window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                        }, 1500);
                        return false;
                    }
                    const data = await response.json().catch(() => ({}));
                    if (window.showSnackbar) {
                        window.showSnackbar(data.message || 'Failed to save to favorites', 'error');
                    }
                }
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            if (window.showSnackbar) {
                window.showSnackbar('An error occurred. Please try again.', 'error');
            }
        }
        
        return false;
    };

    // Handle Enquire button click
    window.handleEnquire = async function(vehicleId, event) {
        // Prevent any default behavior and stop propagation
        if (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Get button element to show loading state
        const button = event?.target?.closest('button') || event?.target;
        
        try {
            // Show loading state
            if (button) {
                const originalText = button.textContent;
                button.disabled = true;
                button.textContent = 'Loading...';
                
                // Restore button state after timeout (fallback)
                setTimeout(() => {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                }, 5000);
            }
            
            // Make API call to create lead
            const response = await fetch(`/vehicles/${vehicleId}/enquire`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    category: 'Enquire'
                }),
                credentials: 'same-origin'
            });
            
            // Restore button state
            if (button) {
                button.disabled = false;
                button.textContent = button.getAttribute('data-original-text') || 'Enquire';
            }
            
            if (!response.ok) {
                if (response.status === 401) {
                    // Redirect to login
                    if (window.showSnackbar) {
                        window.showSnackbar('Please login to enquire about vehicles', 'error');
                    }
                    setTimeout(() => {
                        window.location.href = '/auth/login?return_url=' + encodeURIComponent(window.location.pathname);
                    }, 1500);
                    return false;
                }
                
                const errorData = await response.json().catch(() => ({}));
                const errorMessage = errorData.message || 'Failed to create enquiry. Please try again.';
                
                if (window.showSnackbar) {
                    window.showSnackbar(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
                return false;
            }
            
            const data = await response.json();
            
            if (data.status === 'success' && data.data) {
                const phoneNumber = data.data.phone_number;
                
                // Initiate phone call if phone number is available
                if (phoneNumber) {
                    window.location.href = 'tel:' + phoneNumber;
                } else {
                    // Show message that phone number is not available
                    if (window.showSnackbar) {
                        window.showSnackbar('Phone number is not available for this vehicle', 'info');
                    }
                }
            }
        } catch (error) {
            console.error('Error creating enquiry:', error);
            
            // Restore button state
            if (button) {
                button.disabled = false;
                button.textContent = button.getAttribute('data-original-text') || 'Enquire';
            }
            
            if (window.showSnackbar) {
                window.showSnackbar('An error occurred. Please try again.', 'error');
            } else {
                alert('An error occurred. Please try again.');
            }
        }
        
        return false;
    };
</script>
@endpush
@endsection
