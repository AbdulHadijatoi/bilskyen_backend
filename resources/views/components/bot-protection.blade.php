{{-- Honeypot + optional Cloudflare Turnstile widget for public forms --}}
@php
    $turnstileSiteKey = config('security.turnstile.site_key');
    $honeypotField = config('security.honeypot.field', 'website');
@endphp
<div class="absolute -left-[9999px] top-auto h-0 w-0 overflow-hidden" aria-hidden="true">
    <label for="{{ $honeypotField }}">Website</label>
    <input type="text" name="{{ $honeypotField }}" id="{{ $honeypotField }}" value="" tabindex="-1" autocomplete="off">
</div>
@if(config('security.hardening_enabled') && filled($turnstileSiteKey))
    <div class="cf-turnstile mt-4" data-sitekey="{{ $turnstileSiteKey }}" data-theme="light"></div>
    @error('cf-turnstile-response')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
