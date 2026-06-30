@component('mail::message')
# {{ __('messages.mail.lead_buyer_message_heading', ['vehicle' => $vehicleTitle]) }}

{{ __('messages.mail.lead_buyer_message_intro', ['dealer' => $dealerName]) }}

{{ $messageBody }}

{{ __('messages.mail.lead_buyer_message_footer') }}

{{ __('messages.mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
