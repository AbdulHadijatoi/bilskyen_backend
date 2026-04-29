<x-mail::layout>
{{-- Header: logo links to site (replaces plain app name in default Laravel mail) --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<div style="text-align: center; line-height: 0;">
<img src="{{ url('/images/logo.png') }}" alt="{{ __('messages.layouts.logo_alt') }}" width="160" style="max-width: 160px; height: auto; border: 0; display: inline-block;">
</div>
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
