@component('mail::message')
# {{ __('messages.mail.saved_search_match_heading') }}

{{ __('messages.mail.saved_search_match_body', ['count' => $matchCount]) }}

@component('mail::button', ['url' => $vehiclesUrl])
{{ __('messages.mail.saved_search_match_action') }}
@endcomponent

{{ __('messages.mail.thanks') }},<br>
{{ config('app.name') }}
@endcomponent
