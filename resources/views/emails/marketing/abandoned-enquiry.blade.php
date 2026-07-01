@component('mail::message')
# {{ __('messages.marketing.follow_up_greeting', ['name' => $meta['name'] ?? __('messages.common.customer')]) }}

{{ __('messages.marketing.abandoned_enquiry_body') }}

{{ __('messages.marketing.follow_up_signoff') }}

@endcomponent
