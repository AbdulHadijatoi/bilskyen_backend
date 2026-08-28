@props([
    'vd',
    'variant' => 'desktop',
])

@php
    use Illuminate\Support\Carbon;

    $variantClass = $variant === 'mobile'
        ? 'vehicle-registration-status--mobile'
        : 'vehicle-registration-status--desktop';
@endphp

@if(!empty($vd['registration_status']) || !empty($vd['dmr']['registration_status_name']) || !empty($vd['last_registration_change']))
<div {{ $attributes->merge(['class' => 'detail-section bg-gray-50 vehicle-registration-status ' . $variantClass]) }}>
    <h2 class="text-foreground text-xl font-semibold mb-4">{{ __('messages.pages.vehicles.detail.registration_status') }}</h2>
    <div class="detail-grid">
        @if(!empty($vd['registration_status']))
        <div class="detail-item">
            <span class="detail-label">{{ __('messages.pages.vehicles.detail.registration_status_label') }}</span>
            <span class="detail-value">{{ $vd['registration_status'] }}</span>
        </div>
        @endif
        @if(!empty($vd['dmr']['registration_status_name']))
        <div class="detail-item">
            <span class="detail-label">{{ __('messages.pages.vehicles.detail.registration_status_label') }} (DMR)</span>
            <span class="detail-value">{{ $vd['dmr']['registration_status_name'] }}</span>
        </div>
        @endif
        @if(!empty($vd['last_registration_change']))
        <div class="detail-item">
            <span class="detail-label">{{ __('messages.pages.vehicles.detail.registration_status_updated_date') }}</span>
            <span class="detail-value">{{ Carbon::parse($vd['last_registration_change'])->format('F j, Y') }}</span>
        </div>
        @endif
    </div>
</div>
@endif
