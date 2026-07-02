@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/panel-blade.css'])
@else
    <link rel="stylesheet" href="{{ asset('css/panel-blade.css') }}">
@endif
