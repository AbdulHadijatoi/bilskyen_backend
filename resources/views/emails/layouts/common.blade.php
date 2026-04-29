<x-mail::message>
# {{ $heading }}

{{ $slot }}

@if(!empty($buttonUrl) && !empty($buttonText))
<x-mail::button :url="$buttonUrl">
{{ $buttonText }}
</x-mail::button>
@endif

{{ $footerText ?? __('messages.mail.subscription_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
