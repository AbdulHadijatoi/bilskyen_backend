@extends('layouts.app')

@section('title', __('messages.pages.seller_dashboard.title') . ' | Bilskyen')

@php
    use App\Helpers\FormatHelper;
    use App\Constants\VehicleListStatus;

    $dashboardUrl = function (?int $status = null) use ($token) {
        return route('seller.dashboard', array_filter([
            'token' => $token,
            'status' => $status,
        ]));
    };

    $countFor = function (int $status) use ($statusCounts) {
        return (int) ($statusCounts[$status] ?? 0);
    };

    $statusChipClass = function (int $statusId): string {
        return match ($statusId) {
            VehicleListStatus::PUBLISHED => 'panel-status-chip--success',
            VehicleListStatus::SOLD => 'panel-status-chip--info',
            VehicleListStatus::ARCHIVED, VehicleListStatus::PENDING_REVIEW => 'panel-status-chip--warning',
            default => 'panel-status-chip--neutral',
        };
    };

    $statusTabs = [
        ['label' => __('messages.pages.seller_dashboard.all'), 'status' => null, 'count' => $statistics['total_vehicles']],
        ['label' => __('messages.pages.seller_dashboard.published'), 'status' => VehicleListStatus::PUBLISHED, 'count' => $countFor(VehicleListStatus::PUBLISHED)],
        ['label' => __('messages.pages.seller_dashboard.draft'), 'status' => VehicleListStatus::DRAFT, 'count' => $countFor(VehicleListStatus::DRAFT)],
        ['label' => __('messages.pages.seller_dashboard.sold'), 'status' => VehicleListStatus::SOLD, 'count' => $countFor(VehicleListStatus::SOLD)],
        ['label' => __('messages.pages.seller_dashboard.archived'), 'status' => VehicleListStatus::ARCHIVED, 'count' => $countFor(VehicleListStatus::ARCHIVED)],
    ];
@endphp

