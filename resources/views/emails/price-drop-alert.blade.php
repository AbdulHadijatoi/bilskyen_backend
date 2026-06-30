@component('mail::message')
# {{ __('messages.mail.price_drop_alert_heading', ['vehicle' => $vehicleTitle]) }}

{{ __('messages.mail.price_drop_alert_body', ['price' => number_format($newPrice, 0, ',', '.') . ' ' . $currency]) }}

@component('mail::button', ['url' => $vehicleUrl])
{{ __('messages.mail.price_drop_alert_action') }}
@endcomponent

{{ __('messages.mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
