@props([
    'label' => 'More actions',
])

<div {{ $attributes->merge(['class' => 'panel-dropdown']) }} data-dropdown>
    <button
        type="button"
        class="panel-icon-btn panel-dropdown__trigger"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="{{ $label }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="2"></circle>
            <circle cx="12" cy="12" r="2"></circle>
            <circle cx="12" cy="19" r="2"></circle>
        </svg>
    </button>
    <div class="panel-dropdown__menu" role="menu">
        {{ $slot }}
    </div>
</div>
