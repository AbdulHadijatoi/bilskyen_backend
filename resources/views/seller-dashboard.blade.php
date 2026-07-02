@extends('layouts.app')

@section('title', __('messages.pages.seller_dashboard.title') . ' | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    use App\Constants\VehicleListStatus;
@endphp

@section('content')
<div class="min-h-screen" style="background: #f4f5f7;">
    <div class="panel-content panel-page">
        <x-panel.page-header :title="__('messages.pages.seller_dashboard.title')" />

        <div class="panel-card panel-section">
            <div class="panel-card__body">
                <div class="panel-stat-grid">
                    <x-panel.stat-card
                        :label="__('messages.pages.seller_dashboard.total_vehicles')"
                        :value="$statistics['total_vehicles']"
                    >
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                                <polygon points="12 15 17 21 7 21 12 15"></polygon>
                            </svg>
                        </x-slot:icon>
                    </x-panel.stat-card>

                    <x-panel.stat-card
                        :label="__('messages.pages.seller_dashboard.total_worth')"
                        :value="FormatHelper::formatCurrency($statistics['total_worth'])"
                        icon-variant="success"
                    >
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" x2="12" y1="2" y2="22"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </x-slot:icon>
                    </x-panel.stat-card>

                    <x-panel.stat-card
                        :label="__('messages.pages.seller_dashboard.total_inquiries')"
                        :value="$statistics['total_inquiries']"
                        icon-variant="info"
                    >
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </x-slot:icon>
                    </x-panel.stat-card>

                    <x-panel.stat-card
                        :label="__('messages.pages.seller_dashboard.total_views')"
                        :value="number_format($statistics['total_views'])"
                        icon-variant="warning"
                    >
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </x-slot:icon>
                    </x-panel.stat-card>
                </div>
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-table-card__header" style="border: none; padding: 0 0 0.75rem 0; background: transparent;">
                <h2 class="panel-table-card__title">{{ __('messages.pages.seller_dashboard.my_vehicles') }}</h2>
                <span id="vehicle-count" class="panel-table-card__meta">{{ $vehicles->total() }} {{ __('messages.pages.seller_dashboard.vehicles_count') }}</span>
            </div>

            <div class="panel-tabs" aria-label="{{ __('messages.pages.seller_dashboard.status_tabs') }}">
                <button
                    type="button"
                    onclick="filterByStatus(null, '{{ $token }}')"
                    class="panel-tabs__btn panel-tabs__btn--active status-tab"
                    data-status="all"
                >
                    {{ __('messages.pages.seller_dashboard.all') }}
                    <span class="panel-tabs__count">{{ $statistics['total_vehicles'] }}</span>
                </button>
                <button
                    type="button"
                    onclick="filterByStatus({{ VehicleListStatus::PUBLISHED }}, '{{ $token }}')"
                    class="panel-tabs__btn status-tab"
                    data-status="{{ VehicleListStatus::PUBLISHED }}"
                >
                    {{ __('messages.pages.seller_dashboard.published') }}
                    <span class="panel-tabs__count" id="count-published">{{ $vehicles->where('list_status_id', VehicleListStatus::PUBLISHED)->count() }}</span>
                </button>
                <button
                    type="button"
                    onclick="filterByStatus({{ VehicleListStatus::DRAFT }}, '{{ $token }}')"
                    class="panel-tabs__btn status-tab"
                    data-status="{{ VehicleListStatus::DRAFT }}"
                >
                    {{ __('messages.pages.seller_dashboard.draft') }}
                    <span class="panel-tabs__count" id="count-draft">{{ $vehicles->where('list_status_id', VehicleListStatus::DRAFT)->count() }}</span>
                </button>
                <button
                    type="button"
                    onclick="filterByStatus({{ VehicleListStatus::SOLD }}, '{{ $token }}')"
                    class="panel-tabs__btn status-tab"
                    data-status="{{ VehicleListStatus::SOLD }}"
                >
                    {{ __('messages.pages.seller_dashboard.sold') }}
                    <span class="panel-tabs__count" id="count-sold">{{ $vehicles->where('list_status_id', VehicleListStatus::SOLD)->count() }}</span>
                </button>
                <button
                    type="button"
                    onclick="filterByStatus({{ VehicleListStatus::ARCHIVED }}, '{{ $token }}')"
                    class="panel-tabs__btn status-tab"
                    data-status="{{ VehicleListStatus::ARCHIVED }}"
                >
                    {{ __('messages.pages.seller_dashboard.archived') }}
                    <span class="panel-tabs__count" id="count-archived">{{ $vehicles->where('list_status_id', VehicleListStatus::ARCHIVED)->count() }}</span>
                </button>
            </div>

            <!-- Vehicle Grid -->
            <div id="vehicle-grid" class="grid w-full grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($vehicles as $vehicle)
                <div class="vehicle-card flex flex-col rounded-lg bg-card border border-border overflow-hidden shadow-sm" data-status="{{ $vehicle->list_status_id }}">
                    <!-- Vehicle Image -->
                    <div class="relative aspect-[2/1.5] overflow-hidden">
                        <img
                            src="{{ $vehicle->images->first()?->thumbnail_url ?? $vehicle->images->first()?->image_url ?? '/placeholder-vehicle.jpg' }}"
                            alt="{{ $vehicle->title }}"
                            class="h-full w-full object-cover"
                        />
                        <!-- Status & Sales Type Badges -->
                        <div class="absolute top-2 left-2 z-10 flex flex-row flex-wrap items-center gap-1.5">
                            @php
                                $statusColors = [
                                    VehicleListStatus::DRAFT => 'bg-gray-500',
                                    VehicleListStatus::PUBLISHED => 'bg-green-500',
                                    VehicleListStatus::SOLD => 'bg-blue-500',
                                    VehicleListStatus::ARCHIVED => 'bg-orange-500',
                                ];
                                $statusColor = $statusColors[$vehicle->list_status_id] ?? 'bg-gray-500';
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium text-white {{ $statusColor }}">
                                {{ $vehicle->vehicleListStatus?->name ?? $vehicle->vehicle_list_status_name }}
                            </span>
                            @if($vehicle->salesType)
                            <span class="inline-flex items-center rounded-md bg-green-600/60 px-2.5 py-1 text-xs font-semibold text-primary-foreground shadow-sm">
                                {{ $vehicle->salesType->name }}
                            </span>
                            @endif
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
                            @if($vehicle->km_driven !== null)
                            <span class="inline-flex items-center rounded-md border border-border px-2 py-1">
                                {{ number_format((int) $vehicle->km_driven) }} km
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
                                <span>{{ $vehicle->views_count ?? 0 }} {{ __('messages.pages.seller_dashboard.views') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>{{ $vehicle->enquiries_count ?? 0 }} {{ __('messages.pages.seller_dashboard.inquiries_label') }}</span>
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
                                        {{ __('messages.pages.seller_dashboard.edit') }}
                                    </button>
                                </a>
                                <button 
                                    type="button"
                                    onclick="toggleInquiries({{ $vehicle->id }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-border bg-background px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
                                >
                                    {{ __('messages.pages.seller_dashboard.inquiries') }} ({{ $vehicle->enquiries_count ?? 0 }})
                                </button>
                            </div>
                            <div class="flex gap-2">
                                @if($vehicle->list_status_id == VehicleListStatus::PUBLISHED)
                                <button 
                                    type="button"
                                    onclick="unpublishVehicle({{ $vehicle->id }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-orange-500 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 hover:bg-orange-100 transition-colors"
                                >
                                    {{ __('messages.pages.seller_dashboard.unpublish') }}
                                </button>
                                @else
                                <button 
                                    type="button"
                                    onclick="updateStatus({{ $vehicle->id }}, {{ VehicleListStatus::PUBLISHED }}, '{{ $token }}')"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-green-500 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 transition-colors"
                                >
                                    {{ __('messages.pages.seller_dashboard.publish') }}
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
                                    {{ __('messages.pages.seller_dashboard.delete') }}
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
                        <h3 class="text-lg font-semibold">{{ __('messages.pages.seller_dashboard.no_vehicles_found') }}</h3>
                        <p class="text-muted-foreground mt-1">
                            {{ __('messages.pages.seller_dashboard.no_vehicles_description') }}
                        </p>
                        <a href="/sell-your-car" class="mt-4 inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                            {{ __('messages.pages.seller_dashboard.list_first_vehicle') }}
                        </a>
                    </div>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($vehicles->hasPages())
            <div id="pagination-container" class="flex items-center justify-center gap-2">
                @if($vehicles->onFirstPage())
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    {{ __('messages.common.previous') }}
                </button>
                @else
                <a href="{{ $vehicles->previousPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    {{ __('messages.common.previous') }}
                </a>
                @endif

                <span class="px-4 py-2 text-sm text-muted-foreground">
                    Page {{ $vehicles->currentPage() }} of {{ $vehicles->lastPage() }}
                </span>

                @if($vehicles->hasMorePages())
                <a href="{{ $vehicles->nextPageUrl() }}" class="px-4 py-2 rounded-md border border-border bg-background text-foreground hover:bg-accent">
                    {{ __('messages.common.next') }}
                </a>
                @else
                <button disabled class="px-4 py-2 rounded-md border border-border bg-background text-muted-foreground cursor-not-allowed">
                    {{ __('messages.common.next') }}
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
                        {{ __('messages.pages.seller_dashboard.vehicle_inquiries') }}
                    </h2>
                    <p id="inquiries-modal-subtitle" class="text-sm text-muted-foreground mt-1">
                        {{ __('messages.pages.seller_dashboard.loading_inquiries') }}
                    </p>
                </div>
                <button
                    type="button"
                    onclick="closeInquiriesModal()"
                    class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="{{ __('messages.dialogs.close_dialog') }}"
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
                        <p class="text-sm text-muted-foreground">{{ __('messages.pages.seller_dashboard.loading_inquiries') }}</p>
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
                <p class="text-sm text-muted-foreground">{{ __('messages.pages.seller_dashboard.loading_inquiries') }}</p>
            </div>
        </div>
    `;
            subtitle.textContent = '{{ __('messages.pages.seller_dashboard.loading_inquiries') }}';
    
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
            const inquiryText = inquiries.length === 1 
                ? '{{ __('messages.pages.seller_dashboard.inquiry_count', ['count' => '']) }}'.replace(':count', inquiries.length)
                : '{{ __('messages.pages.seller_dashboard.inquiries_count', ['count' => '']) }}'.replace(':count', inquiries.length);
            subtitle.textContent = `${inquiries.length} ${inquiryText} {{ __('messages.pages.seller_dashboard.for_this_vehicle') }}`;
            
            if (inquiries.length === 0) {
                content.innerHTML = `
                    <div class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 text-muted-foreground">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            <p class="text-lg font-semibold text-foreground">{{ __('messages.pages.seller_dashboard.no_inquiries_yet') }}</p>
                            <p class="text-sm text-muted-foreground mt-2">{{ __('messages.pages.seller_dashboard.no_inquiries_description') }}</p>
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
                                    <p class="text-sm font-semibold text-foreground">${enquiry.name || '{{ __('messages.pages.seller_dashboard.anonymous') }}'}</p>
                                    <p class="text-xs text-muted-foreground mt-1">${enquiry.email || '{{ __('messages.pages.seller_dashboard.no_email') }}'}</p>
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
                            <p class="text-sm text-foreground whitespace-pre-wrap">${enquiry.message || '{{ __('messages.pages.seller_dashboard.no_message_provided') }}'}</p>
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
                        <p class="text-sm text-red-600">{{ __('messages.pages.seller_dashboard.failed_to_load_inquiries') }}</p>
                    </div>
                </div>
            `;
            subtitle.textContent = '{{ __('messages.pages.seller_dashboard.error_loading_inquiries') }}';
        }
    })
    .catch(error => {
        console.error('Error fetching inquiries:', error);
        content.innerHTML = `
            <div class="flex items-center justify-center py-12">
                <div class="text-center">
                    <p class="text-sm text-red-600">{{ __('messages.pages.seller_dashboard.error_occurred_loading') }}</p>
                </div>
            </div>
        `;
        subtitle.textContent = '{{ __('messages.pages.seller_dashboard.error_loading_inquiries') }}';
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
    if (!confirm('{{ __('messages.pages.seller_dashboard.confirm_unpublish') }}')) {
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
                window.showSnackbar('{{ __('messages.pages.seller_dashboard.vehicle_unpublished_successfully') }}', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || '{{ __('messages.pages.seller_dashboard.failed_to_unpublish') }}', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
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
        body: JSON.stringify({ list_status_id: statusId }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.pages.seller_dashboard.vehicle_status_updated') }}', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || '{{ __('messages.pages.seller_dashboard.failed_to_update_status') }}', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
        }
    });
}

window.deleteVehicle = function(vehicleId, token) {
    if (!confirm('{{ __('messages.pages.seller_dashboard.confirm_delete') }}')) {
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
                window.showSnackbar('{{ __('messages.pages.seller_dashboard.vehicle_deleted_successfully') }}', 'success');
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (window.showSnackbar) {
                window.showSnackbar(data.message || '{{ __('messages.pages.seller_dashboard.failed_to_delete') }}', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showSnackbar) {
                window.showSnackbar('{{ __('messages.dialogs.error_occurred') }}', 'error');
        }
    });
}

// Status filtering - make globally accessible
window.currentStatusFilter = null;

window.filterByStatus = function(statusId, token) {
    window.currentStatusFilter = statusId;
    
    // Update active tab
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.classList.remove('panel-tabs__btn--active', 'active');
    });
    
    const activeTab = document.querySelector(`.status-tab[data-status="${statusId || 'all'}"]`);
    if (activeTab) {
        activeTab.classList.add('panel-tabs__btn--active', 'active');
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
        countElement.textContent = `${visibleCount} {{ __('messages.pages.seller_dashboard.vehicles_count') }}`;
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
    const activeTab = document.querySelector('.status-tab.active');
    if (activeTab) {
        activeTab.classList.add('panel-tabs__btn--active');
    }
});
</script>
@endsection