@section('content')
<div class="min-h-screen" style="background: #f4f5f7;">
    <div class="panel-content panel-page">
        <x-panel.page-header
            :title="__('messages.pages.seller_dashboard.title')"
            :subtitle="__('messages.pages.seller_dashboard.subtitle')"
        >
            <x-slot:actions>
                <x-panel.button href="/sell-your-car" variant="primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" x2="12" y1="5" y2="19"></line>
                        <line x1="5" x2="19" y1="12" y2="12"></line>
                    </svg>
                    {{ __('messages.pages.seller_dashboard.list_new_vehicle') }}
                </x-panel.button>
            </x-slot:actions>
        </x-panel.page-header>

        <div class="panel-card panel-section">
            <div class="panel-card__body">
                <div class="panel-stat-grid">
                    <x-panel.stat-card
                        :label="__('messages.pages.seller_dashboard.total_vehicles')"
                        :value="$statistics['total_vehicles']"
                    >
                        <x-slot:icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </x-slot:icon>
                    </x-panel.stat-card>
                </div>
            </div>
        </div>

        <div class="panel-section">
            <div class="panel-filters-card">
                <div class="panel-filters-grid">
                    <div class="panel-filters-grid__search">
                        <label class="panel-filters-card__label" for="seller-vehicle-search">{{ __('messages.common.search') }}</label>
                        <div class="panel-search-input">
                            <svg class="panel-search-input__icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.3-4.3"></path>
                            </svg>
                            <input
                                id="seller-vehicle-search"
                                type="search"
                                class="panel-search-input__field"
                                placeholder="{{ __('messages.pages.seller_dashboard.search_placeholder') }}"
                                autocomplete="off"
                            />
                        </div>
                    </div>
                    <div class="panel-filters-grid__tabs">
                        <div class="panel-tabs" aria-label="{{ __('messages.pages.seller_dashboard.status_tabs') }}">
                            @foreach ($statusTabs as $tab)
                                <a
                                    href="{{ $dashboardUrl($tab['status']) }}"
                                    class="panel-tabs__btn {{ ($currentStatus === $tab['status'] || ($currentStatus === null && $tab['status'] === null)) ? 'panel-tabs__btn--active' : '' }}"
                                >
                                    {{ $tab['label'] }}
                                    <span class="panel-tabs__count">{{ $tab['count'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-table-card">
                <div class="panel-table-card__header">
                    <h2 class="panel-table-card__title">{{ __('messages.pages.seller_dashboard.my_vehicles') }}</h2>
                    <span class="panel-table-card__meta">{{ $vehicles->total() }} {{ __('messages.pages.seller_dashboard.vehicles_count') }}</span>
                </div>

                @if ($vehicles->isEmpty())
                    <div class="panel-table-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path>
                            <polygon points="12 15 17 21 7 21 12 15"></polygon>
                        </svg>
                        <p class="panel-table-empty__title">{{ __('messages.pages.seller_dashboard.no_vehicles_found') }}</p>
                        <p>{{ __('messages.pages.seller_dashboard.no_vehicles_description') }}</p>
                        <a href="/sell-your-car" class="panel-btn panel-btn--primary" style="margin-top:0.75rem">
                            {{ __('messages.pages.seller_dashboard.list_first_vehicle') }}
                        </a>
                    </div>
                @else
                    <div class="panel-table-card__body">
                        <div class="seller-mobile-list">
                            @foreach ($vehicles as $vehicle)
                                @php
                                    $imageUrl = $vehicle->images->first()?->thumbnail_url ?? $vehicle->images->first()?->image_url ?? '/placeholder-vehicle.jpg';
                                    $searchText = trim($vehicle->title . ' ' . ($vehicle->version ?? ''));
                                @endphp
                                <article
                                    class="seller-list-row--mobile"
                                    data-vehicle-row
                                    data-search="{{ $searchText }}"
                                >
                                    <div class="seller-list-row--mobile__top">
                                        <div class="panel-vehicle-cell__thumb">
                                            <img src="{{ $imageUrl }}" alt="" loading="lazy" />
                                        </div>
                                        <div class="min-w-0" style="flex:1">
                                            <div class="panel-vehicle-cell__title">{{ $vehicle->title }}</div>
                                            @if ($vehicle->version)
                                                <div class="panel-vehicle-cell__subtitle">{{ $vehicle->version }}</div>
                                            @endif
                                            <div style="margin-top:0.5rem;display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
                                                <span class="panel-status-chip {{ $statusChipClass($vehicle->list_status_id) }}">
                                                    {{ $vehicle->vehicleListStatus?->name ?? $vehicle->vehicle_list_status_name }}
                                                </span>
                                                <span class="panel-price-cell">{{ FormatHelper::formatCurrency($vehicle->price) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:1rem;font-size:0.8125rem;color:var(--muted-foreground)">
                                        <span>{{ $vehicle->views_count ?? 0 }} {{ __('messages.pages.seller_dashboard.views') }}</span>
                                        <button type="button" class="panel-metric-cell--link" onclick="toggleInquiries({{ $vehicle->id }})">
                                            {{ $vehicle->enquiries_count ?? 0 }} {{ __('messages.pages.seller_dashboard.inquiries_label') }}
                                        </button>
                                    </div>
                                    <div class="seller-list-row--mobile__actions">
                                        <a href="{{ route('seller.vehicle.edit', ['token' => $token, 'id' => $vehicle->id]) }}" class="panel-btn panel-btn--primary panel-btn--sm">
                                            {{ __('messages.pages.seller_dashboard.edit') }}
                                        </a>
                                        <button type="button" class="panel-btn panel-btn--outline panel-btn--sm" onclick="toggleInquiries({{ $vehicle->id }})">
                                            {{ __('messages.pages.seller_dashboard.inquiries') }}
                                        </button>
                                        @if ($vehicle->list_status_id == VehicleListStatus::PUBLISHED)
                                            <button type="button" class="panel-btn panel-btn--outline panel-btn--sm" onclick="unpublishVehicle({{ $vehicle->id }})">
                                                {{ __('messages.pages.seller_dashboard.unpublish') }}
                                            </button>
                                        @else
                                            <button type="button" class="panel-btn panel-btn--outline panel-btn--sm" onclick="updateStatus({{ $vehicle->id }}, {{ VehicleListStatus::PUBLISHED }})">
                                                {{ __('messages.pages.seller_dashboard.publish') }}
                                            </button>
                                        @endif
                                        <button type="button" class="panel-btn panel-btn--outline panel-btn--sm" style="color:var(--destructive);border-color:color-mix(in oklch, var(--destructive) 35%, var(--border))" onclick="deleteVehicle({{ $vehicle->id }})">
                                            {{ __('messages.pages.seller_dashboard.delete') }}
                                        </button>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <table class="panel-data-table seller-desktop-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('messages.pages.seller_dashboard.col_vehicle') }}</th>
                                    <th scope="col">{{ __('messages.pages.seller_dashboard.col_price') }}</th>
                                    <th scope="col">{{ __('messages.pages.seller_dashboard.col_status') }}</th>
                                    <th scope="col" class="panel-data-table__col--hide-sm">{{ __('messages.pages.seller_dashboard.col_views') }}</th>
                                    <th scope="col" class="panel-data-table__col--hide-sm">{{ __('messages.pages.seller_dashboard.col_inquiries') }}</th>
                                    <th scope="col" style="text-align:right">{{ __('messages.pages.seller_dashboard.col_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vehicles as $vehicle)
                                    @php
                                        $imageUrl = $vehicle->images->first()?->thumbnail_url ?? $vehicle->images->first()?->image_url ?? '/placeholder-vehicle.jpg';
                                        $searchText = trim($vehicle->title . ' ' . ($vehicle->version ?? ''));
                                    @endphp
                                    <tr data-vehicle-row data-search="{{ $searchText }}">
                                        <td>
                                            <div class="panel-vehicle-cell">
                                                <div class="panel-vehicle-cell__thumb">
                                                    <img src="{{ $imageUrl }}" alt="" loading="lazy" />
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="panel-vehicle-cell__title">{{ $vehicle->title }}</div>
                                                    @if ($vehicle->version)
                                                        <div class="panel-vehicle-cell__subtitle">{{ $vehicle->version }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="panel-price-cell">{{ FormatHelper::formatCurrency($vehicle->price) }}</span>
                                        </td>
                                        <td>
                                            <span class="panel-status-chip {{ $statusChipClass($vehicle->list_status_id) }}">
                                                {{ $vehicle->vehicleListStatus?->name ?? $vehicle->vehicle_list_status_name }}
                                            </span>
                                        </td>
                                        <td class="panel-data-table__col--hide-sm">
                                            <span class="panel-metric-cell">{{ number_format($vehicle->views_count ?? 0) }}</span>
                                        </td>
                                        <td class="panel-data-table__col--hide-sm">
                                            <button type="button" class="panel-metric-cell--link" onclick="toggleInquiries({{ $vehicle->id }})">
                                                {{ number_format($vehicle->enquiries_count ?? 0) }}
                                            </button>
                                        </td>
                                        <td>
                                            <div class="panel-row-actions">
                                                <a
                                                    href="{{ route('seller.vehicle.edit', ['token' => $token, 'id' => $vehicle->id]) }}"
                                                    class="panel-icon-btn panel-icon-btn--primary"
                                                    title="{{ __('messages.pages.seller_dashboard.edit') }}"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </a>
                                                <button
                                                    type="button"
                                                    class="panel-icon-btn"
                                                    title="{{ __('messages.pages.seller_dashboard.inquiries') }}"
                                                    onclick="toggleInquiries({{ $vehicle->id }})"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                                    </svg>
                                                </button>
                                                <x-panel.action-menu :label="__('messages.pages.seller_dashboard.more_actions')">
                                                    @if ($vehicle->list_status_id == VehicleListStatus::PUBLISHED)
                                                        <button type="button" class="panel-dropdown__item" role="menuitem" onclick="unpublishVehicle({{ $vehicle->id }})">
                                                            {{ __('messages.pages.seller_dashboard.unpublish') }}
                                                        </button>
                                                    @else
                                                        <button type="button" class="panel-dropdown__item" role="menuitem" onclick="updateStatus({{ $vehicle->id }}, {{ VehicleListStatus::PUBLISHED }})">
                                                            {{ __('messages.pages.seller_dashboard.publish') }}
                                                        </button>
                                                    @endif
                                                    <button type="button" class="panel-dropdown__item panel-dropdown__item--danger" role="menuitem" onclick="deleteVehicle({{ $vehicle->id }})">
                                                        {{ __('messages.pages.seller_dashboard.delete') }}
                                                    </button>
                                                </x-panel.action-menu>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($vehicles->hasPages())
                        <div class="panel-pagination">
                            @if ($vehicles->onFirstPage())
                                <button type="button" disabled class="panel-btn panel-btn--outline panel-btn--sm" style="opacity:0.5;cursor:not-allowed">
                                    {{ __('messages.common.previous') }}
                                </button>
                            @else
                                <a href="{{ $vehicles->previousPageUrl() }}" class="panel-btn panel-btn--outline panel-btn--sm">
                                    {{ __('messages.common.previous') }}
                                </a>
                            @endif

                            <span class="panel-pagination__info">
                                Page {{ $vehicles->currentPage() }} of {{ $vehicles->lastPage() }}
                            </span>

                            @if ($vehicles->hasMorePages())
                                <a href="{{ $vehicles->nextPageUrl() }}" class="panel-btn panel-btn--outline panel-btn--sm">
                                    {{ __('messages.common.next') }}
                                </a>
                            @else
                                <button type="button" disabled class="panel-btn panel-btn--outline panel-btn--sm" style="opacity:0.5;cursor:not-allowed">
                                    {{ __('messages.common.next') }}
                                </button>
                            @endif
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div id="inquiries-drawer-backdrop" class="panel-drawer-backdrop" aria-hidden="true"></div>
<aside
    id="inquiries-drawer"
    class="panel-drawer"
    role="dialog"
    aria-modal="true"
    aria-labelledby="inquiries-drawer-title"
    aria-hidden="true"
>
    <div class="panel-drawer__header">
        <div>
            <h2 id="inquiries-drawer-title" class="panel-drawer__title">
                {{ __('messages.pages.seller_dashboard.vehicle_inquiries') }}
            </h2>
            <p id="inquiries-drawer-subtitle" class="panel-drawer__subtitle">
                {{ __('messages.pages.seller_dashboard.loading_inquiries') }}
            </p>
        </div>
        <button
            type="button"
            id="inquiries-drawer-close"
            class="panel-icon-btn"
            aria-label="{{ __('messages.dialogs.close_dialog') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6L6 18M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <div id="inquiries-drawer-content" class="panel-drawer__body">
        <div class="panel-table-empty">
            <p>{{ __('messages.pages.seller_dashboard.loading_inquiries') }}</p>
        </div>
    </div>
</aside>

@php
    $sellerDashboardConfig = [
        'token' => $token,
        'translations' => [
            'loadingInquiries' => __('messages.pages.seller_dashboard.loading_inquiries'),
            'inquiryCount' => __('messages.pages.seller_dashboard.inquiry_count', ['count' => ':count']),
            'inquiriesCount' => __('messages.pages.seller_dashboard.inquiries_count', ['count' => ':count']),
            'forThisVehicle' => __('messages.pages.seller_dashboard.for_this_vehicle'),
            'noInquiriesYet' => __('messages.pages.seller_dashboard.no_inquiries_yet'),
            'noInquiriesDescription' => __('messages.pages.seller_dashboard.no_inquiries_description'),
            'anonymous' => __('messages.pages.seller_dashboard.anonymous'),
            'noMessageProvided' => __('messages.pages.seller_dashboard.no_message_provided'),
            'failedToLoadInquiries' => __('messages.pages.seller_dashboard.failed_to_load_inquiries'),
            'errorLoadingInquiries' => __('messages.pages.seller_dashboard.error_loading_inquiries'),
            'errorOccurredLoading' => __('messages.pages.seller_dashboard.error_occurred_loading'),
            'confirmUnpublish' => __('messages.pages.seller_dashboard.confirm_unpublish'),
            'vehicleUnpublishedSuccessfully' => __('messages.pages.seller_dashboard.vehicle_unpublished_successfully'),
            'failedToUnpublish' => __('messages.pages.seller_dashboard.failed_to_unpublish'),
            'vehicleStatusUpdated' => __('messages.pages.seller_dashboard.vehicle_status_updated'),
            'failedToUpdateStatus' => __('messages.pages.seller_dashboard.failed_to_update_status'),
            'confirmDelete' => __('messages.pages.seller_dashboard.confirm_delete'),
            'vehicleDeletedSuccessfully' => __('messages.pages.seller_dashboard.vehicle_deleted_successfully'),
            'failedToDelete' => __('messages.pages.seller_dashboard.failed_to_delete'),
            'genericError' => __('messages.dialogs.error_occurred'),
            'sendEmail' => __('messages.pages.seller_dashboard.send_email'),
            'call' => __('messages.pages.seller_dashboard.call'),
        ],
    ];
@endphp

@push('scripts')
<script id="seller-dashboard-config" type="application/json">@json($sellerDashboardConfig)</script>
<script src="{{ asset('js/seller-dashboard.js') }}"></script>
@endpush
@endsection
