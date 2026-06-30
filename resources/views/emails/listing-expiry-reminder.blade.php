@component('mail::message')
# {{ __('messages.mail.listing_expiry_reminder_heading') }}

{{ __('messages.mail.listing_expiry_reminder_body', ['vehicle' => $vehicleTitle, 'days' => $daysRemaining]) }}

@component('mail::button', ['url' => $manageUrl])
{{ __('messages.mail.listing_expiry_reminder_action') }}
@endcomponent

{{ __('messages.mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
