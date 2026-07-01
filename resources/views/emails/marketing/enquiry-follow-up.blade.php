@component('mail::message')
# {{ __('messages.marketing.follow_up_greeting', ['name' => $enquiry->name ?? __('messages.common.customer')]) }}

@if($stepKey === 'day_3')
{{ __('messages.marketing.follow_up_day3_body') }}
@elseif($stepKey === 'reminder')
{{ __('messages.marketing.abandoned_enquiry_body') }}
@else
{{ __('messages.marketing.follow_up_day1_body') }}
@endif

@if($enquiry->vehicle)
**{{ $enquiry->vehicle->title }}**
@endif

{{ __('messages.marketing.follow_up_signoff') }}

@component('mail::button', ['url' => url('/vehicles/'.($enquiry->vehicle?->slug ?? ''))])
{{ __('messages.marketing.view_vehicle') }}
@endcomponent

{{ __('messages.marketing.unsubscribe_hint') }}

@endcomponent
