<x-mail::message>
# {{ __('messages.mail.subscription_change_approved_heading') }}

{{ __('messages.mail.subscription_change_approved_body', ['plan' => $changeRequest->requestedPlan->name ?? '']) }}

<x-mail::button :url="config('app.url')">
{{ __('messages.mail.subscription_change_view_panel') }}
</x-mail::button>

{{ __('messages.mail.subscription_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
