<x-mail::message>
# {{ $campaignName }}

{!! nl2br(e($body)) !!}

{{ __('messages.mail.subscription_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
