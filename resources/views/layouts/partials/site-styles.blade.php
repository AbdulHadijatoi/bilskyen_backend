@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/site-base.css'])
@else
    <link rel="stylesheet" href="{{ asset('css/site-base.css') }}">
@endif
