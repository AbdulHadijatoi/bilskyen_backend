@extends('layouts.app')

@section('title', 'My Listings | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    use App\Constants\VehicleListStatus;
@endphp

@section('content')
<div class="bg-muted min-h-screen">
    <div class="container mx-auto space-y-4 py-6">
        <!-- Statistics Section -->
        <div class="rounded-lg bg-card p-6">
            <h1 class="text-3xl font-bold text-foreground mb-6">My Listings Dashboard</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Vehicles -->
                <div class="rounded-lg border border-border bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Vehicles</p>
                            <p class="text-2xl font-bold text-foreground mt-1">{{ $statistics['total_vehicles'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-primary">
                                <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                                <polygon points="12 15 17 21 7 21 12 15"></polygon>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Worth -->
                <div class="rounded-lg border border-border bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Worth</p>
                            <p class="text-2xl font-bold text-foreground mt-1">{{ FormatHelper::formatCurrency($statistics['total_worth']) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-500/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-green-500">
                                <line x1="12" x2="12" y1="2" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Inquiries -->
                <div class="rounded-lg border border-border bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Inquiries</p>
                            <p class="text-2xl font-bold text-foreground mt-1">{{ $statistics['total_inquiries'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-500/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-blue-500">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="rounded-lg border border-border bg-background p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Total Views</p>
                            <p class="text-2xl font-bold text-foreground mt-1">{{ number_format($statistics['total_views']) }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-purple-500/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 text-purple-500">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Listings Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-foreground">My Vehicles</h2>
                <span id="vehicle-count" class="text-muted-foreground text-sm">{{ $vehicles->total() }} vehicles</span>
            </div>

            <!-- Status Tabs -->
            <div class="border-b border-border">
                <nav class="flex space-x-1" aria-label="Status tabs">
                    <button
                        type="button"
                        onclick="filterByStatus(null, '{{ $token }}')"
                        class="status-tab active inline-flex items-center px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent hover:text-foreground hover:border-border transition-colors"
                        data-status="all"
                    >
                        All
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-muted text-muted-foreground">{{ $statistics['total_vehicles'] }}</span>
                    </button>
                    <button
                        type="button"
                        onclick="filterByStatus({{ VehicleListStatus::PUBLISHED }}, '{{ $token }}')"
                        class="status-tab inline-flex items-center px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-muted-foreground hover:text-foreground hover:border-border transition-colors"
                        data-status="{{ VehicleListStatus::PUBLISHED }}"
                    >
                        Published
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700" id="count-published">{{ $vehicles->where('vehicle_list_status_id', VehicleListStatus::PUBLISHED)->count() }}</span>
                    </button>
                    <button
                        type="button"
                        onclick="filterByStatus({{ VehicleListStatus::DRAFT }}, '{{ $token }}')"
                        class="status-tab inline-flex items-center px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-muted-foreground hover:text-foreground hover:border-border transition-colors"
                        data-status="{{ VehicleListStatus::DRAFT }}"
                    >
                        Draft
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-700" id="count-draft">{{ $vehicles->where('vehicle_list_status_id', VehicleListStatus::DRAFT)->count() }}</span>
                    </button>
                    <button
                        type="button"
                        onclick="filterByStatus({{ VehicleListStatus::SOLD }}, '{{ $token }}')"
                        class="status-tab inline-flex items-center px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-muted-foreground hover:text-foreground hover:border-border transition-colors"
                        data-status="{{ VehicleListStatus::SOLD }}"
                    >
                        Sold
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-700" id="count-sold">{{ $vehicles->where('vehicle_list_status_id', VehicleListStatus::SOLD)->count() }}</span>
                    </button>
                    <button
                        type="button"
                        onclick="filterByStatus({{ VehicleListStatus::ARCHIVED }}, '{{ $token }}')"
                        class="status-tab inline-flex items-center px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 border-transparent text-muted-foreground hover:text-foreground hover:border-border transition-colors"
                        data-status="{{ VehicleListStatus::ARCHIVED }}"
                    >
                        Archived
                        <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-700" id="count-archived">{{ $vehicles->where('vehicle_list_status_id', VehicleListStatus::ARCHIVED)->count() }}</span>
                    </button>
                </nav>
            </div>

            <!-- Vehicle Grid -->
            <div id="vehicle-grid" class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($vehicles as $vehicle)
                <div class="vehicle-card flex flex-col rounded-lg bg-card border border-border overflow-hidden shadow-sm" data-status="{{ $vehicle->vehicle_list_status_id }}">
                    <!-- Vehicle Image -->
                    <div class="relative aspect-[2/1.5] overflow-hidden">
                        <img
                            src="{{ $vehicle->images->first()?->thumbnail_url ?? $vehicle->images->first()?->url ?? '/placeholder-vehicle.jpg' }}"
                            alt="{{ $vehicle->title }}"
                            class="h-full w-full object-cover"
                        />
                        <!-- Status Badge -->
                        <div class="absolute top-2 left-2">
                            @php
                                $statusColors = [
                                    VehicleListStatus::DRAFT => 'bg-gray-500',
                                    VehicleListStatus::PUBLISHED => 'bg-green-500',
                                    VehicleListStatus::SOLD => 'bg-blue-500',
                                    VehicleListStatus::ARCHIVED => 'bg-orange-500',
                                ];
                                $statusColor = $statusColors[$vehicle->vehicle_list_status_id] ?? 'bg-gray-500';
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-white {{ $statusColor }}">
                                {{ $vehicle->vehicle_list_status_name }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Vehicle Details -->
                    <div class="p-4 space-y-3">
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">{{ $vehicle->title }}</h3>
                            @if($vehicle->version)
                            <p class="text-sm text-muted-foreground mt-1">{{ $vehicle->version }}</p>
                            @endif
                            <p class="text-xl font-bold text-primary mt-2">
                                {{ FormatHelper::formatCurrency($vehicle->price) }}
                            </p>
                        </div>

                        <!-- Vehicle Stats -->
                        <div class="flex flex-wrap gap-2 text-xs">
                            @if($vehicle->mileage || $vehicle->km_driven)
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1">
                                {{ number_format($vehicle->mileage ?? $vehicle->km_driven ?? 0) }} km
                            </span>
                            @endif
                            @if($vehicle->engine_power_hp)
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1">
                                {{ number_format($vehicle->engine_power_hp, 0) }} HP
                            </span>
                            @endif
                            @if($vehicle->first_registration_date)
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1">
                                {{ \Carbon\Carbon::parse($vehicle->first_registration_date)->format('M Y') }}
                            </span>
                            @endif
                        </div>

                        <!-- Vehicle Metrics -->
                        <div class="flex items-center gap-4 text-sm text-muted-foreground pt-2 border-t border-border">
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <span>{{ $vehicle->views_count ?? 0 }} views</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>{{ $vehicle->enquiries_count ?? 0 }} inquiries</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 pt-2">
                            <div class="flex gap-2">
                                <a href="{{ route('seller.vehicle.edit', ['token' => $token, 'id' => $vehicle->id]) }}" class="flex-1">
                                    <button class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        Edit
                                    </button>
                                </a>
                                <button 
                                    type="button"
                                    onclick="toggleInquiries({{ $vehicle->id }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
                                >
                                    Inquiries ({{ $vehicle->enquiries_count ?? 0 }})
                                </button>
                            </div>
                            <div class="flex gap-2">
                                @if($vehicle->vehicle_list_status_id == VehicleListStatus::PUBLISHED)
                                <button 
                                    type="button"
                                    onclick="unpublishVehicle({{ $vehicle->id }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-orange-500 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100 transition-colors"
                                >
                                    Unpublish
                                </button>
                                @else
                                <button 
                                    type="button"
                                    onclick="updateStatus({{ $vehicle->id }}, {{ VehicleListStatus::PUBLISHED }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-green-500 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 transition-colors"
                                >
                                    Publish
                                </button>
                                @endif
                                <button 
                                    type="button"
                                    onclick="deleteVehicle({{ $vehicle->id }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-red-500 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full flex items-center justify-center py-12">
                    <div class="flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mb-4 h-12 w-12 text-muted-foreground">
                            <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                            <polygon points="12 15 17 21 7 21 12 15"></polygon>
                        </svg>
                        <h3 class="text-lg font-semibold">No vehicles found</h3>
                        <p class="text-muted-foreground mt-1">
                            You haven't listed any vehicles yet.
                        </p>
                        <a href="/sell-your-car" class="mt-4 inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                            List Your First Vehicle
                        </a>
                    </div>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($vehicles->hasPages())
            <div class="flex items-center justify-center gap-2">
                @if($vehicles->onFirstPage())
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    Previous
                </button>
                @else
                <a href="{{ $vehicles->previousPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    Previous
                </a>
                @endif

                <span class="px-4 py-2 text-sm text-muted-foreground">
                    Page {{ $vehicles->currentPage() }} of {{ $vehicles->lastPage() }}
                </span>

                @if($vehicles->hasMorePages())
                <a href="{{ $vehicles->nextPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    Next
                </a>
                @else
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    Next
                </button>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Inquiries Modal Dialog -->
<div id="inquiries-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="inquiries-modal-title">
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        onclick="closeInquiriesModal()"
        aria-hidden="true"
    ></div>
    
    <!-- Modal Container -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-background rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <div class="flex-1">
                    <h2 id="inquiries-modal-title" class="text-xl font-semibold text-foreground">
                        Vehicle Inquiries
                    </h2>
                    <p id="inquiries-modal-subtitle" class="text-sm text-muted-foreground mt-1">
                        Loading inquiries...
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeInquiriesModal()"
                    class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="Close dialog"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Scrollable Content -->
            <div id="inquiries-modal-content" class="overflow-y-auto flex-1 px-6 py-4">
                <div class="flex items-center justify-center py-8">
                    <div class="text-center">
                        <svg class="animate-spin h-8 w-8 text-primary mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-muted-foreground">Loading inquiries...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Make functions globally accessible
window.currentVehicleId = null;
window.currentToken = null;

window.toggleInquiries = function(vehicleId, token) {
    window.currentVehicleId = vehicleId;
    window.currentToken = token;
    window.openInquiriesModal(vehicleId, token);
}

window.openInquiriesModal = function(vehicleId, token) {
    const modal = document.getElementById('inquiries-modal');
    const content = document.getElementById('inquiries-modal-content');
    const subtitle = document.getElementById('inquiries-modal-subtitle');
    
    if (!modal) return;
    
    // Show modal
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    
    // Reset content to loading state
    content.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-primary mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-muted-foreground">Loading inquiries...</p>
            </div>
        </div>
    `;
    subtitle.textContent = 'Loading inquiries...';
    
    // Fetch inquiries
    fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/inquiries`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.inquiries) {
            const inquiries = data.inquiries;
            subtitle.textContent = `${inquiries.length} ${inquiries.length === 1 ? 'inquiry' : 'inquiries'} for this vehicle`;
            
            if (inquiries.length === 0) {
                content.innerHTML = `
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-muted-foreground">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <p class="text-lg font-semibold text-foreground">No inquiries yet</p>
                            <p class="text-sm text-muted-foreground mt-2">This vehicle hasn't received any inquiries.</p>
                        </div>
                    </div>
                `;
            } else {
                let inquiriesHTML = '<div class="space-y-4">';
                inquiries.forEach(enquiry => {
                    const date = new Date(enquiry.created_at);
                    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    inquiriesHTML += `
                        <div class="rounded-lg border border-border bg-card p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-foreground">${enquiry.name || 'Anonymous'}</p>
                                    <p class="text-xs text-muted-foreground mt-1">${enquiry.email || 'No email'}</p>
                                    ${enquiry.phone ? `<p class="text-xs text-muted-foreground">${enquiry.phone}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-muted-foreground">${formattedDate}</span>
                                    ${enquiry.type ? `
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-primary/10 text-primary mt-2">
                                        ${enquiry.type}
                                    </span>
                                    ` : ''}
                                </div>
                            </div>
                            ${enquiry.subject ? `<p class="text-sm font-medium text-foreground mb-2">${enquiry.subject}</p>` : ''}
                            <p class="text-sm text-foreground whitespace-pre-wrap">${enquiry.message || 'No message provided'}</p>
                        </div>
                    `;
                });
                inquiriesHTML += '</div>';
                content.innerHTML = inquiriesHTML;
            }
        } else {
            content.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <p class="text-sm text-red-600">Failed to load inquiries. Please try again.</p>
                    </div>
                </div>
            `;
            subtitle.textContent = 'Error loading inquiries';
        }
    })
    .catch(error => {
        console.error('Error fetching inquiries:', error);
        content.innerHTML = `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <p class="text-sm text-red-600">An error occurred while loading inquiries. Please try again.</p>
                </div>
            </div>
        `;
        subtitle.textContent = 'Error loading inquiries';
    });
}

window.closeInquiriesModal = function() {
    const modal = document.getElementById('inquiries-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.currentVehicleId = null;
        window.currentToken = null;
    }
}

// Handle ESC key to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.closeInquiriesModal();
    }
});

window.unpublishVehicle = function(vehicleId, token) {
    if (!confirm('Are you sure you want to unpublish this vehicle? It will be removed from public listings.')) {
        return;
    }

    fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/unpublish`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (window.showSnackbar) {
                window.showSnackbar('Vehicle unpublished successfully', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || 'Failed to unpublish vehicle', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
            window.showSnackbar('An error occurred. Please try again.', 'error');
        }
    });
}

window.updateStatus = function(vehicleId, statusId, token) {
    fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ vehicle_list_status_id: statusId }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (window.showSnackbar) {
                window.showSnackbar('Vehicle status updated successfully', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || 'Failed to update vehicle status', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
            window.showSnackbar('An error occurred. Please try again.', 'error');
        }
    });
}

window.deleteVehicle = function(vehicleId, token) {
    if (!confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
        return;
    }

    fetch(`/seller-dashboard/${token}/vehicle/${vehicleId}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (window.showSnackbar) {
                window.showSnackbar('Vehicle deleted successfully', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || 'Failed to delete vehicle', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
            window.showSnackbar('An error occurred. Please try again.', 'error');
        }
    });
}

// Status filtering - make globally accessible
window.currentStatusFilter = null;

window.filterByStatus = function(statusId, token) {
    window.currentStatusFilter = statusId;
    
    // Update active tab
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.classList.remove('active', 'border-primary', 'text-foreground');
        tab.classList.add('text-muted-foreground', 'border-transparent');
    });
    
    const activeTab = document.querySelector(`.status-tab[data-status="${statusId || 'all'}"]`);
    if (activeTab) {
        activeTab.classList.add('active', 'border-primary', 'text-foreground');
        activeTab.classList.remove('text-muted-foreground', 'border-transparent');
    }
    
    // Filter vehicles
    const vehicleCards = document.querySelectorAll('.vehicle-card');
    let visibleCount = 0;
    
    vehicleCards.forEach(card => {
        const cardStatus = parseInt(card.getAttribute('data-status'));
        if (statusId === null || cardStatus === statusId) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Update vehicle count
    const countElement = document.getElementById('vehicle-count');
    if (countElement) {
        countElement.textContent = `${visibleCount} ${visibleCount === 1 ? 'vehicle' : 'vehicles'}`;
    }
    
    // Hide pagination when filtering (since we're filtering client-side)
    const paginationContainer = document.getElementById('pagination-container');
    if (paginationContainer) {
        if (statusId !== null) {
            paginationContainer.style.display = 'none';
        } else {
            paginationContainer.style.display = 'block';
        }
    }
}

// Initialize: show all vehicles by default
document.addEventListener('DOMContentLoaded', function() {
    // Set active tab styling
    const activeTab = document.querySelector('.status-tab.active');
    if (activeTab) {
        activeTab.classList.add('border-primary', 'text-foreground');
        activeTab.classList.remove('text-muted-foreground', 'border-transparent');
    }
});
</script>

@push('styles')
<style>
    /* Status Tab Styles */
    .status-tab {
        position: relative;
        transition: all 0.2s ease;
    }
    
    .status-tab.active {
        color: hsl(var(--foreground));
        border-bottom-color: hsl(var(--primary)) !important;
    }
    
    .status-tab:hover:not(.active) {
        color: hsl(var(--foreground));
        border-bottom-color: hsl(var(--border));
    }
    
    .status-tab span {
        transition: all 0.2s ease;
    }
</style>
@endpush
@endsection
