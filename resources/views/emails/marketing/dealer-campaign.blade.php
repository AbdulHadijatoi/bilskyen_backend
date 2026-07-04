<x-mail::message>
# {{ $campaignName }}

{!! nl2br(e($body)) !!}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
