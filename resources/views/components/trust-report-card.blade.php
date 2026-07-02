@props(['trustReport' => [], 'fairPrice' => null])

@php
    $tr = $trustReport ?? [];
    $hasContent = !empty($tr['has_registry_data']) || !empty($tr['inspection_date']) || !empty($tr['has_price_reduction']) || !empty($fairPrice);
@endphp

@if($hasContent)
<div class="detail-section bg-gradient-to-br from-blue-50 to-white border border-blue-100">
    <div class="flex items-start gap-3 mb-4">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <path d="m9 12 2 2 4-4"></path>
            </svg>
        </div>
        <div>
            <h2 class="text-foreground text-xl font-semibold">{{ __('messages.pages.vehicles.detail.trust_report_title') }}</h2>
            <p class="text-sm text-muted-foreground">{{ __('messages.pages.vehicles.detail.trust_report_subtitle') }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @if(!empty($tr['registry']))
        <div class="rounded-lg border border-border bg-white/80 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">{{ __('messages.pages.vehicles.detail.registration_status') }}</p>
            @php $reg = $tr['registry']; @endphp
            @if(!empty($reg['brand_name']))
                <p class="text-sm text-foreground"><span class="font-medium">{{ __('messages.forms.brand') }}:</span> {{ $reg['brand_name'] }}</p>
            @endif
            @if(!empty($reg['model_name']))
                <p class="text-sm text-foreground mt-1"><span class="font-medium">{{ __('messages.forms.model') }}:</span> {{ $reg['model_name'] }}</p>
            @endif
            @if(!empty($reg['first_registration_date']))
                <p class="text-sm text-muted-foreground mt-1">{{ __('messages.pages.vehicles.detail.first_registration_date') }}: {{ \Illuminate\Support\Carbon::parse($reg['first_registration_date'])->format('M Y') }}</p>
            @endif
            @if(!empty($reg['km_driven']))
                <p class="text-sm text-muted-foreground mt-1">{{ __('messages.forms.km_driven') }}: {{ number_format((float) $reg['km_driven']) }} km</p>
            @endif
        </div>
        @endif

        @if(!empty($tr['inspection_date']))
        <div class="rounded-lg border border-border bg-white/80 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">{{ __('messages.pages.vehicles.detail.inspection_details') }}</p>
            <p class="text-sm font-medium text-foreground">{{ \Illuminate\Support\Carbon::parse($tr['inspection_date'])->format('F j, Y') }}</p>
            @if(!empty($tr['inspection_result']))
                <p class="text-sm text-muted-foreground mt-1">{{ $tr['inspection_result'] }}</p>
            @endif
            @if(!empty($tr['inspection_odometer']))
                <p class="text-sm text-muted-foreground mt-1">{{ number_format((int) $tr['inspection_odometer']) }} km</p>
            @endif
            @if(!empty($tr['inspection_passed']))
                <span class="inline-flex mt-2 items-center rounded-md bg-green-100 px-2 py-1 text-xs font-semibold text-green-800">{{ __('messages.pages.vehicles.detail.trust_inspection_passed') }}</span>
            @endif
        </div>
        @endif

        <div class="rounded-lg border border-border bg-white/80 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">{{ __('messages.pages.vehicles.detail.pricing') }}</p>
            @if(!empty($tr['days_listed']))
                <p class="text-sm text-foreground">{{ __('messages.pages.vehicles.detail.trust_listed_days', ['days' => $tr['days_listed']]) }}</p>
            @endif
            @if(!empty($tr['has_price_reduction']) && !empty($tr['price_reduction_percent']))
                <p class="text-sm text-green-700 font-medium mt-1">{{ __('messages.pages.vehicles.detail.trust_price_reduced', ['percent' => $tr['price_reduction_percent']]) }}</p>
            @endif
            @if(!empty($fairPrice['label']))
                @php
                    $fairLabel = match($fairPrice['label']) {
                        'below_market' => __('messages.pages.vehicles.detail.fair_price_below_market'),
                        'above_market' => __('messages.pages.vehicles.detail.fair_price_above_market'),
                        default => __('messages.pages.vehicles.detail.fair_price_fair'),
                    };
                @endphp
                <span class="inline-flex mt-2 items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">{{ $fairLabel }}</span>
            @endif
        </div>
    </div>
</div>
@endif
